<?php

/**
 * Vocabulary dataobjects provide access to the vocabularies on the SPARQL endpoint.
 */
class Vocabulary extends DataObject
{
    /** cached value of URI space */
    private $urispace = null;
    private $config;

    public function __construct($model, $resource)
    {
        parent::__construct($model, $resource);
        $this->config = new VocabularyConfig($resource, $model->getConfig()->getGlobalPlugins());
    }

    /**
     * Returns the VocabularyConfig object
     * @return VocabularyConfig
     */
    public function getConfig()
    {
      return $this->config;
    }

    /**
     * Get the SPARQL endpoint URL for this vocabulary
     *
     * @return string endpoint URL
     */
    public function getEndpoint()
    {
        return $this->resource->get('void:sparqlEndpoint')->getUri();
    }

    /**
     * Get the SPARQL graph URI for this vocabulary
     *
     * @return string graph URI
     */
    public function getGraph()
    {
        $graph = $this->resource->get('skosmos:sparqlGraph');
        if ($graph) {
            $graph = $graph->getUri();
        }

        return $graph;
    }

    /**
     * Get the SPARQL implementation for this vocabulary
     *
     * @return Sparql SPARQL object
     */
    public function getSparql()
    {
        $endpoint = $this->getEndpoint();
        $graph = $this->getGraph();
        $dialect = $this->resource->get('skosmos:sparqlDialect');
        $dialect = $dialect ? $dialect->getValue() : $this->model->getConfig()->getDefaultSparqlDialect();

        return $this->model->getSparqlImplementation($dialect, $endpoint, $graph);
    }

    /**
     * Get the URI space of concepts in this vocabulary.
     *
     * @return string full URI of concept
     */
    public function getUriSpace()
    {
        if ($this->urispace === null) // initialize cache
        {
            $this->urispace = $this->resource->getLiteral('void:uriSpace')->getValue();
        }

        return $this->urispace;
    }

    /**
     * Get the full URI of a concept in a vocabulary. If the passed local
     * name is already a full URI, return it unchanged.
     *
     * @param $lname string local name of concept
     * @return string full URI of concept
     */
    public function getConceptURI($lname)
    {
        if (strpos($lname, 'http') === 0) {
            return $lname;
        }
        // already a full URI
        return $this->getUriSpace() . $lname;
    }

    /**
     * Asks the sparql implementation to make a label query for a uri.
     * @param string $uri
     * @param string $lang
     */
    public function getConceptLabel($uri, $lang)
    {
        return $this->getSparql()->queryLabel($uri, $lang);
    }

    /**
     * Get the localname of a concept in the vocabulary. If the URI is not
     * in the URI space of this vocabulary, return the full URI.
     *
     * @param $uri string full URI of concept
     * @return string local name of concept, or original full URI if the local name cannot be determined
     */
    public function getLocalName($uri)
    {
        return str_replace($this->getUriSpace(), "", $uri);
    }

