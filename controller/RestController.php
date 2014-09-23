<?php
/**
 * Copyright (c) 2012-2013 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/**
 * Rest controller is an extension of the controller so that must be imported.
 */
require_once 'controller/Controller.php';

/**
 * RestController is responsible for handling all the requests directed to the /rest address.
 */
class RestController extends Controller
{
  /* supported MIME types that can be used to return RDF data */
  private static $SUPPORTED_MIME_TYPES = 'application/rdf+xml text/turtle application/ld+json application/json';

  /**
   * Echos an error message when the request can't be fulfilled.
   * @param string $code
   * @param string $status
   * @param string $message
   */
  private function return_error($code, $status, $message)
  {
    header("HTTP/1.0 $code $status");
    header("Content-type: text/plain");
    echo "$code $status : $message";
    exit();
  }

  /**
   * Handles json encoding, adding the content type headers and optional callback function.
   * @param array $data the data to be returned.
   */
  private function return_json($data)
  {
    if (isset($_GET['callback'])) {
      header("Content-type: application/javascript");
      // wrap with JSONP callback
      echo $_GET['callback'] . "(" . json_encode($data) . ");";
    } else {
      // negotiate suitable format
      $negotiator = new \Negotiation\FormatNegotiator();
      $priorities = array('application/json', 'application/ld+json');
      $best = (isset($_SERVER['HTTP_ACCEPT'])) ? $negotiator->getBest($_SERVER['HTTP_ACCEPT'], $priorities) : null;
      $format = $best != null ? $best->getValue() : $priorities[0];
      header("Content-type: $format");
      header("Vary: Accept"); // inform caches that we made a choice based on Accept header
      echo json_encode($data);
    }
  }

  /**
   * Gets a Vocabulary object. If not found, aborts with a HTTP 404 error.
   * @param string $vocabId identifier string for the vocabulary eg. 'yso'.
   * @return object Vocabulary object
   */

  private function getVocabulary($vocabId)
  {
    try {
      return $this->model->getVocabulary($vocabId);
    } catch (Exception $e) {
      return $this->return_error(404, "Not Found", "Vocabulary id '$vocabId' not found.");
    }
  }

  /**
   * Parses and returns the uri parameter. Returns and error if the parameter is missing.
   */
  private function parseURI()
  {
    if (isset($_GET['uri'])) return $_GET['uri'];
    return $this->return_error(400, "Bad Request", "uri parameter missing");
  }

  /**
   * Parses and returns the limit parameter. Returns and error if the parameter is missing.
   */
  private function parseLimit()
  {
    $limit = isset($_GET['limit']) ?
             intval($_GET['limit']) : DEFAULT_TRANSITIVE_LIMIT;
    if ($limit <= 0)
      $this->return_error(400, "Bad Request", "Invalid limit parameter");

    return $limit;
  }

  /**
   * Determine the language to use, from the lang URL parameter (if set),
   * otherwise from the default language of the current vocabulary.
   * As a side effect, set this language as current language.
   * @param string $vocabId identifier string for the vocabulary eg. 'yso'.
   * @return string current language eg. 'en'.
   */
  private function getAndSetLanguage($vocabId)
  {
    $lang = isset($_GET['lang']) ?
            $_GET['lang'] :
            $this->getVocabulary($vocabId)->getDefaultLanguage();
    $this->setLanguageProperties($lang);

    return $lang;
  }

/** Global REST methods **/

  /**
   * Returns all the vocabularies available on the server in a json object.
   */
  public function vocabularies()
  {
    if (!isset($_GET['lang']))
      return $this->return_error(400, "Bad Request", "lang parameter missing");
    $lang = $_GET['lang'];
    $this->setLanguageProperties($lang);

    $vocabs = array();
    foreach ($this->model->getVocabularies() as $voc) {
      $vocabs[$voc->getId()] = $voc->getTitle();
    }
    ksort($vocabs);
    $results = array();
    foreach ($vocabs as $id => $title) {
      $results[] = array(
        'uri' => $id,
        'id' => $id,
        'title' => $title);
    }

    /* encode the results in a JSON-LD compatible array */
    $ret = array(
        '@context' => array(
            'onki' => 'http://schema.onki.fi/onki#',
            'title' => array('@id'=>'rdfs:label', '@language'=>$lang),
            'vocabularies' => 'onki:hasVocabulary',
            'id' => 'onki:vocabularyIdentifier',
            'uri' => '@id',
        ),
        'uri' => '',
        'vocabularies' => $results,
    );

    return $this->return_json($ret);
  }

