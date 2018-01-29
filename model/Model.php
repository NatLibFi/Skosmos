<?php

/**
 * Setting some often needed namespace prefixes
 */
EasyRdf\RdfNamespace::set('skosmos', 'http://purl.org/net/skosmos#');
EasyRdf\RdfNamespace::set('skosext', 'http://purl.org/finnonto/schema/skosext#');
EasyRdf\RdfNamespace::set('isothes', 'http://purl.org/iso25964/skos-thes#');
EasyRdf\RdfNamespace::set('mads', 'http://www.loc.gov/mads/rdf/v1#');
EasyRdf\RdfNamespace::set('wd', 'http://www.wikidata.org/entity/');
EasyRdf\RdfNamespace::set('wdt', 'http://www.wikidata.org/prop/direct/');

/**
 * Model provides access to the data.
 * @property EasyRdf\Graph $graph
 * @property GlobalConfig $globalConfig
 */
class Model
{
    /** EasyRdf\Graph graph instance */
    private $graph;
    /** Namespaces from vocabularies configuration file */
    private $namespaces;
    /** cache for Vocabulary objects */
    private $allVocabularies = null;
    /** cache for Vocabulary objects */
    private $vocabsByGraph = null;
    /** cache for Vocabulary objects */
    private $vocabsByUriSpace = null;
    /** how long to store retrieved URI information in APC cache */
    const URI_FETCH_TTL = 86400; // 1 day
    private $globalConfig;
    private $cache;
    private $logger;

    /**
     * Initializes the Model object
     */
    public function __construct($config)
    {
        $this->globalConfig = $config;
        try {
          $this->cache = new Cache();
          $this->initializeVocabularies();
          $this->initializeNamespaces();
          $this->initializeLogging();
        } catch (Exception $e) {
            header("HTTP/1.0 404 Not Found");
            echo("Error: Vocabularies configuration file 'vocabularies.ttl' not found.");
            return;
        }
    }

    /**
     * Returns the GlobalConfig object given to the Model as a constructor parameter.
     * @return GlobalConfig
     */
    public function getConfig() {
      return $this->globalConfig;
    }