    /**
     * Retrieves all the information about the Vocabulary
     * from the SPARQL-endpoint.
     */
    public function getInfo($lang = null)
    {
        $ret = array();
        if (!$lang) {
            $lang = $this->getEnvLang();
        }

        // get metadata (literals only e.g. name) from vocabulary configuration file
        foreach ($this->resource->properties() as $prop) {
            foreach ($this->resource->allLiterals($prop, $lang) as $val) {
                $ret[$prop][] = $val->getValue();
            }
        }

        // also include ConceptScheme metadata from SPARQL endpoint
        $defaultcs = $this->getDefaultConceptScheme();

        // query everything the endpoint knows about the ConceptScheme
        $sparql = $this->getSparql();
        $result = $sparql->queryConceptScheme($defaultcs);
        $conceptscheme = $result->resource($defaultcs);
        $this->order = array("dc:title", "dc11:title", "skos:prefLabel", "rdfs:label", "dc:subject", "dc11:subject", "dc:description", "dc11:description", "dc:publisher", "dc11:publisher", "dc:creator", "dc11:creator", "dc:contributor", "dc:language", "dc11:language", "owl:versionInfo", "dc:source", "dc11:source");

        foreach ($conceptscheme->properties() as $prop) {
            foreach ($conceptscheme->allLiterals($prop, $lang) as $val) {
                $prop = (substr($prop, 0, 5) == 'dc11:') ? str_replace('dc11:', 'dc:', $prop) : $prop;
                $ret[$prop][$val->getValue()] = $val;
            }
            if (!isset($ret[$prop]) || sizeof($ret[$prop]) == 0) { // not found with language tag
                foreach ($conceptscheme->allLiterals($prop, null) as $val) {
                    $prop = (substr($prop, 0, 5) == 'dc11:') ? str_replace('dc11:', 'dc:', $prop) : $prop;
                    if ($val->getValue() instanceof DateTime) {
                        $val = Punic\Calendar::formatDate($val->getValue(), 'full', $lang) . ' ' . Punic\Calendar::format($val->getValue(), 'HH:mm:ss', $lang);
                    }
                    $ret[$prop][] = $val;
                }
            }
            foreach ($conceptscheme->allResources($prop) as $val) {
                $prop = (substr($prop, 0, 5) == 'dc11:') ? str_replace('dc11:', 'dc:', $prop) : $prop;
                $exvocab = $this->model->guessVocabularyFromURI($val->getURI());
                $exlabel = $this->getExternalLabel($exvocab, $val->getURI(), $lang);
                if (isset($exlabel)) {
                    $val->add('skosmos:vocab', $exvocab->getId());
                    $val->add('skosmos:label', $exlabel);
                }
                $label = $val->label($lang) ? $val->label($lang)->getValue() : $val->getUri();
                $ret[$prop][$exlabel ? $exlabel->getValue() : $label] = $val;
            }
            if (isset($ret[$prop])) {
                ksort($ret[$prop]);
            }
        }
        if (isset($ret['owl:versionInfo'])) { // if version info availible for vocabulary convert it to a more readable format
            $ret['owl:versionInfo'][0] = $this->parseVersionInfo($ret['owl:versionInfo'][0]);
        }
        // remove duplicate values
        foreach (array_keys($ret) as $prop) {
            $ret[$prop] = array_unique($ret[$prop]);
        }

        $ret = $this->arbitrarySort($ret);

        // filtering multiple labels
        if (isset($ret['dc:title'])) {
            unset($ret['dc11:title'], $ret['skos:prefLabel'], $ret['rdfs:label']);
        } else if (isset($ret['dc11:title'])) {
            unset($ret['skos:prefLabel'], $ret['rdfs:label']);
        } else if (isset($ret['skos:prefLabel'])) {
            unset($ret['rdfs:label']);
        }

        return $ret;
    }

    /**
     * Return all concept schemes in the vocabulary.
     * @return array Array with concept scheme URIs (string) as keys and labels (string) as values
     */

    public function getConceptSchemes($lang = '')
    {
        if ($lang === '') {
            $lang = $this->getEnvLang();
        }

        return $this->getSparql()->queryConceptSchemes($lang);
    }

    /**
     * Return the URI of the default concept scheme of this vocabulary. If the skosmos:mainConceptScheme property is set in the
     * vocabulary configuration, that will be returned. Otherwise an arbitrary concept scheme will be returned.
     * @return string concept scheme URI
     */

    public function getDefaultConceptScheme()
    {
        $conceptScheme = $this->config->getMainConceptSchemeURI();
        if ($conceptScheme) {
            return $conceptScheme;
        }

        // mainConceptScheme not explicitly set, guess it
        $conceptSchemes = $this->getConceptSchemes();
        $conceptSchemeURIs = array_keys($conceptSchemes);
        // return the URI of the last concept scheme
        return array_pop($conceptSchemeURIs);
    }

