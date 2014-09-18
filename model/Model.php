<?php
/**
 * Copyright (c) 2012 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/**
 * Setting some often needed namespace prefixes
 */
EasyRdf_Namespace::set('skosmos', 'http://purl.org/net/skosmos#');
EasyRdf_Namespace::set('void', 'http://rdfs.org/ns/void#');
EasyRdf_Namespace::set('skosext', 'http://purl.org/finnonto/schema/skosext#');
EasyRdf_Namespace::set('isothes', 'http://purl.org/iso25964/skos-thes#');

require_once 'model/DataObject.php';
require_once 'model/VocabularyDataObject.php';
require_once 'model/Concept.php';
require_once 'model/VocabularyCategory.php';
require_once 'model/Vocabulary.php';
require_once 'model/Breadcrumb.php';

require_once 'model/sparql/GenericSparql.php';
require_once 'model/sparql/BigdataSparql.php';
require_once 'model/sparql/JenaTextSparql.php';

/**
 * Model provides access to the data.
 * @property EasyRdf_Graph $graph
 */
class Model
{
  /** EasyRdf_Graph graph instance */
  private $graph;
  /** cache for Vocabulary objects */
  private $all_vocabularies = null;
  /** cache for Vocabulary objects */
  private $vocabularies_by_graph = null;
  /** cache for Vocabulary objects */
  private $vocabularies_by_urispace = null;
  /** stores the breadcrumbs */
  private $crumbs;

  /**
   * Initializes the object with the configuration from the vocabularies.ttl
   */
  public function __construct()
  {
    if (!file_exists(VOCABULARIES_FILE))
      throw new Exception(VOCABULARIES_FILE . ' is missing, please provide one.');
    try {
      // use APC user cache to store parsed vocabularies.ttl configuration
      if (function_exists('apc_store') && function_exists('apc_fetch')) {
        $key = realpath(VOCABULARIES_FILE) . ", " . filemtime(VOCABULARIES_FILE);
        $this->graph = apc_fetch($key);
        if ($this->graph === FALSE) { // was not found in cache
          $this->graph = new EasyRdf_Graph();
          $this->graph->parse(file_get_contents(VOCABULARIES_FILE));
          apc_store($key, $this->graph);
        }
      } else { // APC not available, parse on every request
          $this->graph = new EasyRdf_Graph();
          $this->graph->parse(file_get_contents(VOCABULARIES_FILE));
      }
    } catch (Exception $e) {
      echo "Error: " . $e->getMessage();
    }
  }

  /**
   * Return all the vocabularies available.
   * @param boolean $categories wheter you want everything included in a subarray of
   * a category.
   */
  public function getVocabularyList($categories = true)
  {
    $cats = $this->getVocabularyCategories();
    $ret = array();
    foreach ($cats as $cat) {
      $catlabel = $cat->getTitle();

      // find all the vocabs in this category
      $vocs = array();
      foreach ($cat->getVocabularies() as $voc) {
        $vocs[$voc->getTitle()] = $voc;
      }
      uksort($vocs, 'strcoll');

      if (sizeof($vocs) > 0 && $categories)
        $ret[$catlabel] = $vocs;
      elseif (sizeof($vocs) > 0)
        $ret = array_merge($ret, $vocs);
    }

    if (!$categories)
      uksort($ret, 'strcoll');
    return $ret;
  }

  /**
   * Makes a query for the transitive broaders of a concept and returns the concepts hierarchy processed for the view.
   * @param string $vocab
   * @param string $lang
   * @param string $uri
   */
  public function getBreadCrumbs($vocab, $lang, $uri)
  {
    $broaders = $vocab->getConceptTransitiveBroaders($uri, 1000, true);
    $this->getCrumbs($broaders, $uri);
    $crumbs['combined'] = $this->combineCrumbs();
    $crumbs['breadcrumbs'] = $this->crumbs;

    return $crumbs;
  }