    /**
     * Initializes the configuration from the vocabularies.ttl file
     */
    private function initializeVocabularies()
    {
        if (!file_exists($this->getConfig()->getVocabularyConfigFile())) {
            throw new Exception($this->getConfig()->getVocabularyConfigFile() . ' is missing, please provide one.');
        }

        try {
            // use APC user cache to store parsed vocabularies.ttl configuration
            if ($this->cache->isAvailable()) {
                // @codeCoverageIgnoreStart
                $key = realpath($this->getConfig()->getVocabularyConfigFile()) . ", " . filemtime($this->getConfig()->getVocabularyConfigFile());
                $nskey = "namespaces of " . $key;
                $this->graph = $this->cache->fetch($key);
                $this->namespaces = $this->cache->fetch($nskey);
                if ($this->graph === false || $this->namespaces === false) { // was not found in cache
                    $this->parseVocabularies($this->getConfig()->getVocabularyConfigFile());
                    $this->cache->store($key, $this->graph);
                    $this->cache->store($nskey, $this->namespaces);
                }
                // @codeCoverageIgnoreEnd
            } else { // APC not available, parse on every request
                $this->parseVocabularies($this->getConfig()->getVocabularyConfigFile());
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    /**
     * Parses vocabulary configuration and RDF namespaces from the vocabularies.ttl file
     * @param string $filename path to vocabularies.ttl file
     */

    private function parseVocabularies($filename)
    {
        $this->graph = new EasyRdf\Graph();
        $parser = new SkosmosTurtleParser();
        $parser->parse($this->graph, file_get_contents($filename), 'turtle', $filename);
        $this->namespaces = $parser->getNamespaces();
    }

    /**
     * Registers RDF namespaces from the vocabularies.ttl file for use by EasyRdf (e.g. serializing)
     */

    private function initializeNamespaces() {
        foreach ($this->namespaces as $prefix => $fullUri) {
            if ($prefix != '' && EasyRdf\RdfNamespace::get($prefix) === null) // if not already defined
            {
                EasyRdf\RdfNamespace::set($prefix, $fullUri);
            }
        }
    }

    /**
     * Configures the logging facility
     */
    private function initializeLogging() {
        $this->logger = new \Monolog\Logger('general');
        $formatter = new \Monolog\Formatter\LineFormatter("[%datetime%] %level_name% %message%\n");
        $formatter->allowInlineLineBreaks(true);
        if ($this->getConfig()->getLoggingBrowserConsole()) {
            $browserHandler = new \Monolog\Handler\BrowserConsoleHandler(\Monolog\Logger::INFO);
            $browserHandler->setFormatter($formatter);
            $this->logger->pushHandler($browserHandler);
        }
        if ($this->getConfig()->getLoggingFilename() !== null) {
            $streamHandler = new \Monolog\Handler\StreamHandler($this->getConfig()->getLoggingFilename(), \Monolog\Logger::INFO);
            $streamHandler->setFormatter($formatter);
            $this->logger->pushHandler($streamHandler);
        }
        if (!$this->logger->getHandlers()) {
            // add a NullHandler to suppress the default Monolog logging to stderr
            $nullHandler = new \Monolog\Handler\NullHandler();
            $this->logger->pushHandler($nullHandler);
        }
    }

    /**
     * Return the logging facility
     * @return object logger
     */
    public function getLogger() {
        return $this->logger;
    }

    /**
     * Return the version of this Skosmos installation, or "unknown" if
     * it cannot be determined. The version information is based on Git tags.
     * @return string version
     */
    public function getVersion()
    {
        $ver = null;
        if (file_exists('.git')) {
            $ver = shell_exec('git describe --tags');
        }

        if ($ver === null) {
            return "unknown";
        }

        return $ver;
    }

    /**
     * Return all the vocabularies available.
     * @param boolean $categories whether you want everything included in a subarray of
     * a category.
     * @param boolean $shortname set to true if you want the vocabularies sorted by
     * their shortnames instead of ther titles.
     */
    public function getVocabularyList($categories = true, $shortname = false)
    {
        $cats = $this->getVocabularyCategories();
        $ret = array();
        foreach ($cats as $cat) {
            $catlabel = $cat->getTitle();

            // find all the vocabs in this category
            $vocs = array();
            foreach ($cat->getVocabularies() as $voc) {
                $vocs[$shortname ? $voc->getConfig()->getShortname() : $voc->getConfig()->getTitle()] = $voc;
            }
            uksort($vocs, 'strcoll');

            if (sizeof($vocs) > 0 && $categories) {
                $ret[$catlabel] = $vocs;
            } elseif (sizeof($vocs) > 0) {
                $ret = array_merge($ret, $vocs);
            }

        }

        if (!$categories) {
            uksort($ret, 'strcoll');
        }

        return $ret;
    }

    /**
     * Return all types (RDFS/OWL classes) present in the specified vocabulary or all vocabularies.
     * @return array Array with URIs (string) as key and array of (label, superclassURI) as value
     */
    public function getTypes($vocid = null, $lang = null)
    {
        $sparql = (isset($vocid)) ? $this->getVocabulary($vocid)->getSparql() : $this->getDefaultSparql();
        $result = $sparql->queryTypes($lang);

        foreach ($result as $uri => $values) {
            if (empty($values)) {
                $shorteneduri = EasyRdf\RdfNamespace::shorten($uri);
                if ($shorteneduri !== null) {
                    $trans = gettext($shorteneduri);
                    if ($trans) {
                        $result[$uri] = array('label' => $trans);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Return the languages present in the configured vocabularies.
     * @return array Array with lang codes (string)
     */
    public function getLanguages($lang)
    {
        $vocabs = $this->getVocabularyList(false);
        $ret = array();
        foreach ($vocabs as $vocab) {
            foreach ($vocab->getConfig()->getLanguages() as $langcode) {
                $langlit = Punic\Language::getName($langcode, $lang);
                $ret[$langlit] = $langcode;
            }
        }
        ksort($ret);
        return array_unique($ret);
    }

    /**
     * returns a concept's RDF data in downloadable format
     * @param string $vocid vocabulary id, or null for global data (from all vocabularies)
     * @param string $uri concept URI
     * @param string $format the format in which you want to get the result, currently this function supports
     * text/turtle, application/rdf+xml and application/json
     * @return string RDF data in the requested serialization
     */
    public function getRDF($vocid, $uri, $format)
    {

        if ($format == 'text/turtle') {
            $retform = 'turtle';
            $serialiser = new EasyRdf\Serialiser\Turtle();
        } elseif ($format == 'application/ld+json' || $format == 'application/json') {
            $retform = 'jsonld'; // serve JSON-LD for both JSON-LD and plain JSON requests
            $serialiser = new EasyRdf\Serialiser\JsonLd();
        } else {
            $retform = 'rdfxml';
            $serialiser = new EasyRdf\Serialiser\RdfXml();
        }

        if ($vocid !== null) {
            $vocab = $this->getVocabulary($vocid);
            $sparql = $vocab->getSparql();
            $arrayClass = $vocab->getConfig()->getArrayClassURI();
            $vocabs = array($vocab);
        } else {
            $sparql = $this->getDefaultSparql();
            $arrayClass = null;
            $vocabs = null;
        }
        $result = $sparql->queryConceptInfoGraph($uri, $arrayClass, $vocabs);

        if (!$result->isEmpty()) {
            return $serialiser->serialise($result, $retform);
        }
    }

    /**
     * Makes a SPARQL-query to the endpoint that retrieves concept
     * references as it's search results.
     * @param ConceptSearchParameters $params an object that contains all the parameters needed for the search
     * @return array search results
     */
    public function searchConcepts($params)
    {
        // don't even try to search for empty prefix if no other search criteria (group or parent concept) has been set
        if (($params->getSearchTerm() === "" || !preg_match('/[^*]/', $params->getSearchTerm())) && !$params->getGroupLimit() && !$params->getParentLimit()) {
            return array();
        }

        $vocabs = $params->getVocabs();
        $showDeprecated=false;
        if (sizeof($vocabs) === 1) { // search within vocabulary
            $voc = $vocabs[0];
            $sparql = $voc->getSparql();
            $showDeprecated=$voc->getConfig()->getShowDeprecated();
        } else { // multi-vocabulary or global search
            $voc = null;
            $sparql = $this->getDefaultSparql();
            // @TODO : in a global search showDeprecated will always be false and cannot be set globally
        }

        $results = $sparql->queryConcepts($vocabs, $params->getAdditionalFields(), $params->getUnique(), $params,$showDeprecated);
        if ($params->getRest() && $results && $params->getSearchLimit() !== 0) {
          $results = array_slice($results, $params->getOffset(), $params->getSearchLimit());
        }
        $ret = array();

        foreach ($results as $hit) {
            if (sizeof($vocabs) == 1) {
                $hit['vocab'] = $vocabs[0]->getId();
            } else {
                try {
                    $voc = $this->getVocabularyByGraph($hit['graph']);
                    $hit['vocab'] = $voc->getId();
                } catch (Exception $e) {
                    trigger_error($e->getMessage(), E_USER_WARNING);
                    $voc = null;
                    $hit['vocab'] = "???";
                }
            }
            unset($hit['graph']);

            $hit['voc'] = $voc;

            // if uri is a external vocab uri that is included in the current vocab
            $realvoc = $this->guessVocabularyFromURI($hit['uri']);
            if ($realvoc != $voc) {
                unset($hit['localname']);
                $hit['exvocab'] = ($realvoc !== null) ? $realvoc->getId() : "???";
            }

            $ret[] = $hit;
        }

        return $ret;
    }

    /**
     * Function for performing a search for concepts and their data fields.
     * @param ConceptSearchParameters $params an object that contains all the parameters needed for the search
     * @return array array with keys 'count' and 'results'
     */
    public function searchConceptsAndInfo($params)
    {
        $params->setUnique(true);
        $allhits = $this->searchConcepts($params);
        $count = sizeof($allhits);
        $hits = array_slice($allhits, $params->getOffset(), $params->getSearchLimit());

        $uris = array();
        $vocabs = array();
        $uniqueVocabs = array();
        foreach ($hits as $hit) {
            $uniqueVocabs[$hit['voc']->getId()] = $hit['voc']->getId();
            $vocabs[] = $hit['voc'];
            $uris[] = $hit['uri'];
        }
        if (sizeof($uniqueVocabs) == 1) {
            $voc = $vocabs[0];
            $sparql = $voc->getSparql();
            $arrayClass = $voc->getConfig()->getArrayClassURI();
        } else {
            $arrayClass = null;
            $sparql = $this->getDefaultSparql();
        }
        $ret = $sparql->queryConceptInfo($uris, $arrayClass, $vocabs, $params->getSearchLang());

        // For marking that the concept has been found through an alternative label, hidden
        // label or a label in another language
        foreach ($hits as $idx => $hit) {
            if (isset($hit['altLabel']) && isset($ret[$idx])) {
                $ret[$idx]->setFoundBy($hit['altLabel'], 'alt');
            }

            if (isset($hit['hiddenLabel']) && isset($ret[$idx])) {
                $ret[$idx]->setFoundBy($hit['hiddenLabel'], 'hidden');
            }

            if (isset($hit['matchedPrefLabel'])) {
                $ret[$idx]->setFoundBy($hit['matchedPrefLabel'], 'lang');
            }

            if ($ret[$idx] && isset($hit['lang'])) {
                $ret[$idx]->setContentLang($hit['lang']);
            }
        }

        return array('count' => $count, 'results' => $ret);
    }

    /**
     * Creates dataobjects from an input array.
     * @param string $class the type of class eg. 'Vocabulary'.
     * @param array $resarr contains the EasyRdf\Resources.
     */
    private function createDataObjects($class, $resarr)
    {
        $ret = array();
        foreach ($resarr as $res) {
            $ret[] = new $class($this, $res);
        }

        return $ret;
    }

    /**
     * Returns the cached vocabularies.
     * @return array of Vocabulary dataobjects
     */
    public function getVocabularies()
    {
        if ($this->allVocabularies === null) { // initialize cache
            $vocs = $this->graph->allOfType('skosmos:Vocabulary');
            $this->allVocabularies = $this->createDataObjects("Vocabulary", $vocs);
            foreach ($this->allVocabularies as $voc) {
                // register vocabulary ids as RDF namespace prefixes
                $prefix = preg_replace('/\W+/', '', $voc->getId()); // strip non-word characters
                try {
                    if ($prefix != '' && EasyRdf\RdfNamespace::get($prefix) === null) // if not already defined
                    {
                        EasyRdf\RdfNamespace::set($prefix, $voc->getUriSpace());
                    }

                } catch (Exception $e) {
                    // not valid as namespace identifier, ignore
                }
            }
        }

        return $this->allVocabularies;
    }

    /**
     * Returns the cached vocabularies from a category.
     * @param EasyRdf\Resource $cat the category in question
     * @return array of vocabulary dataobjects
     */
    public function getVocabulariesInCategory($cat)
    {
        $vocs = $this->graph->resourcesMatching('dc:subject', $cat);

        return $this->createDataObjects("Vocabulary", $vocs);
    }

    /**
     * Creates dataobjects of all the different vocabulary categories (Health etc.).
     * @return array of Dataobjects of the type VocabularyCategory.
     */
    public function getVocabularyCategories()
    {
        $cats = $this->graph->allOfType('skos:Concept');
        if(empty($cats)) {
            return array(new VocabularyCategory($this, null));
        }

        return $this->createDataObjects("VocabularyCategory", $cats);
    }

    /**
     * Returns the label defined in vocabularies.ttl with the appropriate language.
     * @param string $lang language code of returned labels, eg. 'fi'
     * @return string the label for vocabulary categories.
     */
    public function getClassificationLabel($lang)
    {
        $cats = $this->graph->allOfType('skos:ConceptScheme');
        $label = $cats ? $cats[0]->label($lang) : null;

        return $label;
    }

    /**
     * Returns a single cached vocabulary.
     * @param string $vocid the vocabulary id eg. 'mesh'.
     * @return vocabulary dataobject
     */
    public function getVocabulary($vocid)
    {
        $vocs = $this->getVocabularies();
        foreach ($vocs as $voc) {
            if ($voc->getId() == $vocid) {
                return $voc;
            }
        }
        throw new Exception("Vocabulary id '$vocid' not found in configuration.");
    }

    /**
     * Return the vocabulary that is stored in the given graph on the given endpoint.
     *
     * @param $graph string graph URI
     * @param $endpoint string endpoint URL (default SPARQL endpoint if omitted)
     * @return Vocabulary vocabulary of this URI, or null if not found
     */
    public function getVocabularyByGraph($graph, $endpoint = null)
    {
        if ($endpoint === null) {
            $endpoint = $this->getConfig()->getDefaultEndpoint();
        }
        if ($this->vocabsByGraph === null) { // initialize cache
            $this->vocabsByGraph = array();
            foreach ($this->getVocabularies() as $voc) {
                $key = json_encode(array($voc->getGraph(), $voc->getEndpoint()));
                $this->vocabsByGraph[$key] = $voc;
            }
        }

        $key = json_encode(array($graph, $endpoint));
        if (array_key_exists($key, $this->vocabsByGraph)) {
            return $this->vocabsByGraph[$key];
        } else {
            throw new Exception("no vocabulary found for graph $graph and endpoint $endpoint");
        }

    }

    /**
     * When multiple vocabularies share same URI namespace, return the
     * vocabulary in which the URI is actually defined (has a label).
     *
     * @param Vocabulary[] $vocabs vocabularies to search
     * @param string $uri URI to look for
     * @param $preferredVocabId string ID of the preferred vocabulary to return if more than one is found
     * @return Vocabulary the vocabulary with the URI
     */

    private function disambiguateVocabulary($vocabs, $uri, $preferredVocabId = null)
    {
        // if there is only one candidate vocabulary, return it
        if (sizeof($vocabs) == 1) {
            return $vocabs[0];
        }

        // if there are multiple vocabularies and one is the preferred vocabulary, return it
        if($preferredVocabId != null) {
            foreach ($vocabs as $vocab) {
                if($vocab->getId() == $preferredVocabId) {
                    return $vocab;
                }
            }
        }

        // no preferred vocabulary, or it was not found, search in which vocabulary the concept has a label
        foreach ($vocabs as $vocab) {
            if ($vocab->getConceptLabel($uri, null) !== null)
                return $vocab;
        }

        // if the URI couldn't be found, fall back to the first vocabulary
        return $vocabs[0];
    }

    /**
     * Guess which vocabulary a URI originates from, based on the declared
     * vocabulary URI spaces.
     *
     * @param $uri string URI to search
     * @param $preferredVocabId string ID of the preferred vocabulary to return if more than one is found
     * @return Vocabulary vocabulary of this URI, or null if not found
     */
    public function guessVocabularyFromURI($uri, $preferredVocabId = null)
    {
        if ($this->vocabsByUriSpace === null) { // initialize cache
            $this->vocabsByUriSpace = array();
            foreach ($this->getVocabularies() as $voc) {
                $this->vocabsByUriSpace[$voc->getUriSpace()][] = $voc;
            }
        }

        // try to guess the URI space and look it up in the cache
        $res = new EasyRdf\Resource($uri);
        $namespace = substr($uri, 0, -strlen($res->localName()));
        if (array_key_exists($namespace, $this->vocabsByUriSpace)) {
            $vocabs = $this->vocabsByUriSpace[$namespace];
            return $this->disambiguateVocabulary($vocabs, $uri, $preferredVocabId);
        }

        // didn't work, try to match with each URI space separately
        foreach ($this->vocabsByUriSpace as $urispace => $vocabs) {
            if (strpos($uri, $urispace) === 0) {
                return $this->disambiguateVocabulary($vocabs, $uri, $preferredVocabId);
            }
        }

        // not found
        return null;
    }

    /**
     * Get the label for a resource, preferring 1. the given language 2. configured languages 3. any language.
     * @param EasyRdf\Resource $res resource whose label to return
     * @param string $lang preferred language
     * @return EasyRdf\Literal label as an EasyRdf\Literal object, or null if not found
     */
    public function getResourceLabel($res, $lang)
    {
        $langs = array_merge(array($lang), array_keys($this->getConfig()->getLanguages()));
        foreach ($langs as $l) {
            $label = $res->label($l);
            if ($label !== null) {
                return $label;
            }

        }
        return $res->label(); // desperate check for label in any language; will return null if even this fails
    }

    private function fetchResourceFromUri($uri)
    {
        try {
            // change the timeout setting for external requests
            $httpclient = EasyRdf\Http::getDefaultHttpClient();
            $httpclient->setConfig(array('timeout' => $this->getConfig()->getHttpTimeout()));
            EasyRdf\Http::setDefaultHttpClient($httpclient);

            $client = EasyRdf\Graph::newAndLoad(EasyRdf\Utils::removeFragmentFromUri($uri));
            return $client->resource($uri);
        } catch (Exception $e) {
            return null;
        }
    }

    public function getResourceFromUri($uri)
    {
        // prevent parsing errors for sources which return invalid JSON (see #447)
        // 1. Unregister the legacy RDF/JSON parser, we don't want to use it
        EasyRdf\Format::unregister('json');
        // 2. Add "application/json" as a possible MIME type for the JSON-LD format
        $jsonld = EasyRdf\Format::getFormat('jsonld');
        $mimetypes = $jsonld->getMimeTypes();
        $mimetypes['application/json'] = 0.5;
        $jsonld->setMimeTypes($mimetypes);

        // using apc cache for the resource if available
        if ($this->cache->isAvailable()) {
            // @codeCoverageIgnoreStart
            $key = 'fetch: ' . $uri;
            $resource = $this->cache->fetch($key);
            if ($resource === null || $resource === false) { // was not found in cache, or previous request failed
                $resource = $this->fetchResourceFromUri($uri);
                $this->cache->store($key, $resource, self::URI_FETCH_TTL);
            }
            // @codeCoverageIgnoreEnd
        } else { // APC not available, parse on every request
            $resource = $this->fetchResourceFromUri($uri);
        }
        return $resource;
    }

    /**
     * Returns a SPARQL endpoint object.
     * @param string $dialect eg. 'JenaText'.
     * @param string $endpoint url address of endpoint
     * @param string $graph uri for the target graph.
     */
    public function getSparqlImplementation($dialect, $endpoint, $graph)
    {
        $classname = $dialect . "Sparql";

        return new $classname($endpoint, $graph, $this);
    }

    /**
     * Returns a SPARQL endpoint object using the default implementation set in the config.inc.
     */
    public function getDefaultSparql()
    {
        return $this->getSparqlImplementation($this->getConfig()->getDefaultSparqlDialect(), $this->getConfig()->getDefaultEndpoint(), '?graph');
    }

}