    /**
     * Return the top concepts of a concept scheme in the vocabulary.
     * @param string $conceptScheme URI of concept scheme whose top concepts to return. If not set,
     *                              the default concept scheme of the vocabulary will be used.
     * @param string $lang preferred language for the concept labels,
     * @return array Array with concept URIs (string) as keys and labels (string) as values
     */

    public function getTopConcepts($conceptScheme = null, $lang = '')
    {
        if ($lang === '') {
            $lang = $this->getEnvLang();
        }
        $fallback = $this->config->getDefaultLanguage();

        if ($conceptScheme === null || $conceptScheme == '') {
            $conceptScheme = $this->getDefaultConceptScheme();
        }

        return $this->getSparql()->queryTopConcepts($conceptScheme, $lang, $fallback);
    }

    /**
     * Tries to parse version, date and time from sparql version information into a readable format.
     * @param string $version
     * @return string
     */
    private function parseVersionInfo($version)
    {
        $parts = explode(' ', $version);
        if ($parts[0] != '$Id:') {
            return $version;
        }
        // don't know how to parse
        $rev = $parts[2];
        $datestr = $parts[3] . ' ' . $parts[4];

        return "$datestr (r$rev)";
    }

    /**
     * Counts the statistics of the vocabulary.
     * @return array of the concept/group counts
     */
    public function getStatistics($lang = '', $array=null, $group=null)
    {
        $sparql = $this->getSparql();
        // find the number of concepts
        return $sparql->countConcepts($lang, $array, $group);
    }

    /**
     * Counts the statistics of the vocabulary.
     * @return array of the concept counts in different languages
     */
    public function getLabelStatistics()
    {
        $sparql = $this->getSparql();
        $ret = array();
        // count the number of different types of concepts in all languages
        $ret['terms'] = $sparql->countLangConcepts($this->config->getLanguages(), $this->config->getIndexClasses());

        return $ret;
    }

    /**
     * Gets the parent concepts of a concept and child concepts for all of those.
     * @param string $uri
     * @param string $lang language identifier.
     */
    public function getConceptHierarchy($uri, $lang)
    {
        $lang = $lang ? $lang : $this->getEnvLang();
        $fallback = count($this->config->getLanguageOrder($lang)) > 1 ? $this->config->getLanguageOrder($lang)[1] : $this->config->getDefaultLanguage();
        $props = $this->config->getHierarchyProperty();
        return $this->getSparql()->queryParentList($uri, $lang, $fallback, $props);
    }

    /**
     * Gets the child relations of a concept and whether these children have more children.
     * @param string $uri
     */
    public function getConceptChildren($uri, $lang)
    {
        $lang = $lang ? $lang : $this->getEnvLang();
        $fallback = $this->config->getDefaultLanguage();
        $props = $this->config->getHierarchyProperty();
        return $this->getSparql()->queryChildren($uri, $lang, $fallback, $props);
    }

    /**
     * Gets the skos:narrower relations of a concept.
     * @param string $uri
     * @param string $lang language identifier.
     */
    public function getConceptNarrowers($uri, $lang)
    {
        $lang = $lang ? $lang : $this->getEnvLang();
        return $this->getSparql()->queryProperty($uri, 'skos:narrower', $lang);
    }

    /**
     * Gets the skos:narrowerTransitive relations of a concept.
     * @param string $uri
     * @param integer $limit
     * @param string $lang language identifier.
     */
    public function getConceptTransitiveNarrowers($uri, $limit, $lang)
    {
        $lang = $lang ? $lang : $this->getEnvLang();
        return $this->getSparql()->queryTransitiveProperty($uri, array('skos:narrower'), $lang, $limit);
    }

    /**
     * Gets the skos:broader relations of a concept.
     * @param string $uri
     * @param string $lang language identifier.
     */
    public function getConceptBroaders($uri, $lang)
    {
        $lang = $lang ? $lang : $this->getEnvLang();
        return $this->getSparql()->queryProperty($uri, 'skos:broader', $lang);
    }