  /**
   * Performs the search function calls. And wraps the result in a json-ld object.
   * @param string $vocab identifier string for the vocabulary eg. 'yso'.
   */
  public function search($vocab=null)
  {
    if(isset($_GET['query']))
      $term = $_GET['query'];
    else
      return $this->return_error(400, "Bad Request", "query parameter missing");
    if (isset($_GET['maxhits']) && (!is_numeric($_GET['maxhits']) || $_GET['maxhits'] <= 0)) {
      return $this->return_error(400, "Bad Request", "maxhits parameter is invalid");
    }
    if (isset($_GET['offset']) && (!is_numeric($_GET['offset']) || $_GET['offset'] < 0)) {
      return $this->return_error(400, "Bad Request", "offset parameter is invalid");
    }

    $vocid = isset($_GET['vocab']) ? $_GET['vocab'] : $vocab; # optional
    $lang = isset($_GET['lang']) ? $_GET['lang'] : null; # optional
    $labellang = isset($_GET['labellang']) ? $_GET['labellang'] : null; # optional
    $type = isset($_GET['type']) ? $_GET['type'] : 'skos:Concept';
    $parent = isset($_GET['parent']) ? $_GET['parent'] : null;
    $group = isset($_GET['group']) ? $_GET['group'] : null;

    // convert to vocids array to support multi-vocabulary search
    $vocids = !empty($vocid) ? explode(' ', $vocid) : null;

    $maxhits = isset($_GET['maxhits']) ? ($_GET['maxhits']) : null; # optional
    $offset = isset($_GET['offset']) ? ($_GET['offset']) : 0; # optional
    $results = $this->model->searchConcepts($term, $vocids, $labellang, $lang, $type, $parent, $group, $offset, $maxhits);
    // before serializing to JSON, get rid of the Vocabulary object that came with each resource
    foreach ($results as &$res) {
      unset($res['voc']);
    }

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'onki' => 'http://schema.onki.fi/onki#',
            'uri' => '@id',
            'type' => '@type',
            'results' => array(
                '@id' => 'onki:results',
                '@container' => '@list',
            ),
            'prefLabel' => 'skos:prefLabel',
            'altLabel' => 'skos:altLabel',
            'hiddenLabel' => 'skos:hiddenLabel',
        ),
        'uri' => '',
        'results' => $results,
    );

    if ($lang)
      $ret['@context']['@language'] = $lang;

    return $this->return_json($ret);
  }