  /**
   * Takes the crumbs as a parameter and combines the crumbs if the path they form is too long.
   * @return array
   */
  public function combineCrumbs()
  {
    $combined = array();
    foreach ($this->crumbs as $pathKey => $path) {
      $firstToCombine = true;
      $combinedPath = array();
      foreach ($path as $crumbKey => $crumb) {
        if ($crumb->getPrefLabel() === '...') {
          array_push($combinedPath, $crumb);
          if ($firstToCombine) {
            $firstToCombine = false;
          } else {
            unset($this->crumbs[$pathKey][$crumbKey]);
          }
        }
      }
      $combined[] = $combinedPath;
    }

    return $combined;
  }

  /**
   * Recursive function for building the breadcrumb paths for the view.
   * @param array $bT contains the results of the broaderTransitive query.
   * @param string $uri
   * @param array $path
   */
  public function getCrumbs($bT, $uri, $path=null)
  {
    if(!isset($path))
      $path = array();
    if (isset($bT[$uri]['direct'])) {
      foreach ($bT[$uri]['direct'] as $broaderUri) {
        $newpath = array_merge($path, array(new Breadcrumb($uri, $bT[$uri]['label'])));
        if ($uri !== $broaderUri)
          $this->getCrumbs($bT, $broaderUri, $newpath);
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
      $this->crumbs[] = array_reverse($path);
    }
  }

  /**
   * Return all types (RDFS/OWL classes) present in the specified vocabulary or all vocabularies.
   * @return array Array with URIs (string) as key and array of (label, superclassURI) as value
   */
  public function getTypes($vocid=null, $lang=null)
  {
    $sparql = (isset($vocid)) ? $this->getVocabulary($vocid)->getSparql() : $this->getDefaultSparql();
    $result = (isset($lang)) ? $sparql->queryTypes($lang) : $sparql->queryTypes($_GET['lang']);

    foreach ($result as $uri => $values)
      if(empty($values)) {
        $shorteneduri = EasyRdf_Namespace::shorten($uri);
        if ($shorteneduri)
          $trans = gettext($shorteneduri);
        if ($trans) {
          $result[$uri] = array('label' => $trans);
        }
      }
    return $result;
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
      $serialiser = new EasyRdf_Serialiser_Turtle();
    } elseif ($format == 'application/ld+json' || $format == 'application/json') {
      $retform = 'jsonld'; // serve JSON-LD for both JSON-LD and plain JSON requests
      $serialiser = new EasyRdf_Serialiser_JsonLd();
    } else {
      $retform = 'rdfxml';
      $serialiser = new EasyRdf_Serialiser_RdfXml();
    }

    if ($vocid !== null) {
      $vocab = $this->getVocabulary($vocid);
      $sparql = $vocab->getSparql();
      $arrayClass = $vocab->getArrayClassURI();
      $vocabs = array($vocab);
    } else {
      $sparql = $this->getDefaultSparql();
      $arrayClass = null;
      $vocabs = null;
    }
    $result = $sparql->queryConceptInfo($uri, $arrayClass, $vocabs, true);

    return $serialiser->serialise($result, $retform);
  }



  /**
   * Makes a SPARQL-query to the endpoint that retrieves concept
   * references as it's search results.
   * @param string $term the term that is looked for eg. 'cat'.
   * @param mixed $vocids vocabulary id eg. 'yso', array of such ids for multi-vocabulary search, or null for global search.
   * @param string $lang language code to show labels in, eg. 'fi' for Finnish.
   * @param string $search_lang language code used for matching, eg. 'fi' for Finnish, null for any language
   * @param string $type limit search to concepts of the given type
   * @param string $parent limit search to concepts which have the given concept as parent in the transitive broader hierarchy
   * @param string $group limit search to concepts which are in the given group
   * @param int $offset optional parameter for search offset.
   * @param int $limit optional paramater for maximum amount of results.
   * @param boolean $hidden include matches on hidden labels (default: true).
   */
  public function searchConcepts($term, $vocids, $lang, $search_lang, $type = null, $parent=null, $group=null, $offset = 0, $limit = DEFAULT_SEARCH_LIMIT, $hidden = true)
  {
    $term = trim($term);
    if ($term == "" || $term == "*")
      return array(); // don't even try to search for empty prefix

    // make vocids an array in every case
    if ($vocids === null) $vocids = array();
    if (!is_array($vocids)) $vocids = array($vocids);
    $vocabs = array();
    foreach ($vocids as $vocid)
      $vocabs[] = $this->getVocabulary($vocid);

    if (sizeof($vocids) == 1) { // search within vocabulary
      $voc = $vocabs[0];
      $sparql = $voc->getSparql();
      $arrayClass = $voc->getArrayClassURI();
    } else { // multi-vocabulary or global search
      $voc = null;
      $arrayClass = null;
      $sparql = $this->getDefaultSparql();
    }
    if (!$type) $type = 'skos:Concept';

    $results = $sparql->queryConcepts($term, $vocabs, $lang, $search_lang, $limit, $offset, $arrayClass, $type, $parent, $group, $hidden);
    $ret = array();

    foreach ($results as $hit) {
      if (sizeof($vocids) == 1) {
        $hit['vocab'] = $vocids[0];
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
        $hit['exvocab'] = $realvoc != null ? $realvoc->getId() : "???";
      }

      $ret[] = $hit;
    }

    return $ret;
  }