    /**
     * Gets the skos:broaderTransitive relations of a concept.
     * @param string $uri
     * @param integer $limit
     * @param boolean $any set to true if you want to have a label even in case of a correct language one missing.
     * @param string $lang language identifier.
     */
    public function getConceptTransitiveBroaders($uri, $limit, $any = false, $lang)
    {
        $lang = $lang ? $lang : $this->getEnvLang();
        $fallback = $this->config->getDefaultLanguage();
        return $this->getSparql()->queryTransitiveProperty($uri, array('skos:broader'), $lang, $limit, $any, $fallback);
    }

    /**
     * Gets all the skos:related concepts of a concept.
     * @param string $uri
     * @param string $lang language identifier.
     */
    public function getConceptRelateds($uri, $lang)
    {
        $lang = $lang ? $lang : $this->getEnvLang();
        return $this->getSparql()->queryProperty($uri, 'skos:related', $lang);
    }

    /**
     * Makes a query into the sparql endpoint for a concept.
     * @param string $uri the full URI of the concept
     * @return array
     */
    public function getConceptInfo($uri, $clang)
    {
        $sparql = $this->getSparql();

        return $sparql->queryConceptInfo($uri, $this->config->getArrayClassURI(), array($this), $clang);
    }

    /**
     * Lists the different concept groups available in the vocabulary.
     * @param string $clang content language parameter
     * @return array
     */
    public function listConceptGroups($clang = null)
    {
        if ($clang === null || $clang == '') {
            $clang = $this->getEnvLang();
        }

        $ret = array();
        $gclass = $this->config->getGroupClassURI();
        if ($gclass === null) {
            return $ret;
        }
        // no group class defined, so empty result
        $groups = $this->getSparql()->listConceptGroups($gclass, $clang);
        foreach ($groups as $uri => $label) {
            $ret[$uri] = $label;
        }

        return $ret;
    }

    /**
     * Lists the concepts available in the concept group.
     * @param $clname
     * @return array
     */
    public function listConceptGroupContents($glname, $clang)
    {
        if (!$clang) {
            $clang = $this->config->getEnvLang();
        }

        $ret = array();
        $gclass = $this->config->getGroupClassURI();
        if ($gclass === null) {
            return $ret;
        }
        // no group class defined, so empty result
        $group = $this->getConceptURI($glname);
        $contents = $this->getSparql()->listConceptGroupContents($gclass, $group, $clang, $this->config->getShowDeprecated());
        foreach ($contents as $uri => $label) {
            $ret[$uri] = $label;
        }

        return $ret;
    }

    /**
     * Returns the letters of the alphabet which have been used in this vocabulary.
     * The returned letters may also include specials such as '0-9' (digits) and '!*' (special characters).
     * @param $clang content language
     * @return array array of letters
     */
    public function getAlphabet($clang)
    {
        $chars = $this->getSparql()->queryFirstCharacters($clang, $this->config->getIndexClasses());
        $letters = array();
        $digits = false;
        $specials = false;
        foreach ($chars as $char) {
            if (preg_match('/\p{L}/u', $char)) {
                $letters[] = $char;
            } elseif (preg_match('/\d/u', $char)) {
                $digits = true;
            } else {
                $specials = true;
            }
        }
        usort($letters, 'strcoll');
        if ($specials) {
            $letters[] = '!*';
        }

        if ($digits) {
            $letters[] = '0-9';
        }

        return $letters;
    }

    /**
     * Searches for concepts with a label starting with the specified letter.
     * Also the special tokens '0-9' (digits), '!*' (special characters) and '*'
     * (everything) are supported.
     * @param $letter letter (or special token) to search for
     */
    public function searchConceptsAlphabetical($letter, $limit = null, $offset = null, $clang = null)
    {
        return $this->getSparql()->queryConceptsAlphabetical($letter, $clang, $limit, $offset, $this->config->getIndexClasses(),$this->config->getShowDeprecated());
    }

