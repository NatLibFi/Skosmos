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
    if (filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING)) {
      header("Content-type: application/javascript; charset=utf-8");
      // wrap with JSONP callback
      echo filter_input(INPUT_GET, 'callback', FILTER_UNSAFE_RAW) . "(" . json_encode($data) . ");";
    } else {
      // negotiate suitable format
      $negotiator = new \Negotiation\FormatNegotiator();
      $priorities = array('application/json', 'application/ld+json');
      $best = filter_input(INPUT_SERVER, 'HTTP_ACCEPT', FILTER_SANITIZE_STRING) ? $negotiator->getBest(filter_input(INPUT_SERVER, 'HTTP_ACCEPT', FILTER_SANITIZE_STRING), $priorities) : null;
      $format = $best != null ? $best->getValue() : $priorities[0];
      header("Content-type: $format; charset=utf-8");
      header("Vary: Accept"); // inform caches that we made a choice based on Accept header
      echo json_encode($data);
    }
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
   * Negotiate a MIME type according to the proposed format, the list of valid
   * formats, and an optional proposed format. 
   * As a side effect, set the HTTP Vary header if a choice was made based on
   * the Accept header.
   * @param array $choices possible MIME types as strings
   * @param string $accept HTTP Accept header value
   * @param string $format proposed format
   * @return string selected format, or null if negotiation failed
   */
  private function negotiateFormat($choices, $accept, $format) {
    if ($format) {
      if (!in_array($format, $choices))
        return null;
    } else {
      header('Vary: Accept'); // inform caches that a decision was made based on Accept header
      $best = $this->negotiator->getBest($accept, $choices);
      $format = ($best != null) ? $best->getValue() : null;
    }
    return $format;
  }