  /**
   * Function for performing a search for concepts and their data fields.
   * @param string $term searchterm eg. 'cat'
   * @param mixed $vocids vocabulary id eg. 'yso', array of such ids for multi-vocabulary search, or null for global search.
   * @param string $lang language code of returned labels, eg. 'fi'
   * @param string $search_lang language code used for matching, eg. 'fi', or null for anything
   * @param integer $offset used for offsetting the result set eg. '20'
   * @param integer $limit upper count for the search results eg. '10'
   * @param string $type limit search to concepts of the given type
   * @param string $parent limit search to concepts which have the given concept as parent in the transitive broader hierarchy
   * @param string $group limit search to concepts which are in the given group
   * @return array array with keys 'count' and 'results'
   */
  public function searchConceptsAndInfo($term, $vocids, $lang, $search_lang, $offset = 0, $limit = 20, $type = null, $parent = null, $group = null)
  {
    // make vocids an array in every case
    if ($vocids === null) $vocids = array();
    if (!is_array($vocids)) $vocids = array($vocids);

    $allhits = $this->searchConcepts($term, $vocids, $lang, $search_lang, $type, $parent, $group, 0, 0);
    $hits = array_slice($allhits, $offset, $limit);

    $uris = array();
    $vocabs = array();
    if ($vocids != null) {
      foreach ($vocids as $vocid) {
        $vocabs[] = $this->getVocabulary($vocid);
      }
    }
    foreach ($hits as $hit)
      $uris[] = $hit['uri'];
    if (sizeof($vocabs) == 1) {
      $voc = $vocabs[0];
      $sparql = $voc->getSparql();
      $arrayClass = $voc->getArrayClassURI();
    } else {
      $arrayClass = null;
      $sparql = $this->getDefaultSparql();
    }
    $ret = $sparql->queryConceptInfo($uris, $arrayClass, $vocabs);

    // For marking that the concept has been found through an alternative label, hidden
    // label or a label in another language
    foreach ($hits as $idx => $hit) {
      $lang_suffix = (isset($hit['lang']) && $hit['lang'] !== $lang) ? ' (' . $hit['lang'] . ')' : '';
      if (isset($hit['altLabel']) && isset($ret[$idx]))
        $ret[$idx]->setFoundBy($hit['altLabel'] . $lang_suffix, 'alt');
      if (isset($hit['hiddenLabel']) && isset($ret[$idx]))
        $ret[$idx]->setFoundBy($hit['hiddenLabel'] . $lang_suffix, 'hidden');
      if (isset($hit['matchedPrefLabel']))
        $ret[$idx]->setFoundBy($hit['matchedPrefLabel'] . $lang_suffix, 'lang');
    }

    return array('count' => sizeof($allhits), 'results' => $ret);
  }

  /**
   * Creates dataobjects from an input array.
   * @param string $class the type of class eg. 'Vocabulary'.
   * @param array $resarr contains the EasyRdf_Resources.
   */
  private function createDataObjects($class, $resarr)
  {
    $ret = array();
    foreach ($resarr as $res)
      $ret[] = new $class($this, $res);

    return $ret;
  }

