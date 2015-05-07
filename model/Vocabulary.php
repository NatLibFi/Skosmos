<?php
/**
 * Copyright (c) 2012-2013 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/**
 * Vocabulary dataobjects provide access to the vocabularies on the SPARQL endpoint.
 */
class Vocabulary extends DataObject
{
  /** the preferred order of the vocabulary information properties */
  public $order;

  /** cached value of URI space */
  private $urispace = null;

  /**
   * Extracts the vocabulary id string from the baseuri of the vocabulary.
   * @return string identifier eg. 'mesh'.
   */
  public function getId()
  {
    $uriparts = explode("#", $this->resource->getURI());
    if (count($uriparts) != 1)
    // hash namespace
      return $uriparts[1];
    // slash namespace
    $uriparts = explode("/", $voc->getURI());

    return $uriparts[count($uriparts) - 1];
  }

  /**
   * Returns the human readable vocabulary title.
   * @return string the title of the vocabulary
   */
  public function getTitle()
  {
    $literal = $this->resource->getLiteral('dc:title', $this->lang);
    if ($literal)
      return $literal->getValue();
    // not found with selected language, try any language
    return $this->resource->getLiteral('dc:title')->getValue();
  }

  /**
   * Get the languages supported by this vocabulary
   * @return array languages supported by this vocabulary (as language tag strings)
   */
  public function getLanguages()
  {
    $langs = $this->resource->allLiterals('skosmos:language');
    $ret = array();
    foreach ($langs as $lang) {
      $ret[] = $lang->getValue();
    }

    return $ret;
  }
  
  /**
   * Returns the vocabulary default sidebar view.
   * @return string name of the view 
   */
  public function getDefaultSidebarView()
  {
    $defview = $this->resource->getLiteral('skosmos:defaultSidebarView');
    if ($defview) {
      $value = $defview->getValue();
      if ($value === 'groups' || $value === 'hierarchy') 
        return $value;
    }
    return 'alphabetical'; // if not defined displaying the alphabetical index 
  }

  /**
   * Get the default language of this vocabulary
   * @return string default language, e.g. 'en'
   */

  public function getDefaultLanguage()
  {
    $deflang = $this->resource->getLiteral('skosmos:defaultLanguage');
    if ($deflang) return $deflang->getValue();
    $langs = $this->getLanguages();
    if (sizeof($langs) > 1)
      trigger_error("Default language for vocabulary '" . $this->getId() . "' unknown, choosing '$langs[0]'.", E_USER_WARNING);

    return $langs[0];
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
    if ($graph)
      $graph = $graph->getUri();

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
    $dialect = $dialect ? $dialect->getValue() : DEFAULT_SPARQL_DIALECT;

    return $this->model->getSparqlImplementation($dialect, $endpoint, $graph);
  }

  /**
   * Get the URI space of concepts in this vocabulary.
   *
   * @return string full URI of concept
   */
  public function getUriSpace()
  {
    if ($this->urispace == null) // initialize cache
      $this->urispace = $this->resource->getLiteral('void:uriSpace')->getValue();

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
    if (strpos($lname, 'http') === 0) return $lname; // already a full URI
    return $this->getUriSpace() . $lname;
  }

