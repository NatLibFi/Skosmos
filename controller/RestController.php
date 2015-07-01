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
    header("Content-type: text/plain; charset=utf-8");
    echo "$code $status : $message";
  }

  /**
   * Handles json encoding, adding the content type headers and optional callback function.
   * @param array $data the data to be returned.
   */
  private function return_json($data)
  {
    if (isset($_GET['callback'])) {
      header("Content-type: application/javascript; charset=utf-8");
      // wrap with JSONP callback
      echo $_GET['callback'] . "(" . json_encode($data) . ");";
    } else {
      // negotiate suitable format
      $negotiator = new \Negotiation\FormatNegotiator();
      $priorities = array('application/json', 'application/ld+json');
      $best = (isset($_SERVER['HTTP_ACCEPT'])) ? $negotiator->getBest($_SERVER['HTTP_ACCEPT'], $priorities) : null;
      $format = $best != null ? $best->getValue() : $priorities[0];
      header("Content-type: $format; charset=utf-8");
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
   * Parses and returns the uri parameter. Returns and error if the parameter is missing.
   */
  private function parseLang()
  {
    if (isset($_GET['lang'])) return $_GET['lang'];
    else return '';
  }

  /**
   * Parses and returns the limit parameter. Returns and error if the parameter is missing.
   */
  private function parseLimit()
  {
    $limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT) ? filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT) : DEFAULT_TRANSITIVE_LIMIT;
    if ($limit <= 0)
      return $this->return_error(400, "Bad Request", "Invalid limit parameter");

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
  public function vocabularies($request)
  {
    if (!$request->getLang())
      return $this->return_error(400, "Bad Request", "lang parameter missing");

    $vocabs = array();
    foreach ($this->model->getVocabularies() as $voc) {
      $vocabs[$voc->getId()] = $voc->getTitle($request->getLang());
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
            'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
            'onki' => 'http://schema.onki.fi/onki#',
            'title' => array('@id'=>'rdfs:label', '@language'=>$request->getLang()),
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
   * @param Request $request
   */
  public function search($request)
  {
    $maxhits = $request->getQueryParam('maxhits');
    $offset = $request->getQueryParam('offset');
    $term = $request->getQueryParam('query');

    if(!$term) {
      return $this->return_error(400, "Bad Request", "query parameter missing");
    }
    if ($maxhits && (!is_numeric($maxhits) || $maxhits <= 0)) {
      return $this->return_error(400, "Bad Request", "maxhits parameter is invalid");
    }
    if ($offset && (!is_numeric($offset) || $offset < 0)) {
      return $this->return_error(400, "Bad Request", "offset parameter is invalid");
    }

    $vocid = $request->getVocab() ? $request->getVocab()->getId() : null; # optional
    $lang = $request->getLang(); # optional
    $labellang = $request->getQueryParam('labellang'); # optional
    $types =  $request->getQueryParam('type') ? explode(' ', $request->getQueryParam('type')) : array('skos:Concept');
    $parent = $request->getQueryParam('parent');
    $group = $request->getQueryParam('group');
    $fields = $request->getQueryParam('fields') ? explode(' ', $request->getQueryParam('fields')) : null;

    // convert to vocids array to support multi-vocabulary search
    $vocids = !empty($vocid) ? explode(' ', $vocid) : null;

    $results = $this->model->searchConcepts($term, $vocids, $labellang, $lang, $types, $parent, $group, $offset, $maxhits, true, $fields);
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
            'broader' => 'skos:broader',
        ),
        'uri' => '',
        'results' => $results,
    );

    if ($lang)
      $ret['@context']['@language'] = $labellang;

    return $this->return_json($ret);
  }

/** Vocabulary-specific methods **/

  /**
   * Loads the vocabulary metadata. And wraps the result in a json-ld object.
   * @param Request $request
   */
  public function vocabularyInformation($request)
  {
    $vocab = $request->getVocab();

    /* encode the results in a JSON-LD compatible array */
    $conceptschemes = array();
    foreach ($vocab->getConceptSchemes() as $uri => $csdata) {
      $csdata['uri'] = $uri;
      $csdata['type'] = 'skos:ConceptScheme';
      $conceptschemes[] = $csdata;
    }

    $ret = array(
        '@context' => array(
            'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
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
            '@language' => $request->getLang(),
        ),
        'uri' => '',
        'id' => $vocab->getId(),
        'title' => $vocab->getTitle(),
        'defaultLanguage' => $vocab->getDefaultLanguage(),
        'languages' => $vocab->getLanguages(),
        'conceptschemes' => $conceptschemes,
    );

    return $this->return_json($ret);
  }

  /**
   * Loads the vocabulary metadata. And wraps the result in a json-ld object.
   * @param Request $request
   */
  public function vocabularyStatistics($request)
  {
    $vocab_stats = $request->getVocab()->getStatistics();

    /* encode the results in a JSON-LD compatible array */
    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'void' => 'http://rdfs.org/ns/void#',
            'onki' => 'http://schema.onki.fi/onki#',
            'uri' => '@id',
            'id' => 'onki:vocabularyIdentifier',
            'concepts' => 'void:classPartition',
            'class' => array('@id' => 'void:class', '@type' => '@id'),
            'count' => 'void:entities',
            '@language' => $request->getLang(),
        ),
        'uri' => '',
        'id' => $request->getVocab()->getId(),
        'title' => $request->getVocab()->getTitle(),
        'concepts' => array(
            'class' => 'skos:Concept',
            'count' => $vocab_stats['concepts'],
        ),
    );

    return $this->return_json($ret);
  }
  
  /**
   * Loads the vocabulary metadata. And wraps the result in a json-ld object.
   * @param string $vocabId identifier string for the vocabulary eg. 'yso'.
   */
  public function labelStatistics($vocabId)
  {
    $lang = $this->getAndSetLanguage($vocabId);
    $vocab = $this->getVocabulary($vocabId);
    
    if(isset($_GET['lang'])) // used in the UI for including literals for the langcodes.
      $litlang = $_GET['lang'];
    else
      $litlang = null;
    
    $vocab_stats = $vocab->getLabelStatistics();

    /* encode the results in a JSON-LD compatible array */
    $counts = array();
    foreach ($vocab_stats['terms'] as $proplang => $properties) {
      $langdata = array('language' => $proplang);
      if ($litlang) $langdata['literal'] = Punic\Language::getName($proplang, $litlang);
      $langdata['properties'] = array();
      foreach ($properties as $prop => $value) {
        $langdata['properties'][] = array('property' => $prop, 'labels' => $value);
      }
      $counts[] = $langdata;
    }

    $ret = array(
        '@context' => array(
            'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'void' => 'http://rdfs.org/ns/void#',
            'void-ext' => 'http://ldf.fi/void-ext#',
            'onki' => 'http://schema.onki.fi/onki#',
            'uri' => '@id',
            'id' => 'onki:vocabularyIdentifier',
            'languages' => 'void-ext:languagePartition',
            'language' => 'void-ext:language',
            'properties' => 'void:propertyPartition',
            'labels' => 'void:triples',
        ),
        'uri' => '',
        'id' => $vocabId,
        'title' => $vocab->getTitle($litlang),
        'languages' => $counts 
    );
    
    if ($litlang)
      $ret['@context']['literal'] = array('@id' => 'rdfs:label', '@language' => $litlang);

    return $this->return_json($ret);
  }

  /**
   * Loads the vocabulary type metadata. And wraps the result in a json-ld object.
   * @param Request $request
   */
  public function types($request)
  {
    $queriedtypes = $this->model->getTypes($request->getVocab()->getId(), $request->getLang());

    $types = array();

    /* encode the results in a JSON-LD compatible array */
    foreach ($queriedtypes as $uri => $typedata) {
      $type = array_merge(array('uri' => $uri), $typedata);
      $types[] = $type;
    }

    $ret = array(
        '@context' => array(
            'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'onki' => 'http://schema.onki.fi/onki#',
            'uri' => '@id',
            'type' => '@type',
            'label' => 'rdfs:label',
            'superclass' => array('@id' => 'rdfs:subClassOf', '@type' => '@id'),
            'types' => 'onki:hasType',
            '@language' => $request->getLang(),
        ),
        'uri' => '',
        'types' => $types,
    );

    return $this->return_json($ret);
  }

  /**
   * Used for finding terms by their exact prefLabel. Wraps the result in a json-ld object.
   * @param Request $request
   */
  public function lookup($request)
  {
    $label = $request->getQueryParam('label');
    if(!$label)
      return $this->return_error(400, "Bad Request", "label parameter missing");
    $lang = $request->getLang(); # optional
    $vocab = $request->getVocab();

    $results = $this->model->searchConcepts($label, $vocab->getId(), $lang, $lang);

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
   * @param Request $request
   * @return object json-ld object
   */
  public function topConcepts($request)
  {
    $vocab = $request->getVocab();
    $scheme = $request->getQueryParam('scheme') ? $request->getQueryParam('scheme') : $vocab->getDefaultConceptScheme();

    /* encode the results in a JSON-LD compatible array */
    $topconcepts = $vocab->getTopConcepts($scheme, $request->getLang());
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
            '@language' => $request->getLang(),
        ),
        'uri' => $scheme,
        'topconcepts' => $results,
    );

    return $this->return_json($ret);
  }

  /**
   * Download a concept as json-ld or redirect to download the whole vocabulary.
   * @param Request $request
   * @return object json-ld formatted concept.
   */
  public function data($request)
  {
    $vocab = $request->getVocab();
    $format = $request->getQueryParam('format');

    if ($request->getUri()) {
      $uri = $request->getUri();
    } else if ($vocab !== null) { // whole vocabulary - redirect to download URL
      $urls = $vocab->getDataURLs();
      if (sizeof($urls) == 0)
        return $this->return_error('404', 'Not Found', "No download source URL known for vocabulary $vocab");

      if ($format) {
        if (!in_array($format, array_keys($urls)))
          return $this->return_error(400, 'Bad Request', "Unsupported format. Supported MIME types are: " . implode(' ', array_keys($urls)));
      } else {
        header('Vary: Accept'); // inform caches that a decision was made based on Accept header
        $priorities = array_keys($urls);
        $best = $this->negotiator->getBest($request->getServerConstant('HTTP_ACCEPT'), $priorities);
        $format = $best != null ? $best->getValue() : $priorities[0];
      }
      header("Location: " . $urls[$format]);
      return;
    } else {
      return $this->return_error(400, 'Bad Request', "uri parameter missing");
    }
    
    if ($format) {
      if (!in_array($format, explode(' ', self::$SUPPORTED_MIME_TYPES)))
        return $this->return_error(400, 'Bad Request', "Unsupported format. Supported MIME types are: " . self::$SUPPORTED_MIME_TYPES);
    } else {
      header('Vary: Accept'); // inform caches that a decision was made based on Accept header
      $priorities = explode(' ', self::$SUPPORTED_MIME_TYPES);
      $best = $this->negotiator->getBest($request->getQueryParam('HTTP_ACCEPT'), $priorities);
      $format = $best != null ? $best->getValue() : $priorities[0];
    }
    
    $vocid = $vocab ? $vocab->getId() : null;
    $results = $this->model->getRDF($vocid, $uri, $format);

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

    header("Content-type: $format; charset=utf-8");
    echo $results;
  }

  /**
   * Used for querying labels for a uri.
   * @param Request $request
   * @return object json-ld wrapped labels.
   */
  public function label($request)
  {
    $lang = $request->getLang() ? $request->getLang() : $request->getVocab()->getDefaultLanguage(); 
    
    if (!$request->getUri())
      return $this->return_error(400, "Bad Request", "uri parameter missing");
    $uri = $request->getUri();

    $results = $request->getVocab()->getConceptLabel($uri, $lang);
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
      $ret['prefLabel'] = $results[$lang]->getValue();

    return $this->return_json($ret);
  }

  /**
   * Used for querying broader relations for a concept.
   * @param Request $request
   * @return object json-ld wrapped broader concept uris and labels.
   */
  public function broader($request)
  {
    $lang = $request->getLang() ? $request->getLang() : $request->getVocab()->getDefaultLanguage(); 
    $uri = $request->getUri();

    $results = array();
    $broaders = $request->getVocab()->getConceptBroaders($uri, $lang);
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
   * @param Request $request
   * @return object json-ld wrapped broader transitive concept uris and labels.
   */
  public function broaderTransitive($request)
  {
    $lang = $request->getLang() ? $request->getLang() : $request->getVocab()->getDefaultLanguage(); 
    $uri = $request->getUri();
    $limit = $this->parseLimit();

    $results = array();
    $broaders = $request->getVocab()->getConceptTransitiveBroaders($uri, $limit, false, $lang);
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
   * @param Request $request
   * @return object json-ld wrapped narrower concept uris and labels.
   */
  public function narrower($request)
  {
    $lang = $request->getLang() ? $request->getLang() : $request->getVocab()->getDefaultLanguage(); 
    $uri = $request->getUri();

    $results = array();
    $narrowers = $request->getVocab()->getConceptNarrowers($uri, $lang);
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
   * @param Request $request
   * @return object json-ld wrapped narrower transitive concept uris and labels.
   */
  public function narrowerTransitive($request)
  {
    $lang = $request->getLang() ? $request->getLang() : $request->getVocab()->getDefaultLanguage(); 
    $uri = $request->getUri();
    $limit = $this->parseLimit();

    $results = array();
    $narrowers = $request->getVocab()->getConceptTransitiveNarrowers($uri, $limit, $lang);
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
   * @param Request $request
   * @return object json-ld wrapped hierarchical concept uris and labels.
   */
  public function hierarchy($request)
  {
    $lang = $request->getLang() ? $request->getLang() : $request->getVocab()->getDefaultLanguage(); 
    $uri = $request->getUri();

    $results = $request->getVocab()->getConceptHierarchy($uri, $lang);
    if ($results === NULL)
      return $this->return_error('404', 'Not Found', "Could not find concept <$uri>");
    
    if ($request->getVocab()->getShowHierarchy()) {
      $scheme = $request->getQueryParam('scheme') ? $request->getQueryParam('scheme') : $request->getVocab()->getDefaultConceptScheme();

      /* encode the results in a JSON-LD compatible array */
      $topconcepts = $request->getVocab()->getTopConcepts($scheme, $lang);
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
   * @param Request $request
   * @return object json-ld wrapped narrower concept uris and labels.
   */
  public function children($request)
  {
    $lang = $request->getLang() ? $request->getLang() : $request->getVocab()->getDefaultLanguage(); 
    $uri = $request->getUri();

    $children = $request->getVocab()->getConceptChildren($uri, $lang);
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
   * @param Request $request
   * @return object json-ld wrapped hierarchical concept uris and labels.
   */
  public function related($request)
  {
    $lang = $request->getLang() ? $request->getLang() : $request->getVocab()->getDefaultLanguage(); 
    $uri = $request->getUri();

    $results = array();
    $related = $request->getVocab()->getConceptRelateds($uri, $lang);
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