  /**
   * Returns the cached vocabularies.
   * @return array of Vocabulary dataobjects
   */
  public function getVocabularies()
  {
    if ($this->all_vocabularies == null) { // initialize cache
      $vocs = $this->graph->allOfType('skosmos:Vocabulary');
      $this->all_vocabularies = $this->createDataObjects("Vocabulary", $vocs);
      foreach ($this->all_vocabularies as $voc) {
        // register vocabulary ids as RDF namespace prefixes
        $prefix = preg_replace('/\W+/', '', $voc->getId()); // strip non-word characters
        try {
          if ($prefix != '' && EasyRdf_Namespace::get($prefix) === null) // if not already defined
            EasyRdf_Namespace::set($prefix, $voc->getUriSpace());
        } catch (Exception $e) {
          // not valid as namespace identifier, ignore
        }
      }
    }

    return $this->all_vocabularies;
  }

  /**
   * Returns the cached vocabularies from a category.
   * @param EasyRdf_Resource $cat the category in question
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

    return $this->createDataObjects("VocabularyCategory", $cats);
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
  public function getVocabularyByGraph($graph, $endpoint = DEFAULT_ENDPOINT)
  {
    if ($this->vocabularies_by_graph == null) { // initialize cache
      $this->vocabularies_by_graph = array();
      foreach ($this->getVocabularies() as $voc) {
        $key = json_encode(array($voc->getGraph(), $voc->getEndpoint()));
        $this->vocabularies_by_graph[$key] = $voc;
      }
    }

    $key = json_encode(array($graph,$endpoint));
    if (array_key_exists($key, $this->vocabularies_by_graph))
      return $this->vocabularies_by_graph[$key];
    else
      throw new Exception( "no vocabulary found for graph $graph and endpoint $endpoint");
  }

  /**
   * Guess which vocabulary a URI originates from, based on the declared
   * vocabulary URI spaces.
   *
   * @param $uri string URI to search
   * @return Vocabulary vocabulary of this URI, or null if not found
   */
  public function guessVocabularyFromURI($uri)
  {
    if ($this->vocabularies_by_urispace == null) { // initialize cache
      $this->vocabularies_by_urispace = array();
      foreach ($this->getVocabularies() as $voc) {
        $this->vocabularies_by_urispace[$voc->getUriSpace()] = $voc;
      }
    }

    // try to guess the URI space and look it up in the cache
    $res = new EasyRdf_Resource($uri);
    $namespace = substr($uri, 0, -strlen($res->localName()));
    if (array_key_exists($namespace, $this->vocabularies_by_urispace)) {
      return $this->vocabularies_by_urispace[$namespace];
    }

    // didn't work, try to match with each URI space separately
    foreach ($this->vocabularies_by_urispace as $urispace => $voc)
      if (strpos($uri, $urispace) === 0) return $voc;

    // not found
    return null;
  }

  public function getResourceFromUri($uri) {
    EasyRdf_Format::unregister('json'); 
    $resource = null;
    try {
      // using apc cache for the resource if available
      if (function_exists('apc_store') && function_exists('apc_fetch')) {
        $key = 'fetch: ' . $uri;
        $resource = apc_fetch($key);
        if ($resource === null || $resource === FALSE) { // was not found in cache
          $client = EasyRdf_Graph::newAndLoad($uri);
          $resource = $client->resource($uri);
          apc_store($key, $resource);
        }
      } else { // APC not available, parse on every request
        $client = EasyRdf_Graph::newAndLoad($uri);
        $resource = $client->resource($uri);
      }
    } catch (Exception $e) {
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

    return new $classname($endpoint, $graph, $this); exit();
  }

  /**
   * Returns a SPARQL endpoint object using the default implementation set in the config.inc.
   */
  public function getDefaultSparql()
  {
    return $this->getSparqlImplementation(DEFAULT_SPARQL_DIALECT, DEFAULT_ENDPOINT, '?graph');
  }

}