  /**
   * Asks the sparql implementation to make a label query for a uri.
   * @param string $uri
   * @param string $lang
   */
  public function getConceptLabel($uri, $lang)
  {
    return $this->getSparql()->queryLabel($uri,$lang);
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
   * Wether the alphabetical index is small enough to be shown all at once.
   * @return boolean true if all concepts can be shown at once.
   */
  public function getAlphabeticalFull()
  {
    $val = $this->resource->getLiteral('skosmos:fullAlphabeticalIndex');
    if ($val)
      return filter_var($val->getValue(), FILTER_VALIDATE_BOOLEAN);
    return false;
  }
  
  /**
   * Returns a short name for a vocabulary if configured. If that has not been set 
   * using vocabId as a fallback.
   * @return string
   */
  public function getShortName()
  {
    $val = $this->resource->getLiteral('skosmos:shortName', $this->lang);
    if ($val)
      return $val->getValue();
    // not found with selected language, try any language
    $val = $this->resource->getLiteral('skosmos:shortName');
    if ($val)
      return $val->getValue();
    // if no shortname exists fall back to the id
    return $this->getId();
  }
  
  /**
   * Get the vocabulary feedback e-mail address and return it.
   *
   * @return string e-mail address or null if not defined.
   */
  public function getFeedbackRecipient()
  {
    $email = $this->resource->get('skosmos:feedbackRecipient');
    return isset($email) ? $email->getValue() : null;
  }

  /**
   * Retrieves all the information about the Vocabulary
   * from the SPARQL-endpoint.
   */
  public function getInfo($lang=null)
  {
    $ret = array();
    if (!$lang)
      $lang = $this->lang;

    // get metadata from vocabulary configuration file
    foreach ($this->resource->properties() as $prop) {
      foreach ($this->resource->allLiterals($prop, $lang) as $val) {
        $ret[$prop][] = $val->getValue();
      }
      foreach ($this->resource->allResources($prop) as $val) {
        $label = $val->label($lang);
        if ($label) {
          $ret[$prop][] = $label->getValue();
        }
      }
    }

    // also include ConceptScheme metadata from SPARQL endpoint
    $cs = $this->getDefaultConceptScheme();

    // query everything the endpoint knows about the ConceptScheme
    $sparql = $this->getSparql();
    $result = $sparql->queryConceptScheme($cs);
    $cs = $result->resource($cs);
    $this->order = array("dc:title","dc11:title","skos:prefLabel","rdfs:label","dc:subject", "dc11:subject", "dc:description", "dc11:description","dc:publisher","dc11:publisher","dc:creator","dc11:creator","dc:contributor", "dc:language", "dc11:language","owl:versionInfo","dc:source", "dc11:source");

    foreach ($cs->properties() as $prop) {
      foreach ($cs->allLiterals($prop, $lang) as $val) {
        $ret[$prop][] = $val->getValue();
      }
      if (!isset($ret[$prop]) || sizeof($ret[$prop]) == 0) { // not found with language tag
        foreach ($cs->allLiterals($prop, null) as $val) {
          $v = $val->getValue();
          if ($v instanceof DateTime) {
            $v = Punic\Calendar::formatDate($v, 'full', $lang) . ' ' . Punic\Calendar::format($v, 'HH:mm:ss', $lang);
          }
          $ret[$prop][] = $v;
        }
      }
      foreach ($cs->allResources($prop) as $val) {
        $label = $val->label($lang);
        if ($label) {
          $ret[$prop][] = $label->getValue();
        } else {
          $exvocab = $this->model->guessVocabularyFromURI($val->getURI());
          $exlabel = $this->getExternalLabel($exvocab, $val->getURI(), $lang);
          $ret[$prop][] = isset($exlabel) ? $exlabel : $val->getURI();
        }
      }
    }
    if (isset($ret['owl:versionInfo'])) { // if version info availible for vocabulary convert it to a more readable format
      $ret['owl:versionInfo'][0] = $this->parseVersionInfo($ret['owl:versionInfo'][0]);
    }
    // remove duplicate values
    foreach (array_keys($ret) as $prop)
      $ret[$prop] = array_unique($ret[$prop]);
    $ret = $this->arbitrarySort($ret);

    // filtering multiple labels
    if (isset($ret['dc:title']))
      unset($ret['dc11:title'],$ret['skos:prefLabel'],$ret['rdfs:label']);
    else if(isset($ret['dc11:title']))
      unset($ret['skos:prefLabel'],$ret['rdfs:label']);
    else if(isset($ret['skos:prefLabel']))
      unset($ret['rdfs:label']);

    return $ret;
  }

  /**
   * Return all concept schemes in the vocabulary.
   * @return array Array with concept scheme URIs (string) as keys and labels (string) as values
   */

  public function getConceptSchemes()
  {
    return $this->getSparql()->queryConceptSchemes($this->lang);
  }

  /**
   * Return the URI of the default concept scheme of this vocabulary. If the skosmos:mainConceptScheme property is set in the
   * vocabulary configuration, that will be returned. Otherwise an arbitrary concept scheme will be returned.
   * @return string concept scheme URI
   */

  public function getDefaultConceptScheme()
  {
    $conceptScheme = $this->resource->get("skosmos:mainConceptScheme");
    if ($conceptScheme) return $conceptScheme->getUri();

    // mainConceptScheme not explicitly set, guess it
    foreach ($this->getConceptSchemes() as $uri => $csdata) {
      $conceptScheme = $uri; // actually pick the last one
    }

    return $conceptScheme;
  }

  /**
   * Return the top concepts of a concept scheme in the vocabulary.
   * @param string $conceptScheme URI of concept scheme whose top concepts to return. If not set,
   *                              the default concept scheme of the vocabulary will be used.
   * @param string $lang preferred language for the concept labels,
   * @return array Array with concept URIs (string) as keys and labels (string) as values
   */

  public function getTopConcepts($conceptScheme=null, $lang='')
  {
    if ($lang === '')
      $lang = $this->lang;
    if (!$conceptScheme)
      $conceptScheme = $this->getDefaultConceptScheme();

    return $this->getSparql()->queryTopConcepts($conceptScheme, $lang);
  }

  /**
   * Tries to parse version, date and time from sparql version information into a readable format.
   * @param string $version
   * @return string
   */
  private function parseVersionInfo($version)
  {
    $parts = explode(' ', $version);
    if ($parts[0] != '$Id:') return $version; // don't know how to parse
    $rev = $parts[2];
    $datestr = $parts[3] . ' ' . $parts[4];

    return "$datestr (r$rev)";
  }

  /**
   * Counts the statistics of the vocabulary.
   * @return array of the concept counts in different languages
   */
  public function getStatistics()
  {
    $sparql = $this->getSparql();
    $ret = array();
    // find the number of concepts
    $ret['concepts'] = $sparql->countConcepts();

    return $ret;
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
    $ret['terms'] = $sparql->countLangConcepts($this->getLanguages());

    return $ret;
  }

  /**
   * get the URLs from which the vocabulary data can be downloaded
   * @return array Array with MIME type as key, URL as value
   */
  public function getDataURLs()
  {
    $ret = array();
    $urls = $this->resource->allResources("void:dataDump");
    foreach ($urls as $url) {
      // first try dc:format and dc11:format
      $mimetypelit = $url->getLiteral('dc:format');
      if ($mimetypelit === null)
        $mimetypelit = $url->getLiteral('dc11:format');
      // if still not found, guess MIME type using file extension
      if ($mimetypelit !== null) {
        $mimetype = $mimetypelit->getValue();
      } else {
        $format = EasyRdf_Format::guessFormat(null, $url->getURI());
        if ($format === null) {
          trigger_error("Could not guess format for <$url>.", E_USER_WARNING);
          continue;
        }
        $mimetypes = array_keys($format->getMimeTypes());
        $mimetype = $mimetypes[0];
      }
      $ret[$mimetype] = $url->getURI();
    }
    return $ret;
  }

  /**
   * Returns the class URI used for concept groups in this vocabulary,
   * or null if not set.
   * @return string group class URI or null
   */

  public function getGroupClassURI()
  {
    $val = $this->resource->getResource("skosmos:groupClass");
    if ($val)
      return $val->getURI();
    return null;
  }

  /**
   * Returns the class URI used for thesaurus arrays in this vocabulary,
   * or null if not set.
   * @return string array class URI or null
   */

  public function getArrayClassURI()
  {
    $val = $this->resource->getResource("skosmos:arrayClass");
    if ($val)
      return $val->getURI();
    return null;
  }

  /**
   * Returns a boolean value set in the vocabularies.ttl config.
   * @return boolean
   */
  public function getShowHierarchy()
  {
    $val = $this->resource->getLiteral("skosmos:showTopConcepts");
    if ($val)
      return filter_var($val->getValue(), FILTER_VALIDATE_BOOLEAN);
    return false;
  }
  
  /**
   * Returns a boolean value set in the vocabularies.ttl config.
   * @return boolean defaults to true if fetching hasn't been explicitly denied.
   */
  public function getExternalResourcesLoading()
  {
    $val = $this->resource->getLiteral("skosmos:loadExternalResources");
    if ($val)
      return filter_var($val->getValue(), FILTER_VALIDATE_BOOLEAN);
    return true;
  }
  
  
  /**
   * Returns a boolean value set in the vocabularies.ttl config.
   * @return boolean
   */
  public function getShowLangCodes()
  {
    $val = $this->resource->getLiteral("skosmos:explicitLanguageTags");
    if ($val)
      return filter_var($val->getValue(), FILTER_VALIDATE_BOOLEAN);
    return false;
  }

  /**
   * Returns a boolean value set in the vocabularies.ttl config.
   * @return array array of concept class URIs (can be empty) 
   */
  public function getIndexClasses()
  {
    $resources = $this->resource->allResources("skosmos:indexShowClass");
    $ret = array();
    foreach ($resources as $res) {
      $ret[] = $res->getURI();
    }
    return $ret;
  }

  /**
   * Gets the parent concepts of a concept and child concepts for all of those.
   * @param string $uri
   * @param string $lang language identifier.
   */
  public function getConceptHierarchy($uri, $lang)
  {
    $lang = $lang ? $lang : $this->lang;
    $fallback = $this->getDefaultLanguage();
    return $this->getSparql()->queryParentList($uri, $lang, $fallback);
  }

  /**
   * Gets the child relations of a concept and whether these children have more children.
   * @param string $uri
   */
  public function getConceptChildren($uri, $lang)
  {
    $lang = $lang ? $lang : $this->lang;
    return $this->getSparql()->queryChildren($uri, $lang);
  }

  /**
   * Gets the skos:narrower relations of a concept.
   * @param string $uri
   * @param string $lang language identifier.
   */
  public function getConceptNarrowers($uri, $lang)
  {
    $lang = $lang ? $lang : $this->lang;
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
    $lang = $lang ? $lang : $this->lang;
    return $this->getSparql()->queryTransitiveProperty($uri, 'skos:narrower',$lang,$limit);
  }

  /**
   * Gets the skos:broader relations of a concept.
   * @param string $uri
   * @param string $lang language identifier.
   */
  public function getConceptBroaders($uri, $lang)
  {
    $lang = $lang ? $lang : $this->lang;
    return $this->getSparql()->queryProperty($uri, 'skos:broader', $lang);
  }

  /**
   * Gets the skos:broaderTransitive relations of a concept.
   * @param string $uri
   * @param integer $limit
   * @param boolean $any set to true if you want to have a label even in case of a correct language one missing.
   */
  public function getConceptTransitiveBroaders($uri, $limit, $any=false, $lang)
  {
    $lang = $lang ? $lang : $this->lang;
    $fallback = $this->getDefaultLanguage();
    return $this->getSparql()->queryTransitiveProperty($uri, 'skos:broader', $lang, $limit, $any, $fallback);
  }

  /**
   * Gets all the skos:related concepts of a concept.
   * @param string $uri
   * @param string $lang language identifier.
   */
  public function getConceptRelateds($uri, $lang)
  {
    $lang = $lang ? $lang : $this->lang;
    return $this->getSparql()->queryProperty($uri,'skos:related', $lang);
  }

  /**
   * Makes a query into the sparql endpoint for a concept.
   * @param string $uri the full URI of the concept
   * @return array
   */
  public function getConceptInfo($uri, $clang)
  {
    $sparql = $this->getSparql();

    return $sparql->queryConceptInfo($uri, $this->getArrayClassURI(), array($this), null, $clang);
  }

  /**
   * Lists the different concept groups available in the vocabulary.
   * @param boolean $flat set to true if you want a non hierarchical representation. 
   * @param string $clang content language parameter 
   * @return array
   */
  public function listConceptGroups($flat=false, $clang=null)
  {
    if (!$clang)
      $clang = $this->lang;
    $ret = array();
    $gclass = $this->getGroupClassURI();
    if ($gclass === null) return $ret; // no group class defined, so empty result
    $groups = $this->getSparql()->listConceptGroups($gclass, $clang, $flat);
    foreach ($groups as $uri => $label) {
      $ret[$uri] = $label;
    }

    return $ret;
  }
 
  /**
   * Returns the group name from the available concept groups. If the group has
   * isothes:superGroups those are also added to the name string. 
   * @param string $groupuri uri of the group. 
   * @return string 
   */
  public function getGroupName($groupuri)
  {
    $groups = $this->listConceptGroups(true);
    if (sizeof($groups) === 0)
      return '';
    $current = $groups[$groupuri];
    $ret = $current['label'];
    while(isset($current['super'])) {
      $current = $groups[$current['super']];
      $ret = $current['label'] . ' > ' . $ret;
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
    if (!$clang)
      $clang = $this->lang;
    $ret = array();
    $gclass = $this->getGroupClassURI();
    if ($gclass === null) return $ret; // no group class defined, so empty result
    $group = $this->getConceptURI($glname);
    $contents = $this->getSparql()->listConceptGroupContents($gclass, $group, $clang);
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
  public function getAlphabet($clang) {
    $chars = $this->getSparql()->queryFirstCharacters($clang, $this->getIndexClasses());
    $letters = array();
    $digits = false;
    $specials = false;
    foreach($chars as $char) {
      if (preg_match('/\p{L}/u', $char)) {
        $letters[] = $char;
      } elseif (preg_match('/\d/u', $char)) {
        $digits = true;
      } else {
        $specials = true;
      }
    }
    usort($letters, 'strcoll');
    if ($specials)
      $letters[] = '!*';
    if ($digits)
      $letters[] = '0-9';
    return $letters;
  }
  
  /**
   * Searches for concepts with a label starting with the specified letter.
   * Also the special tokens '0-9' (digits), '!*' (special characters) and '*'
   * (everything) are supported.
   * @param $letter letter (or special token) to search for
   */
  public function searchConceptsAlphabetical($letter, $limit=null, $offset=null, $clang=null) {
    return $this->getSparql()->queryConceptsAlphabetical($letter, $clang, $limit, $offset, $this->getIndexClasses());
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
   * @param array $bT contains the results of the broaderTransitive query.
   * @param string $uri
   * @param array $path
   */
  private function getCrumbs($bT, $uri, $path=null)
  {
    $crumbs = array();
    if(!isset($path))
      $path = array();
    // check that there is no cycle (issue #220)
    foreach ($path as $childcrumb) {
      if ($childcrumb->getUri() == $uri) {
        // found a cycle - short-circuit and stop
        return $crumbs;
      }
    }
    if (isset($bT[$uri]['direct'])) {
      foreach ($bT[$uri]['direct'] as $broaderUri) {
        $newpath = array_merge($path, array(new Breadcrumb($uri, $bT[$uri]['label'])));
        if ($uri !== $broaderUri) {
          $crumbs = array_merge($crumbs, $this->getCrumbs($bT, $broaderUri, $newpath));
        }
      }
    } else { // we have reached the end of a path and we need to start a new row in the 'stack'
      if (isset($bT[$uri]))
        $path = array_merge($path, array(new Breadcrumb($uri, $bT[$uri]['label'])));
      $index = 1;
      $length = sizeof($path);
      $limit = $length - 5;
      foreach ($path as $crumb) {
        if ($length > 5 && $index > $length-$limit) { // displays 5 concepts closest to the concept.
          $crumb->hideLabel();
        }
        $index++;
      }
      $crumbs[] = array_reverse($path);
    }
    return $crumbs;
  }

}