/** Vocabulary-specific methods **/

  /**
   * Loads the vocabulary metadata. And wraps the result in a json-ld object.
   * @param string $vocabId identifier string for the vocabulary eg. 'yso'.
   */
  public function vocabularyInformation($vocabId)
  {
    $lang = $this->getAndSetLanguage($vocabId);
    $vocab = $this->getVocabulary($vocabId);

    /* encode the results in a JSON-LD compatible array */
    $conceptschemes = array();
    foreach ($vocab->getConceptSchemes() as $uri => $csdata) {
      $csdata['uri'] = $uri;
      $csdata['type'] = 'skos:ConceptScheme';
      $conceptschemes[] = $csdata;
    }

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'onki' => 'http://schema.onki.fi/onki#',
            'dct' => 'http://purl.org/dc/terms/',
            'uri' => '@id',
            'type' => '@type',
            'title' => 'rdfs:label',
            'conceptschemes' => 'onki:hasConceptScheme',
            'id' => 'onki:vocabularyIdentifier',
            'defaultLanguage' => 'onki:defaultLanguage',
            'languages' => 'onki:language',
            'label' => 'rdfs:label',
            'prefLabel' => 'skos:prefLabel',
            'title' => 'dct:title',
            '@language' => $lang,
        ),
        'uri' => '',
        'id' => $vocabId,
        'title' => $vocab->getTitle(),
        'defaultLanguage' => $vocab->getDefaultLanguage(),
        'languages' => $vocab->getLanguages(),
        'conceptschemes' => $conceptschemes,
    );

    return $this->return_json($ret);
  }

  /**
   * Loads the vocabulary type metadata. And wraps the result in a json-ld object.
   * @param string $vocabId identifier string for the vocabulary eg. 'yso'.
   */
  public function types($vocabId = null)
  {
    if ($vocabId == null && !isset($_GET['lang']))
      return $this->return_error(400, "Bad Request", "lang parameter missing");
    $lang = $this->getAndSetLanguage($vocabId);
    $queriedtypes = $this->model->getTypes($vocabId, $lang);

    /* encode the results in a JSON-LD compatible array */
    foreach ($queriedtypes as $uri => $typedata) {
      $type = array_merge(array('uri' => $uri), $typedata);
      $types[] = $type;
    }

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'onki' => 'http://schema.onki.fi/onki#',
            'uri' => '@id',
            'type' => '@type',
            'label' => 'rdfs:label',
            'superclass' => array('@id' => 'rdfs:subClassOf', '@type' => '@id'),
            'types' => 'onki:hasType',
            '@language' => $lang,
        ),
        'uri' => '',
        'types' => $types,
    );

    return $this->return_json($ret);
  }

  /**
   * Used for finding terms by their exact prefLabel. Wraps the result in a json-ld object.
   * @param string $vocid identifier string for the vocabulary eg. 'yso'.
   */
  public function lookup($vocid)
  {
    if(isset($_GET['label']))
      $label = $_GET['label'];
    else
      return $this->return_error(400, "Bad Request", "label parameter missing");
    $lang = isset($_GET['lang']) ? $_GET['lang'] : null; # optional
    $vocab = $this->getVocabulary($vocid);
    if ($label == '') $this->return_error(400, 'Bad Request', 'empty label');

    $results = $this->model->searchConcepts($label, $vocid, $lang, $lang);

    $hits = array();
    // case 1: exact match on preferred label
    foreach ($results as $res)
      if ($res['prefLabel'] == $label)
        $hits[] = $res;

    // case 2: case-insensitive match on preferred label
    if (sizeof($hits) == 0) { // not yet found
      foreach ($results as $res)
        if (strtolower($res['prefLabel']) == strtolower($label))
          $hits[] = $res;
    }

    // case 3: exact match on alternate label
    if (sizeof($hits) == 0) { // not yet found
      foreach ($results as $res)
        if (isset($res['altLabel']) && $res['altLabel'] == $label)
          $hits[] = $res;
    }

    // case 4: case-insensitive match on alternate label
    if (sizeof($hits) == 0) { // not yet found
      foreach ($results as $res)
        if (isset($res['altLabel']) && strtolower($res['altLabel']) == strtolower($label))
          $hits[] = $res;
    }

    if (sizeof($hits) == 0) // no matches found
      return $this->return_error(404, 'Not Found', "Could not find label '$label'");

    // did find some matches!
    // get rid of Vocabulary objects
    foreach($hits as &$res)
      unset($res['voc']);

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'onki' => 'http://schema.onki.fi/onki#',
            'results' => array(
                '@id' => 'onki:results',
            ),
            'uri' => '@id',
            'prefLabel' => 'skos:prefLabel',
            'altLabel' => 'skos:altLabel',
            'hiddenLabel' => 'skos:hiddenLabel',
        ),
        'uri' => '',
        'results' => $hits,
    );
    if ($lang)
      $ret['@context']['@language'] = $lang;

    return $this->return_json($ret);
  }

  /**
   * Queries the top concepts of a vocabulary and wraps the results in a json-ld object.
   * @param string $vocabId identifier string for the vocabulary eg. 'yso'.
   * @return object json-ld object
   */
  public function topConcepts($vocabId)
  {
    $lang = $this->getAndSetLanguage($vocabId);
    $vocab = $this->getVocabulary($vocabId);
    $scheme = isset($_GET['scheme']) ? $_GET['scheme'] : $vocab->getDefaultConceptScheme();

    /* encode the results in a JSON-LD compatible array */
    $topconcepts = $vocab->getTopConcepts($scheme);
    $results = array();
    foreach ($topconcepts as $uri => $label) {
      $results[] = array('uri'=>$uri, 'label'=>$label);
    }

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'onki' => 'http://schema.onki.fi/onki#',
            'uri' => '@id',
            'topconcepts' => 'skos:hasTopConcept',
            'label' => 'skos:prefLabel',
            '@language' => $lang,
        ),
        'uri' => $scheme,
        'topconcepts' => $results,
    );

    return $this->return_json($ret);
  }

  /**
   * Download a concept as json-ld or redirect to download the whole vocabulary.
   * @param string $vocab vocabulary identifier.
   * @return object json-ld formatted concept.
   */
  public function data($vocab=null)
  {
    if (isset($_GET['uri'])) {
      $uri = $_GET['uri'];
    } else if ($vocab !== null) { // whole vocabulary - redirect to download URL
      $url = $this->getVocabulary($vocab)->getDataURL();
      if (!$url)
        return $this->return_error('404', 'Not Found', "No download source URL known for vocabulary $vocab");
      header("Location: " . $url);

      return;
    } else {
      return $this->return_error(400, 'Bad Request', "uri parameter missing");
    }
    
    if (isset($_GET['format'])) {
      $format = $_GET['format'];
      if (!in_array($format, explode(' ', self::$SUPPORTED_MIME_TYPES)))
        return $this->return_error(400, 'Bad Request', "Unsupported format. Supported MIME types are: " . self::$SUPPORTED_MIME_TYPES);
    } else {
      header('Vary: Accept'); // inform caches that a decision was made based on Accept header
      $priorities = explode(' ', self::$SUPPORTED_MIME_TYPES);
      $best = $this->negotiator->getBest($_SERVER['HTTP_ACCEPT'], $priorities);
      $format = $best != null ? $best->getValue() : $priorities[0];
    }
    
    $results = $this->model->getRDF($vocab, $uri, $format);

    if ($format == 'application/ld+json' || $format == 'application/json') {
      // further compact JSON-LD document using a context
      $context = array(
          'skos' => 'http://www.w3.org/2004/02/skos/core#',
          'isothes' => 'http://purl.org/iso25964/skos-thes#',
          'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
          'owl' => 'http://www.w3.org/2002/07/owl#',
          'dct' => 'http://purl.org/dc/terms/',
          'dc11' => 'http://purl.org/dc/elements/1.1/',
          'uri' => '@id',
          'type' => '@type',
          'lang' => '@language',
          'value' => '@value',
          'graph' => '@graph',
          'label' => 'rdfs:label',
          'prefLabel' => 'skos:prefLabel',
          'altLabel' => 'skos:altLabel',
          'hiddenLabel' => 'skos:hiddenLabel',
          'broader' => 'skos:broader',
          'narrower' => 'skos:narrower',
          'related' => 'skos:related',
          'inScheme' => 'skos:inScheme',
      );
      $compact_jsonld = \ML\JsonLD\JsonLD::compact($results, json_encode($context));
      $results = \ML\JsonLD\JsonLD::toString($compact_jsonld);
    }

    header("Content-type: " . $format);
    echo $results;
  }

  /**
   * Used for querying labels for a uri.
   * @param string $vocid vocabulary identifier eg. 'yso'.
   * @return object json-ld wrapped labels.
   */
  public function label($vocid)
  {
    $lang = $this->getAndSetLanguage($vocid);
    $vocab = $this->getVocabulary($vocid);
    $uri = $this->parseURI();

    $results = $vocab->getConceptLabel($uri, $lang);
    if ($results === NULL)
      return $this->return_error('404', 'Not Found', "Could not find concept <$uri>");

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'uri' => '@id',
            'prefLabel' => 'skos:prefLabel',
            '@language' => $lang,
        ),
        'uri' => $uri,
    );

    if (isset($results[$lang]))
      $ret['prefLabel'] = $results[$lang];

    return $this->return_json($ret);
  }

  /**
   * Used for querying broader relations for a concept.
   * @param string $vocabId vocabulary identifier eg. 'yso'.
   * @return object json-ld wrapped broader concept uris and labels.
   */
  public function broader($vocabId)
  {
    $lang = $this->getAndSetLanguage($vocabId);
    $vocab = $this->getVocabulary($vocabId);
    $uri = $this->parseURI();

    $results = array();
    $broaders = $vocab->getConceptBroaders($uri);
    if ($broaders === NULL)
      return $this->return_error('404', 'Not Found', "Could not find concept <$uri>");
    foreach ($broaders as $object => $vals) {
      $results[] = array('uri'=>$object, 'prefLabel'=>$vals['label']);
    }

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'uri' => '@id',
            'prefLabel' => 'skos:prefLabel',
            'broader' => 'skos:broader',
            '@language' => $lang,
        ),
        'uri' => $uri,
        'broader' => $results,
    );

    return $this->return_json($ret);
  }

  /**
   * Used for querying broader transitive relations for a concept.
   * @param string $vocabId vocabulary identifier eg. 'yso'.
   * @return object json-ld wrapped broader transitive concept uris and labels.
   */
  public function broaderTransitive($vocabId)
  {
    $lang = $this->getAndSetLanguage($vocabId);
    $vocab = $this->getVocabulary($vocabId);
    $uri = $this->parseURI();
    $limit = $this->parseLimit();

    $results = array();
    $broaders = $vocab->getConceptTransitiveBroaders($uri, $limit);
    if ($broaders === NULL)
      return $this->return_error('404', 'Not Found', "Could not find concept <$uri>");
    foreach ($broaders as $buri => $vals) {
      $result = array('uri'=>$buri, 'prefLabel'=>$vals['label']);
      if (isset($vals['direct'])) {
        $result['broader'] = $vals['direct'];
      }
      $results[$buri] = $result;
    }

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'uri' => '@id',
            'type' => '@type',
            'prefLabel' => 'skos:prefLabel',
            'broader' => array('@id'=>'skos:broader','@type'=>'@id'),
            'broaderTransitive' => array('@id'=>'skos:broaderTransitive','@container'=>'@index'),
            '@language' => $lang,
        ),
        'uri' => $uri,
        'broaderTransitive' => $results,
    );

    return $this->return_json($ret);
  }

  /**
   * Used for querying narrower relations for a concept.
   * @param string $vocabId vocabulary identifier eg. 'yso'.
   * @return object json-ld wrapped narrower concept uris and labels.
   */
  public function narrower($vocabId)
  {
    $lang = $this->getAndSetLanguage($vocabId);
    $vocab = $this->getVocabulary($vocabId);
    $uri = $this->parseURI();

    $results = array();
    $narrowers = $vocab->getConceptNarrowers($uri);
    if ($narrowers === NULL)
      return $this->return_error('404', 'Not Found', "Could not find concept <$uri>");
    foreach ($narrowers as $object => $vals) {
      $results[] = array('uri'=>$object, 'prefLabel'=>$vals['label']);
    }

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'uri' => '@id',
            'prefLabel' => 'skos:prefLabel',
            'narrower' => 'skos:narrower',
            '@language' => $lang,
        ),
        'uri' => $uri,
        'narrower' => $results,
    );

    return $this->return_json($ret);
  }

  /**
   * Used for querying narrower transitive relations for a concept.
   * @param string $vocabId vocabulary identifier eg. 'yso'.
   * @return object json-ld wrapped narrower transitive concept uris and labels.
   */
  public function narrowerTransitive($vocabId)
  {
    $lang = $this->getAndSetLanguage($vocabId);
    $vocab = $this->getVocabulary($vocabId);
    $uri = $this->parseURI();
    $limit = $this->parseLimit();
    isset($_GET['limit']) ?
             intval($_GET['limit']) : DEFAULT_TRANSITIVE_LIMIT;
    if ($limit <= 0) $this->return_error(400, "Bad Request", "Invalid limit parameter");

    $results = array();
    $narrowers = $vocab->getConceptTransitiveNarrowers($uri, $limit);
    if ($narrowers === NULL)
      return $this->return_error('404', 'Not Found', "Could not find concept <$uri>");
    foreach ($narrowers as $nuri => $vals) {
      $result = array('uri'=>$nuri, 'prefLabel'=>$vals['label']);
      if (isset($vals['direct'])) {
        $result['narrower'] = $vals['direct'];
      }
      $results[$nuri] = $result;
    }

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'uri' => '@id',
            'type' => '@type',
            'prefLabel' => 'skos:prefLabel',
            'narrower' => array('@id'=>'skos:narrower','@type'=>'@id'),
            'narrowerTransitive' => array('@id'=>'skos:narrowerTransitive','@container'=>'@index'),
            '@language' => $lang,
        ),
        'uri' => $uri,
        'narrowerTransitive' => $results,
    );

    return $this->return_json($ret);
  }

  /**
   * Used for querying broader transitive relations
   * and some narrowers for a concept in the hierarchy view.
   * @param string $vocabId vocabulary identifier eg. 'yso'.
   * @return object json-ld wrapped hierarchical concept uris and labels.
   */
  public function hierarchy($vocabId)
  {
    $lang = $this->getAndSetLanguage($vocabId);
    $vocab = $this->getVocabulary($vocabId);
    $uri = $this->parseURI();

    $results = $this->getVocabulary($vocabId)->getConceptHierarchy($uri);
    if ($results === NULL)
      return $this->return_error('404', 'Not Found', "Could not find concept <$uri>");
    
    if ($vocab->getShowHierarchy()) {
      $scheme = isset($_GET['scheme']) ? $_GET['scheme'] : $vocab->getDefaultConceptScheme();

      /* encode the results in a JSON-LD compatible array */
      $topconcepts = $vocab->getTopConcepts($scheme);
      foreach ($topconcepts as $uri => $label) {
        if (!isset($results[$uri]))
          $results[$uri] = array('uri'=>$uri, 'top'=>$scheme, 'prefLabel'=>$label, 'hasChildren'=> true);
      }
    }

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'onki' => 'http://schema.onki.fi/onki#',
            'uri' => '@id',
            'type' => '@type',
            'prefLabel' => 'skos:prefLabel',
            'narrower' => array('@id'=>'skos:narrower','@type'=>'@id'),
            'broader' => array('@id'=>'skos:broader','@type'=>'@id'),
            'broaderTransitive' => array('@id'=>'skos:broaderTransitive','@container'=>'@index'),
            'top' => array('@id'=>'skos:topConceptOf','@type'=>'@id'),
            'hasChildren' => 'onki:hasChildren',
            '@language' => $lang,
        ),
        'uri' => $uri,
        'broaderTransitive' => $results,
    );

    return $this->return_json($ret);
  }

  /**
   * Used for querying narrower relations for a concept in the hierarchy view.
   * @param string $vocabId vocabulary identifier eg. 'yso'.
   * @return object json-ld wrapped narrower concept uris and labels.
   */
  public function children($vocabId)
  {
    $lang = $this->getAndSetLanguage($vocabId);
    $vocab = $this->getVocabulary($vocabId);
    $uri = $this->parseURI();

    $children = $vocab->getConceptChildren($uri);
    if ($children === NULL)
      return $this->return_error('404', 'Not Found', "Could not find concept <$uri>");

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'uri' => '@id',
            'type' => '@type',
            'prefLabel' => 'skos:prefLabel',
            'narrower' => 'skos:narrower',
            'hasChildren' => 'onki:hasChildren',
            '@language' => $lang,
        ),
        'uri' => $uri,
        'narrower' => $children,
    );

    return $this->return_json($ret);
  }

  /**
   * Used for querying narrower relations for a concept in the hierarchy view.
   * @param string $vocabId vocabulary identifier eg. 'yso'.
   * @return object json-ld wrapped hierarchical concept uris and labels.
   */
  public function related($vocabId)
  {
    $lang = $this->getAndSetLanguage($vocabId);
    $vocab = $this->getVocabulary($vocabId);
    $uri = $this->parseURI();

    $results = array();
    $related = $vocab->getConceptRelateds($uri);
    if ($related === NULL)
      return $this->return_error('404', 'Not Found', "Could not find concept <$uri>");
    foreach ($related as $uri => $vals) {
      $results[] = array('uri'=>$uri, 'prefLabel'=>$vals['label']);
    }

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'uri' => '@id',
            'type' => '@type',
            'prefLabel' => 'skos:prefLabel',
            'related' => 'skos:related',
            '@language' => $lang,
        ),
        'uri' => $uri,
        'related' => $results,
    );

    return $this->return_json($ret);
  }
}