    /**
     * Makes a query for the transitive broaders of a concept and returns the concepts hierarchy processed for the view.
     * @param string $lang
     * @param string $uri
     */
    public function getBreadCrumbs($lang, $uri)
    {
        $broaders = $this->getConceptTransitiveBroaders($uri, 1000, true, $lang);
        $origCrumbs = $this->getCrumbs($broaders, $uri);
        return $this->combineCrumbs($origCrumbs);
    }

    /**
     * Takes the crumbs as a parameter and combines the crumbs if the path they form is too long.
     * @return array
     */
    private function combineCrumbs($origCrumbs)
    {
        $combined = array();
        foreach ($origCrumbs as $pathKey => $path) {
            $firstToCombine = true;
            $combinedPath = array();
            foreach ($path as $crumbKey => $crumb) {
                if ($crumb->getPrefLabel() === '...') {
                    array_push($combinedPath, $crumb);
                    if ($firstToCombine) {
                        $firstToCombine = false;
                    } else {
                        unset($origCrumbs[$pathKey][$crumbKey]);
                    }
                }
            }
            $combined[] = $combinedPath;
        }

        return array('combined' => $combined, 'breadcrumbs' => $origCrumbs);
    }

    /**
     * Recursive function for building the breadcrumb paths for the view.
     * @param array $bTresult contains the results of the broaderTransitive query.
     * @param string $uri
     * @param array $path
     */
    private function getCrumbs($bTresult, $uri, $path = null)
    {
        $crumbs = array();
        if (!isset($path)) {
            $path = array();
        }

        // check that there is no cycle (issue #220)
        foreach ($path as $childcrumb) {
            if ($childcrumb->getUri() == $uri) {
                // found a cycle - short-circuit and stop
                return $crumbs;
            }
        }
        if (isset($bTresult[$uri]['direct'])) {
            foreach ($bTresult[$uri]['direct'] as $broaderUri) {
                $newpath = array_merge($path, array(new Breadcrumb($uri, $bTresult[$uri]['label'])));
                if ($uri !== $broaderUri) {
                    $crumbs = array_merge($crumbs, $this->getCrumbs($bTresult, $broaderUri, $newpath));
                }
            }
        } else { // we have reached the end of a path and we need to start a new row in the 'stack'
            if (isset($bTresult[$uri])) {
                $path = array_merge($path, array(new Breadcrumb($uri, $bTresult[$uri]['label'])));
            }

            $index = 1;
            $length = sizeof($path);
            $limit = $length - 5;
            foreach ($path as $crumb) {
                if ($length > 5 && $index > $length - $limit) { // displays 5 concepts closest to the concept.
                    $crumb->hideLabel();
                }
                $index++;
            }
            $crumbs[] = array_reverse($path);
        }
        return $crumbs;
    }

    /**
     * Verify that the requested language is supported by the vocabulary. If not, returns
     * the default language of the vocabulary.
     * @param string $lang language to check
     * @return string language tag that is supported by the vocabulary
     */

    public function verifyVocabularyLanguage($lang)
    {
        return (in_array($lang, $this->config->getLanguages())) ? $lang : $this->config->getDefaultLanguage();
    }

    /**
     * Returns a list of recently changed or entirely new concepts.
     * @param string $clang content language for the labels
     * @param string $lang UI language for the dates
     * @return Array
     */
    public function getChangeList($prop, $clang, $lang, $offset)
    {
      $changelist = $this->getSparql()->queryChangeList($clang, $offset, $prop);
      $bydate = array();
      foreach($changelist as $concept) {
        $concept['datestring'] = Punic\Calendar::formatDate($concept['date'], 'medium', $lang);
        $bydate[Punic\Calendar::getMonthName($concept['date'], 'wide', $lang, true) . Punic\Calendar::format($concept['date'], ' y', $lang) ][strtolower($concept['prefLabel'])] = $concept;
      }
      return $bydate;
    }

    public function getTitle($lang=null) {
      return $this->config->getTitle($lang);
    }

    public function getShortName() {
      return $this->config->getShortName();
    }

    public function getId() {
      return $this->config->getId();
    }

}