/** Global REST methods **/

  /**
   * Returns all the vocabularies available on the server in a json object.
   */
  public function vocabularies($request)
  {
    if (!$request->getLang())
      return $this->return_error(400, "Bad Request", "lang parameter missing");
    $this->setLanguageProperties($request->getLang());

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

    $vocid = $request->getVocabId(); # optional
    $lang = $request->getQueryParam('lang'); # optional
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

    if ($labellang)
      $ret['@context']['@language'] = $labellang;
    elseif ($lang)
      $ret['@context']['@language'] = $lang;

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
    $this->setLanguageProperties($request->getLang());

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
    $this->setLanguageProperties($request->getLang());
    $vocab_stats = $request->getVocab()->getStatistics($request->getQueryParam('lang'));
    $subTypes = array();
    foreach($vocab_stats as $subtype) {
      if ($subtype['type'] !== 'http://www.w3.org/2004/02/skos/core#Concept') {
        $subTypes[] = $subtype;
      }
    }

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
            'subTypes' => array('@id' => 'void:class', '@type' => '@id'),
            'count' => 'void:entities',
            '@language' => $request->getLang(),
        ),
        'uri' => '',
        'id' => $request->getVocab()->getId(),
        'title' => $request->getVocab()->getTitle(),
        'concepts' => array(
            'class' => gettext('skos:Concept'),
            'count' => $vocab_stats['http://www.w3.org/2004/02/skos/core#Concept']['count'],
        ),
        'subTypes' => $subTypes 
    );

    return $this->return_json($ret);
  }
  
  /**
   * Loads the vocabulary metadata. And wraps the result in a json-ld object.
   * @param Request $request
   */
  public function labelStatistics($request)
  {
    $lang = $request->getLang();
    $this->setLanguageProperties($request->getLang());
    $vocab_stats = $request->getVocab()->getLabelStatistics();

    /* encode the results in a JSON-LD compatible array */
    $counts = array();
    foreach ($vocab_stats['terms'] as $proplang => $properties) {
      $langdata = array('language' => $proplang);
      if ($lang) $langdata['literal'] = Punic\Language::getName($proplang, $lang);
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
        'id' => $request->getVocab()->getId(),
        'title' => $request->getVocab()->getTitle($lang),
        'languages' => $counts 
    );
    
    if ($lang)
      $ret['@context']['literal'] = array('@id' => 'rdfs:label', '@language' => $lang);

    return $this->return_json($ret);
  }

  /**
   * Loads the vocabulary type metadata. And wraps the result in a json-ld object.
   * @param Request $request
   */
  public function types($request)
  {
    $this->setLanguageProperties($request->getLang());
    $vocid = $request->getVocab() ? $request->getVocab()->getId() : null;
    $queriedtypes = $this->model->getTypes($vocid, $request->getLang());

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
    $lang = $request->getQueryParam('lang');
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

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'onki' => 'http://schema.onki.fi/onki#',
            'uri' => '@id',
            'topconcepts' => 'skos:hasTopConcept',
            'notation' => 'skos:notation',
            'label' => 'skos:prefLabel',
            '@language' => $request->getLang(),
        ),
        'uri' => $scheme,
        'topconcepts' => $topconcepts,
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

      $format = $this->negotiateFormat(array_keys($urls), $request->getServerConstant('HTTP_ACCEPT'), $format);
      if (!$format) return $this->return_error(406, 'Not Acceptable', "Unsupported format. Supported MIME types are: " . implode(' ', array_keys($urls)));
      header("Location: " . $urls[$format]);
      return;
    } else {
      return $this->return_error(400, 'Bad Request', "uri parameter missing");
    }
    
    $format = $this->negotiateFormat(explode(' ', self::$SUPPORTED_MIME_TYPES), $request->getServerConstant('HTTP_ACCEPT'), $format);
    if (!$format) return $this->return_error(406, 'Not Acceptable', "Unsupported format. Supported MIME types are: " . self::$SUPPORTED_MIME_TYPES);
    
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
    if (!$request->getUri())
      return $this->return_error(400, "Bad Request", "uri parameter missing");
    $uri = $request->getUri();

    $results = $request->getVocab()->getConceptLabel($uri, $request->getLang());
    if ($results === NULL)
      return $this->return_error('404', 'Not Found', "Could not find concept <$uri>");

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'uri' => '@id',
            'prefLabel' => 'skos:prefLabel',
            '@language' => $request->getLang(),
        ),
        'uri' => $uri,
    );

    if (isset($results[$request->getLang()]))
      $ret['prefLabel'] = $results[$request->getLang()]->getValue();

    return $this->return_json($ret);
  }

  /**
   * Used for querying broader relations for a concept.
   * @param Request $request
   * @return object json-ld wrapped broader concept uris and labels.
   */
  public function broader($request)
  {
    $uri = $request->getUri();

    $results = array();
    $broaders = $request->getVocab()->getConceptBroaders($uri, $request->getLang());
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
            '@language' => $request->getLang(),
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
    $uri = $request->getUri();
    $limit = $this->parseLimit();

    $results = array();
    $broaders = $request->getVocab()->getConceptTransitiveBroaders($uri, $limit, false, $request->getLang());
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
            '@language' => $request->getLang(),
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
    $uri = $request->getUri();

    $results = array();
    $narrowers = $request->getVocab()->getConceptNarrowers($uri, $request->getLang());
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
            '@language' => $request->getLang(),
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
    $uri = $request->getUri();
    $limit = $this->parseLimit();

    $results = array();
    $narrowers = $request->getVocab()->getConceptTransitiveNarrowers($uri, $limit, $request->getLang());
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
            '@language' => $request->getLang(),
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
    $uri = $request->getUri();

    $results = $request->getVocab()->getConceptHierarchy($uri, $request->getLang());
    if ($results === NULL)
      return $this->return_error('404', 'Not Found', "Could not find concept <$uri>");
    
    if ($request->getVocab()->getShowHierarchy()) {
      $scheme = $request->getQueryParam('scheme') ? $request->getQueryParam('scheme') : $request->getVocab()->getDefaultConceptScheme();

      /* encode the results in a JSON-LD compatible array */
      $topconcepts = $request->getVocab()->getTopConcepts($scheme, $request->getLang());
      foreach ($topconcepts as $top) {
        if (!isset($results[$top['uri']])) {
          $results[$top['uri']] = array('uri'=>$top['uri'], 'top'=>$scheme, 'prefLabel'=>$top['label'], 'hasChildren'=>$top['hasChildren']);
          if (isset($top['notation']))
            $results[$top['uri']]['notation'] = $top['notation'];
        }
      }
    }

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'onki' => 'http://schema.onki.fi/onki#',
            'uri' => '@id',
            'type' => '@type',
            'prefLabel' => 'skos:prefLabel',
            'notation' => 'skos:notation',
            'narrower' => array('@id'=>'skos:narrower','@type'=>'@id'),
            'broader' => array('@id'=>'skos:broader','@type'=>'@id'),
            'broaderTransitive' => array('@id'=>'skos:broaderTransitive','@container'=>'@index'),
            'top' => array('@id'=>'skos:topConceptOf','@type'=>'@id'),
            'hasChildren' => 'onki:hasChildren',
            '@language' => $request->getLang(),
        ),
        'uri' => $uri,
        'broaderTransitive' => $results,
    );

    return $this->return_json($ret);
  }
  
  /**
   * Used for querying group hierarchy for the sidebar group view.
   * @param Request $request
   * @return object json-ld wrapped hierarchical concept uris and labels.
   */
  public function groups($request)
  {
    $results = $request->getVocab()->listConceptGroups($request->getLang());

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'onki' => 'http://schema.onki.fi/onki#',
            'uri' => '@id',
            'type' => '@type',
            'prefLabel' => 'skos:prefLabel',
            'groups' => 'onki:hasGroup',
            'childGroups' => array('@id'=>'skos:member','@type'=>'@id'),
            'hasMembers' => 'onki:hasMembers',
            '@language' => $request->getLang(),
        ),
        'uri' => '',
        'groups' => $results,
    );

    return $this->return_json($ret);
  }
  
  /**
   * Used for querying member relations for a group.
   * @param Request $request
   * @return object json-ld wrapped narrower concept uris and labels.
   */
  public function groupMembers($request)
  {
    $uri = $request->getUri();

    $children = $request->getVocab()->listConceptGroupContents($uri, $request->getLang());
    if ($children === NULL)
      return $this->return_error('404', 'Not Found', "Could not find group <$uri>");

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'uri' => '@id',
            'type' => '@type',
            'prefLabel' => 'skos:prefLabel',
            'members' => 'skos:member',
            '@language' => $request->getLang(),
        ),
        'uri' => $uri,
        'members' => $children,
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
    $uri = $request->getUri();

    $children = $request->getVocab()->getConceptChildren($uri, $request->getLang());
    if ($children === NULL)
      return $this->return_error('404', 'Not Found', "Could not find concept <$uri>");

    $ret = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'uri' => '@id',
            'type' => '@type',
            'prefLabel' => 'skos:prefLabel',
            'narrower' => 'skos:narrower',
            'notation' => 'skos:notation',
            'hasChildren' => 'onki:hasChildren',
            '@language' => $request->getLang(),
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
    $uri = $request->getUri();

    $results = array();
    $related = $request->getVocab()->getConceptRelateds($uri, $request->getLang());
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
            '@language' => $request->getLang(),
        ),
        'uri' => $uri,
        'related' => $results,
    );

    return $this->return_json($ret);
  }
}
