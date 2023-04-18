<?php

/**
 * RestController is responsible for handling all the requests directed to the /rest address.
 */
class RestController extends Controller
{
    /* supported MIME types that can be used to return RDF data */
    public const SUPPORTED_FORMATS = 'application/rdf+xml text/turtle application/ld+json application/json application/marcxml+xml';
    /* context array template */
    private $context = array(
        '@context' => array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'uri' => '@id',
            'type' => '@type',
        ),
    );

    /**
     * Handles json encoding, adding the content type headers and optional callback function.
     * @param array $data the data to be returned.
     */
    private function returnJson($data)
    {
        // wrap with JSONP callback if requested
        if (filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_FULL_SPECIAL_CHARS)) {
            header("Content-type: application/javascript; charset=utf-8");
            echo filter_input(INPUT_GET, 'callback', FILTER_UNSAFE_RAW) . "(" . json_encode($data) . ");";
            return;
        }

        // otherwise negotiate suitable format for the response and return that
        $negotiator = new \Negotiation\Negotiator();
        $priorities = array('application/json', 'application/ld+json');
        $best = filter_input(INPUT_SERVER, 'HTTP_ACCEPT', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ? $negotiator->getBest(filter_input(INPUT_SERVER, 'HTTP_ACCEPT', FILTER_SANITIZE_FULL_SPECIAL_CHARS), $priorities) : null;
        $format = ($best !== null) ? $best->getValue() : $priorities[0];
        header("Content-type: $format; charset=utf-8");
        header("Vary: Accept"); // inform caches that we made a choice based on Accept header
        echo json_encode($data);
    }

    /**
     * Parses and returns the limit parameter. Returns and error if the parameter is missing.
     */
    private function parseLimit()
    {
        $limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT) ? filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT) : $this->model->getConfig()->getDefaultTransitiveLimit();
        if ($limit <= 0) {
            return $this->returnError(400, "Bad Request", "Invalid limit parameter");
        }

        return $limit;
    }


/** Global REST methods **/

    /**
     * Returns all the vocabularies available on the server in a json object.
     */
    public function vocabularies($request)
    {
        if (!$request->getLang()) {
            return $this->returnError(400, "Bad Request", "lang parameter missing");
        }

        $this->setLanguageProperties($request->getLang());

        $vocabs = array();
        foreach ($this->model->getVocabularies() as $voc) {
            $vocabs[$voc->getId()] = $voc->getConfig()->getTitle($request->getLang());
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
                'title' => array('@id' => 'rdfs:label', '@language' => $request->getLang()),
                'vocabularies' => 'onki:hasVocabulary',
                'id' => 'onki:vocabularyIdentifier',
                'uri' => '@id',
                '@base' => $this->getBaseHref() . "rest/v1/vocabularies",
            ),
            'uri' => '',
            'vocabularies' => $results,
        );

        return $this->returnJson($ret);
    }

    private function constructSearchParameters($request)
    {
        $parameters = new ConceptSearchParameters($request, $this->model->getConfig(), true);

        $vocabs = $request->getQueryParam('vocab'); # optional
        // convert to vocids array to support multi-vocabulary search
        $vocids = ($vocabs !== null && $vocabs !== '') ? explode(' ', $vocabs) : array();
        $vocabObjects = array();
        foreach($vocids as $vocid) {
            $vocabObjects[] = $this->model->getVocabulary($vocid);
        }
        $parameters->setVocabularies($vocabObjects);
        return $parameters;
    }

    private function transformSearchResults($request, $results, $parameters)
    {
        // before serializing to JSON, get rid of the Vocabulary object that came with each resource
        foreach ($results as &$res) {
            unset($res['voc']);
        }

        $context = array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'isothes' => 'http://purl.org/iso25964/skos-thes#',
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
        );
        foreach ($parameters->getAdditionalFields() as $field) {

            // Quick-and-dirty compactification
            $context[$field] = 'skos:' . $field;
            foreach ($results as &$result) {
                foreach ($result as $k => $v) {
                    if ($k == 'skos:' . $field) {
                        $result[$field] = $v;
                        unset($result['skos:' . $field]);
                    }
                }
            }
        }

        $ret = array(
            '@context' => $context,
            'uri' => '',
            'results' => $results,
        );

        if (isset($results[0]['prefLabels'])) {
            $ret['@context']['prefLabels'] = array('@id' => 'skos:prefLabel', '@container' => '@language');
        }

        if ($request->getQueryParam('labellang')) {
            $ret['@context']['@language'] = $request->getQueryParam('labellang');
        } elseif ($request->getQueryParam('lang')) {
            $ret['@context']['@language'] = $request->getQueryParam('lang');
        }
        return $ret;
    }

    /**
     * Performs the search function calls. And wraps the result in a json-ld object.
     * @param Request $request
     */
    public function search(Request $request): void
    {
        $maxhits = $request->getQueryParam('maxhits');
        $offset = $request->getQueryParam('offset');
        $term = $request->getQueryParamRaw('query');

        if ((!isset($term) || strlen(trim($term)) === 0) && (!$request->getQueryParam('group') && !$request->getQueryParam('parent'))) {
            $this->returnError(400, "Bad Request", "query parameter missing");
            return;
        }
        if ($maxhits && (!is_numeric($maxhits) || $maxhits <= 0)) {
            $this->returnError(400, "Bad Request", "maxhits parameter is invalid");
            return;
        }
        if ($offset && (!is_numeric($offset) || $offset < 0)) {
            $this->returnError(400, "Bad Request", "offset parameter is invalid");
            return;
        }

        $parameters = $this->constructSearchParameters($request);
        $results = $this->model->searchConcepts($parameters);
        $ret = $this->transformSearchResults($request, $results, $parameters);

        $this->returnJson($ret);
    }

/** Vocabulary-specific methods **/

    /**
     * Loads the vocabulary metadata. And wraps the result in a json-ld object.
     * @param Request $request
     */
    public function vocabularyInformation($request)
    {
        $vocab = $request->getVocab();
        if ($this->notModified($vocab)) {
            return null;
        }

        /* encode the results in a JSON-LD compatible array */
        $conceptschemes = array();
        foreach ($vocab->getConceptSchemes($request->getLang()) as $uri => $csdata) {
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
                'conceptschemes' => 'onki:hasConceptScheme',
                'id' => 'onki:vocabularyIdentifier',
                'defaultLanguage' => 'onki:defaultLanguage',
                'languages' => 'onki:language',
                'label' => 'rdfs:label',
                'prefLabel' => 'skos:prefLabel',
                'title' => 'dct:title',
                '@language' => $request->getLang(),
                '@base' => $this->getBaseHref() . "rest/v1/" . $vocab->getId() . "/",
            ),
            'uri' => '',
            'id' => $vocab->getId(),
            'marcSource' => $vocab->getConfig()->getMarcSourceCode($request->getLang()),
            'title' => $vocab->getConfig()->getTitle($request->getLang()),
            'defaultLanguage' => $vocab->getConfig()->getDefaultLanguage(),
            'languages' => array_values($vocab->getConfig()->getLanguages()),
            'conceptschemes' => $conceptschemes,
        );

        if ($vocab->getConfig()->getTypes($request->getLang())) {
            $ret['type'] = $vocab->getConfig()->getTypes($request->getLang());
        }

        return $this->returnJson($ret);
    }

    /**
     * Loads the vocabulary metadata. And wraps the result in a json-ld object.
     * @param Request $request
     */
    public function vocabularyStatistics($request)
    {
        if ($this->notModified($request->getVocab())) {
            return null;
        }
        $this->setLanguageProperties($request->getLang());
        $arrayClass = $request->getVocab()->getConfig()->getArrayClassURI();
        $groupClass = $request->getVocab()->getConfig()->getGroupClassURI();
        $queryLang = $request->getQueryParam('lang') ?? $request->getLang();
        $vocabStats = $request->getVocab()->getStatistics($queryLang, $arrayClass, $groupClass);
        $types = array('http://www.w3.org/2004/02/skos/core#Concept', 'http://www.w3.org/2004/02/skos/core#Collection', $arrayClass, $groupClass);
        $subTypes = array();
        foreach ($vocabStats as $subtype) {
            if (!in_array($subtype['type'], $types)) {
                $subTypes[] = $subtype;
            }
        }

        /* encode the results in a JSON-LD compatible array */
        $ret = array(
            '@context' => array(
                'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
                'skos' => 'http://www.w3.org/2004/02/skos/core#',
                'void' => 'http://rdfs.org/ns/void#',
                'onki' => 'http://schema.onki.fi/onki#',
                'uri' => '@id',
                'id' => 'onki:vocabularyIdentifier',
                'concepts' => 'void:classPartition',
                'label' => 'rdfs:label',
                'class' => array('@id' => 'void:class', '@type' => '@id'),
                'subTypes' => array('@id' => 'void:class', '@type' => '@id'),
                'count' => 'void:entities',
                '@language' => $request->getLang(),
                '@base' => $this->getBaseHref() . "rest/v1/" . $request->getVocab()->getId() . "/",
            ),
            'uri' => '',
            'id' => $request->getVocab()->getId(),
            'title' => $request->getVocab()->getConfig()->getTitle(),
            'concepts' => array(
                'class' => 'http://www.w3.org/2004/02/skos/core#Concept',
                'label' => gettext('skos:Concept'),
                'count' => isset($vocabStats['http://www.w3.org/2004/02/skos/core#Concept']) ? $vocabStats['http://www.w3.org/2004/02/skos/core#Concept']['count'] : 0,
                'deprecatedCount' => isset($vocabStats['http://www.w3.org/2004/02/skos/core#Concept']) ? $vocabStats['http://www.w3.org/2004/02/skos/core#Concept']['deprecatedCount'] : 0,
            ),
            'subTypes' => $subTypes,
        );

        if (isset($vocabStats['http://www.w3.org/2004/02/skos/core#Collection'])) {
            $ret['conceptGroups'] = array(
                'class' => 'http://www.w3.org/2004/02/skos/core#Collection',
                'label' => gettext('skos:Collection'),
                'count' => $vocabStats['http://www.w3.org/2004/02/skos/core#Collection']['count'],
                'deprecatedCount' => $vocabStats['http://www.w3.org/2004/02/skos/core#Collection']['deprecatedCount'],
            );

        } elseif (isset($vocabStats[$groupClass])) {
            $ret['conceptGroups'] = array(
                'class' => $groupClass,
                'label' => isset($vocabStats[$groupClass]['label']) ? $vocabStats[$groupClass]['label'] : gettext(EasyRdf\RdfNamespace::shorten($groupClass)),
                'count' => $vocabStats[$groupClass]['count'],
                'deprecatedCount' => $vocabStats[$groupClass]['deprecatedCount'],
            );
        } elseif (isset($vocabStats[$arrayClass])) {
            $ret['arrays'] = array(
                'class' => $arrayClass,
                'label' => isset($vocabStats[$arrayClass]['label']) ? $vocabStats[$arrayClass]['label'] : gettext(EasyRdf\RdfNamespace::shorten($arrayClass)),
                'count' => $vocabStats[$arrayClass]['count'],
                'deprecatedCount' => $vocabStats[$arrayClass]['deprecatedCount'],
            );
        }

        return $this->returnJson($ret);
    }

    /**
     * Loads the vocabulary metadata. And wraps the result in a json-ld object.
     * @param Request $request
     */
    public function labelStatistics($request)
    {
        if ($this->notModified($request->getVocab())) {
            return null;
        }
        $lang = $request->getLang();
        $this->setLanguageProperties($request->getLang());
        $vocabStats = $request->getVocab()->getLabelStatistics();

        /* encode the results in a JSON-LD compatible array */
        $counts = array();
        foreach ($vocabStats['terms'] as $proplang => $properties) {
            $langdata = array('language' => $proplang);
            if ($lang) {
                $langdata['literal'] = Punic\Language::getName($proplang, $lang);
            }

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
                '@base' => $this->getBaseHref() . "rest/v1/" . $request->getVocab()->getId() . "/",
            ),
            'uri' => '',
            'id' => $request->getVocab()->getId(),
            'title' => $request->getVocab()->getConfig()->getTitle($lang),
            'languages' => $counts,
        );

        if ($lang) {
            $ret['@context']['literal'] = array('@id' => 'rdfs:label', '@language' => $lang);
        }

        return $this->returnJson($ret);
    }

    /**
     * Loads the vocabulary type metadata. And wraps the result in a json-ld object.
     * @param Request $request
     */
    public function types($request)
    {
        $vocid = $request->getVocab() ? $request->getVocab()->getId() : null;
        if ($vocid === null && !$request->getLang()) {
            return $this->returnError(400, "Bad Request", "lang parameter missing");
        }
        if ($this->notModified($request->getVocab())) {
            return null;
        }

        $this->setLanguageProperties($request->getLang());

        $queriedtypes = $this->model->getTypes($vocid, $request->getLang());

        $types = array();

        /* encode the results in a JSON-LD compatible array */
        foreach ($queriedtypes as $uri => $typedata) {
            $type = array_merge(array('uri' => $uri), $typedata);
            $types[] = $type;
        }

        $base = $request->getVocab() ? $this->getBaseHref() . "rest/v1/" . $request->getVocab()->getId() . "/" : $this->getBaseHref() . "rest/v1/";

        $ret = array_merge_recursive(
            $this->context,
            array(
            '@context' => array(
                'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
                'onki' => 'http://schema.onki.fi/onki#',
                'label' => 'rdfs:label',
                'superclass' => array('@id' => 'rdfs:subClassOf', '@type' => '@id'),
                'types' => 'onki:hasType',
                '@language' => $request->getLang(),
                '@base' => $base,
            ),
            'uri' => '',
            'types' => $types)
        );

        return $this->returnJson($ret);
    }

    private function findLookupHits($results, $label, $lang)
    {
        $hits = array();
        // case 1: exact match on preferred label
        foreach ($results as $res) {
            if (isset($res['prefLabel']) && $res['prefLabel'] == $label) {
                $hits[] = $res;
            }
        }
        if (sizeof($hits) > 0) {
            return $hits;
        }

        // case 2: case-insensitive match on preferred label
        foreach ($results as $res) {
            if (isset($res['prefLabel']) && strtolower($res['prefLabel']) == strtolower($label)) {
                $hits[] = $res;
            }
        }
        if (sizeof($hits) > 0) {
            return $hits;
        }

        if ($lang === null) {
            // case 1A: exact match on preferred label in any language
            foreach ($results as $res) {
                if (isset($res['matchedPrefLabel']) && $res['matchedPrefLabel']  == $label) {
                    $res['prefLabel'] = $res['matchedPrefLabel'];
                    unset($res['matchedPrefLabel']);
                    $hits[] = $res;
                }
            }
            if (sizeof($hits) > 0) {
                return $hits;
            }

            // case 2A: case-insensitive match on preferred label in any language
            foreach ($results as $res) {
                if (isset($res['matchedPrefLabel']) && strtolower($res['matchedPrefLabel']) == strtolower($label)) {
                    $res['prefLabel'] = $res['matchedPrefLabel'];
                    unset($res['matchedPrefLabel']);
                    $hits[] = $res;
                }
            }
            if (sizeof($hits) > 0) {
                return $hits;
            }
        }

        // case 3: exact match on alternate label
        foreach ($results as $res) {
            if (isset($res['altLabel']) && $res['altLabel'] == $label) {
                $hits[] = $res;
            }
        }
        if (sizeof($hits) > 0) {
            return $hits;
        }


        // case 4: case-insensitive match on alternate label
        foreach ($results as $res) {
            if (isset($res['altLabel']) && strtolower($res['altLabel']) == strtolower($label)) {
                $hits[] = $res;
            }
        }
        if (sizeof($hits) > 0) {
            return $hits;
        }

        return $hits;
    }

    private function transformLookupResults($lang, $hits)
    {
        if (sizeof($hits) == 0) {
            // no matches found
            return;
        }

        // found matches, getting rid of Vocabulary objects
        foreach ($hits as &$res) {
            unset($res['voc']);
        }

        $ret = array_merge_recursive(
            $this->context,
            array(
            '@context' => array('onki' => 'http://schema.onki.fi/onki#', 'results' => array('@id' => 'onki:results'), 'prefLabel' => 'skos:prefLabel', 'altLabel' => 'skos:altLabel', 'hiddenLabel' => 'skos:hiddenLabel'),
            'result' => $hits)
        );

        if ($lang) {
            $ret['@context']['@language'] = $lang;
        }

        return $ret;
    }

    /**
     * Used for finding terms by their exact prefLabel. Wraps the result in a json-ld object.
     * @param Request $request
     */
    public function lookup($request)
    {
        $label = $request->getQueryParamRaw('label');
        if (!$label) {
            return $this->returnError(400, "Bad Request", "label parameter missing");
        }

        $lang = $request->getQueryParam('lang');
        $parameters = new ConceptSearchParameters($request, $this->model->getConfig(), true);
        $results = $this->model->searchConcepts($parameters);
        $hits = $this->findLookupHits($results, $label, $lang);
        $ret = $this->transformLookupResults($lang, $hits);
        if ($ret === null) {
            return $this->returnError(404, 'Not Found', "Could not find label '$label'");
        }
        return $this->returnJson($ret);
    }

    /**
     * Queries the top concepts of a vocabulary and wraps the results in a json-ld object.
     * @param Request $request
     * @return object json-ld object
     */
    public function topConcepts($request)
    {
        $vocab = $request->getVocab();
        if ($this->notModified($vocab)) {
            return null;
        }
        $scheme = $request->getQueryParam('scheme');
        if (!$scheme) {
            $scheme = $vocab->getConfig()->showConceptSchemesInHierarchy() ? array_keys($vocab->getConceptSchemes()) : $vocab->getDefaultConceptScheme();
        }

        /* encode the results in a JSON-LD compatible array */
        $topconcepts = $vocab->getTopConcepts($scheme, $request->getLang());

        $ret = array_merge_recursive(
            $this->context,
            array(
            '@context' => array('onki' => 'http://schema.onki.fi/onki#', 'topconcepts' => 'skos:hasTopConcept', 'notation' => 'skos:notation', 'label' => 'skos:prefLabel', '@language' => $request->getLang()),
            'uri' => $scheme,
            'topconcepts' => $topconcepts)
        );

        return $this->returnJson($ret);
    }

    private function redirectToVocabData($request)
    {
        $urls = $request->getVocab()->getConfig()->getDataURLs();
        if (sizeof($urls) == 0) {
            $vocid = $request->getVocab()->getId();
            return $this->returnError('404', 'Not Found', "No download source URL known for vocabulary $vocid");
        }

        $format = $this->negotiateFormat(array_keys($urls), $request->getServerConstant('HTTP_ACCEPT'), $request->getQueryParam('format'));
        if (!$format) {
            return $this->returnError(406, 'Not Acceptable', "Unsupported format. Supported MIME types are: " . implode(' ', array_keys($urls)));
        }
        if (is_array($urls[$format])) {
            $arr = $urls[$format];
            $dataLang = $request->getLang();
            if (isset($arr[$dataLang])) {
                header("Location: " . $arr[$dataLang]);
            } else {
                $vocid = $request->getVocab()->getId();
                return $this->returnError('404', 'Not Found', "No download source URL known for vocabulary $vocid in language $dataLang");
            }
        } else {
            header("Location: " . $urls[$format]);
        }
    }

    private function returnDataResults($results, $format)
    {
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
                'exactMatch' => 'skos:exactMatch',
                'closeMatch' => 'skos:closeMatch',
                'broadMatch' => 'skos:broadMatch',
                'narrowMatch' => 'skos:narrowMatch',
                'relatedMatch' => 'skos:relatedMatch',
            );
            $compactJsonLD = \ML\JsonLD\JsonLD::compact($results, json_encode($context));
            $results = \ML\JsonLD\JsonLD::toString($compactJsonLD);
        }

        header("Content-type: $format; charset=utf-8");
        echo $results;
    }

    /**
     * Download a concept as json-ld or redirect to download the whole vocabulary.
     * @param Request $request
     * @return object json-ld formatted concept.
     */
    public function data($request)
    {
        $vocab = $request->getVocab();
        if ($this->notModified($request->getVocab())) {
            return null;
        }

        if ($request->getUri()) {
            $uri = $request->getUri();
        } elseif ($vocab !== null) { // whole vocabulary - redirect to download URL
            return $this->redirectToVocabData($request);
        } else {
            return $this->returnError(400, 'Bad Request', "uri parameter missing");
        }

        $format = $this->negotiateFormat(explode(' ', self::SUPPORTED_FORMATS), $request->getServerConstant('HTTP_ACCEPT'), $request->getQueryParam('format'));
        if (!$format) {
            return $this->returnError(406, 'Not Acceptable', "Unsupported format. Supported MIME types are: " . self::SUPPORTED_FORMATS);
        }

        $vocid = $vocab ? $vocab->getId() : null;
        $results = $this->model->getRDF($vocid, $uri, $format);
        if (empty($results)) {
            return $this->returnError(404, 'Bad Request', "no concept found with given uri");
        }
        return $this->returnDataResults($results, $format);
    }

    /**
     * Get the mappings associated with a concept, enriched with labels and notations.
     * Returns a JSKOS-compatible JSON object.
     * @param Request $request
     * @throws Exception if the vocabulary ID is not found in configuration
     */
    public function mappings(Request $request)
    {
        $this->setLanguageProperties($request->getLang());
        $vocab = $request->getVocab();
        if ($this->notModified($vocab)) {
            return null;
        }

        $uri = $request->getUri();
        if (!$uri) {
            return $this->returnError(400, 'Bad Request', "uri parameter missing");
        }

        $queryExVocabs = $request->getQueryParamBoolean('external', true);

        $results = $vocab->getConceptInfo($uri, $request->getContentLang());
        if (empty($results)) {
            return $this->returnError(404, 'Bad Request', "no concept found with given uri");
        }

        $concept = $results[0];

        $mappings = [];
        foreach ($concept->getMappingProperties() as $mappingProperty) {
            foreach ($mappingProperty->getValues() as $mappingPropertyValue) {
                $hrefLink = $this->linkUrlFilter($mappingPropertyValue->getUri(), $mappingPropertyValue->getExVocab(), $request->getLang(), 'page', $request->getContentLang());
                $mappings[] = $mappingPropertyValue->asJskos($queryExVocabs, $request->getLang(), $hrefLink);
            }
        }

        $ret = array(
            'mappings' => $mappings,
            'graph' => $concept->dumpJsonLd()
        );

        return $this->returnJson($ret);
    }

    /**
     * Used for querying labels for a uri.
     * @param Request $request
     * @return object json-ld wrapped labels.
     */
    public function label($request)
    {
        if (!$request->getUri()) {
            return $this->returnError(400, "Bad Request", "uri parameter missing");
        }

        if ($this->notModified($request->getVocab())) {
            return null;
        }

        $vocab = $request->getVocab();
        if ($vocab === null) {
            $vocab = $this->model->guessVocabularyFromUri($request->getUri());
        }
        if ($vocab === null) {
            return $this->returnError('404', 'Not Found', "Could not find concept <{$request->getUri()}>");
        }

        $labelResults = $vocab->getAllConceptLabels($request->getUri(), $request->getLang());
        if ($labelResults === null) {
            return $this->returnError('404', 'Not Found', "Could not find concept <{$request->getUri()}>");
        }

        // there should be only one preferred label so no need for an array
        if (array_key_exists('prefLabel', $labelResults)) {
            $labelResults['prefLabel'] = $labelResults['prefLabel'][0];
        }

        $ret = array_merge_recursive(
            $this->context,
            array('@context' => array('prefLabel' => 'skos:prefLabel', 'altLabel' => 'skos:altLabel', 'hiddenLabel' => 'skos:hiddenLabel', '@language' => $request->getLang()),
                                    'uri' => $request->getUri()),
            $labelResults
        );

        return $this->returnJson($ret);
    }

    /**
     * Query for the available letters in the alphabetical index.
     * @param Request $request
     * @return object JSON-LD wrapped list of letters
     */

    public function indexLetters($request)
    {
        $this->setLanguageProperties($request->getLang());
        $letters = $request->getVocab()->getAlphabet($request->getLang());

        $ret = array_merge_recursive(
            $this->context,
            array(
            '@context' => array(
                'indexLetters' => array(
                    '@id' => 'skosmos:indexLetters',
                    '@container' => '@list',
                    '@language' => $request->getLang()
                )
            ),
            'uri' => '',
            'indexLetters' => $letters)
        );
        return $this->returnJson($ret);
    }

    /**
     * Query for the concepts with terms starting with a given letter in the
     * alphabetical index.
     * @param Request $request
     * @return object JSON-LD wrapped list of terms/concepts
     */

    public function indexConcepts($letter, $request)
    {
        $this->setLanguageProperties($request->getLang());

        $offset_param = $request->getQueryParam('offset');
        $offset = (is_numeric($offset_param) && $offset_param >= 0) ? $offset_param : 0;
        $limit_param = $request->getQueryParam('limit');
        $limit = (is_numeric($limit_param) && $limit_param >= 0) ? $limit_param : 0;

        $concepts = $request->getVocab()->searchConceptsAlphabetical($letter, $limit, $offset, $request->getLang());

        $ret = array_merge_recursive(
            $this->context,
            array(
            '@context' => array(
                'indexConcepts' => array(
                    '@id' => 'skosmos:indexConcepts',
                    '@container' => '@list'
                )
            ),
            'uri' => '',
            'indexConcepts' => $concepts)
        );
        return $this->returnJson($ret);
    }

    private function transformPropertyResults($uri, $lang, $objects, $propname, $propuri)
    {
        $results = array();
        foreach ($objects as $objuri => $vals) {
            $results[] = array('uri' => $objuri, 'prefLabel' => $vals['label']);
        }

        return array_merge_recursive(
            $this->context,
            array(
            '@context' => array('prefLabel' => 'skos:prefLabel', $propname => $propuri, '@language' => $lang),
            'uri' => $uri,
            $propname => $results)
        );
    }

    private function transformTransitivePropertyResults($uri, $lang, $objects, $tpropname, $tpropuri, $dpropname, $dpropuri)
    {
        $results = array();
        foreach ($objects as $objuri => $vals) {
            $result = array('uri' => $objuri, 'prefLabel' => $vals['label']);
            if (isset($vals['direct'])) {
                $result[$dpropname] = $vals['direct'];
            }
            $results[$objuri] = $result;
        }

        return array_merge_recursive(
            $this->context,
            array(
            '@context' => array('prefLabel' => 'skos:prefLabel', $dpropname => array('@id' => $dpropuri, '@type' => '@id'), $tpropname => array('@id' => $tpropuri, '@container' => '@index'), '@language' => $lang),
            'uri' => $uri,
            $tpropname => $results)
        );
    }

    /**
     * Used for querying broader relations for a concept.
     * @param Request $request
     * @return object json-ld wrapped broader concept uris and labels.
     */
    public function broader($request)
    {
        if ($this->notModified($request->getVocab())) {
            return null;
        }
        $broaders = $request->getVocab()->getConceptBroaders($request->getUri(), $request->getLang());
        if ($broaders === null) {
            return $this->returnError('404', 'Not Found', "Could not find concept <{$request->getUri()}>");
        }
        $ret = $this->transformPropertyResults($request->getUri(), $request->getLang(), $broaders, "broader", "skos:broader");
        return $this->returnJson($ret);
    }

    /**
     * Used for querying broader transitive relations for a concept.
     * @param Request $request
     * @return object json-ld wrapped broader transitive concept uris and labels.
     */
    public function broaderTransitive($request)
    {
        if ($this->notModified($request->getVocab())) {
            return null;
        }
        $broaders = $request->getVocab()->getConceptTransitiveBroaders($request->getUri(), $this->parseLimit(), false, $request->getLang());
        if (empty($broaders)) {
            return $this->returnError('404', 'Not Found', "Could not find concept <{$request->getUri()}>");
        }
        $ret = $this->transformTransitivePropertyResults($request->getUri(), $request->getLang(), $broaders, "broaderTransitive", "skos:broaderTransitive", "broader", "skos:broader");
        return $this->returnJson($ret);
    }

    /**
     * Used for querying narrower relations for a concept.
     * @param Request $request
     * @return object json-ld wrapped narrower concept uris and labels.
     */
    public function narrower($request)
    {
        if ($this->notModified($request->getVocab())) {
            return null;
        }
        $narrowers = $request->getVocab()->getConceptNarrowers($request->getUri(), $request->getLang());
        if ($narrowers === null) {
            return $this->returnError('404', 'Not Found', "Could not find concept <{$request->getUri()}>");
        }
        $ret = $this->transformPropertyResults($request->getUri(), $request->getLang(), $narrowers, "narrower", "skos:narrower");
        return $this->returnJson($ret);
    }

    /**
     * Used for querying narrower transitive relations for a concept.
     * @param Request $request
     * @return object json-ld wrapped narrower transitive concept uris and labels.
     */
    public function narrowerTransitive($request)
    {
        if ($this->notModified($request->getVocab())) {
            return null;
        }
        $narrowers = $request->getVocab()->getConceptTransitiveNarrowers($request->getUri(), $this->parseLimit(), $request->getLang());
        if (empty($narrowers)) {
            return $this->returnError('404', 'Not Found', "Could not find concept <{$request->getUri()}>");
        }
        $ret = $this->transformTransitivePropertyResults($request->getUri(), $request->getLang(), $narrowers, "narrowerTransitive", "skos:narrowerTransitive", "narrower", "skos:narrower");
        return $this->returnJson($ret);
    }

    /**
     * Used for querying broader transitive relations
     * and some narrowers for a concept in the hierarchy view.
     * @param Request $request
     * @return object json-ld wrapped hierarchical concept uris and labels.
     */
    public function hierarchy($request)
    {
        if ($this->notModified($request->getVocab())) {
            return null;
        }
        $results = $request->getVocab()->getConceptHierarchy($request->getUri(), $request->getLang());
        if (empty($results)) {
            return $this->returnError('404', 'Not Found', "Could not find concept <{$request->getUri()}>");
        }

        // set the "top" key from the "tops" key
        foreach ($results as $value) {
            $uri = $value['uri'];
            if (isset($value['tops'])) {
                if ($request->getVocab()->getConfig()->getMainConceptSchemeURI() != null) {
                    foreach ($results[$uri]['tops'] as $top) {
                        // if a value in 'tops' matches the main concept scheme of the vocabulary, take it
                        if ($top == $request->getVocab()->getConfig()->getMainConceptSchemeURI()) {
                            $results[$uri]['top'] = $top;
                            break;
                        }
                    }
                    // if the main concept scheme was not found, set 'top' to the first 'tops' (sorted alphabetically on the URIs)
                    if (! isset($results[$uri]['top'])) {
                        $results[$uri]['top'] = $results[$uri]['tops'][0];
                    }
                } else {
                    // no main concept scheme set on the vocab, take the first value of 'tops' (sorted alphabetically)
                    $results[$uri]['top'] = $results[$uri]['tops'][0];
                }
            }
        }

        if ($request->getVocab()->getConfig()->getShowHierarchy()) {
            $schemes = $request->getVocab()->getConceptSchemes($request->getLang());
            foreach ($schemes as $scheme) {
                if (!isset($scheme['title']) && !isset($scheme['label']) && !isset($scheme['prefLabel'])) {
                    unset($schemes[array_search($scheme, $schemes)]);
                }

            }

            /* encode the results in a JSON-LD compatible array */
            $topconcepts = $request->getVocab()->getTopConcepts(array_keys($schemes), $request->getLang());
            foreach ($topconcepts as $top) {
                if (!isset($results[$top['uri']])) {
                    $results[$top['uri']] = array('uri' => $top['uri'], 'top'=>$top['topConceptOf'], 'tops'=>array($top['topConceptOf']), 'prefLabel' => $top['label'], 'hasChildren' => $top['hasChildren']);
                    if (isset($top['notation'])) {
                        $results[$top['uri']]['notation'] = $top['notation'];
                    }

                }
            }
        }

        $ret = array_merge_recursive(
            $this->context,
            array(
            '@context' => array(
                'onki' => 'http://schema.onki.fi/onki#',
                'prefLabel' => 'skos:prefLabel',
                'notation' => 'skos:notation',
                'narrower' => array('@id' => 'skos:narrower', '@type' => '@id'),
                'broader' => array('@id' => 'skos:broader', '@type' => '@id'),
                'broaderTransitive' => array('@id' => 'skos:broaderTransitive', '@container' => '@index'),
                'top' => array('@id' => 'skos:topConceptOf', '@type' => '@id'),
                // the tops key will contain all the concept scheme values, while top (singular) contains a single value
                'tops' => array('@id' => 'skos:topConceptOf', '@type' => '@id'),
                'hasChildren' => 'onki:hasChildren',
                '@language' => $request->getLang()
            ),
            'uri' => $request->getUri(),
            'broaderTransitive' => $results)
        );

        return $this->returnJson($ret);
    }

    /**
     * Used for querying group hierarchy for the sidebar group view.
     * @param Request $request
     * @return object json-ld wrapped hierarchical concept uris and labels.
     */
    public function groups($request)
    {
        if ($this->notModified($request->getVocab())) {
            return null;
        }
        $results = $request->getVocab()->listConceptGroups($request->getLang());

        $ret = array_merge_recursive(
            $this->context,
            array(
            '@context' => array('onki' => 'http://schema.onki.fi/onki#', 'prefLabel' => 'skos:prefLabel', 'groups' => 'onki:hasGroup', 'childGroups' => array('@id' => 'skos:member', '@type' => '@id'), 'hasMembers' => 'onki:hasMembers', '@language' => $request->getLang()),
            'uri' => '',
            'groups' => $results)
        );

        return $this->returnJson($ret);
    }

    /**
     * Used for querying member relations for a group.
     * @param Request $request
     * @return object json-ld wrapped narrower concept uris and labels.
     */
    public function groupMembers($request)
    {
        if ($this->notModified($request->getVocab())) {
            return null;
        }
        $children = $request->getVocab()->listConceptGroupContents($request->getUri(), $request->getLang());
        if (empty($children)) {
            return $this->returnError('404', 'Not Found', "Could not find group <{$request->getUri()}>");
        }

        $ret = array_merge_recursive(
            $this->context,
            array(
            '@context' => array('prefLabel' => 'skos:prefLabel', 'members' => 'skos:member', '@language' => $request->getLang()),
            'uri' => $request->getUri(),
            'members' => $children)
        );

        return $this->returnJson($ret);
    }

    /**
     * Used for querying narrower relations for a concept in the hierarchy view.
     * @param Request $request
     * @return object json-ld wrapped narrower concept uris and labels.
     */
    public function children($request)
    {
        if ($this->notModified($request->getVocab())) {
            return null;
        }
        $children = $request->getVocab()->getConceptChildren($request->getUri(), $request->getLang());
        if ($children === null) {
            return $this->returnError('404', 'Not Found', "Could not find concept <{$request->getUri()}>");
        }

        $ret = array_merge_recursive(
            $this->context,
            array(
            '@context' => array('prefLabel' => 'skos:prefLabel', 'narrower' => 'skos:narrower', 'notation' => 'skos:notation', 'hasChildren' => 'onki:hasChildren', '@language' => $request->getLang()),
            'uri' => $request->getUri(),
            'narrower' => $children)
        );

        return $this->returnJson($ret);
    }

    /**
     * Used for querying narrower relations for a concept in the hierarchy view.
     * @param Request $request
     * @return object json-ld wrapped hierarchical concept uris and labels.
     */
    public function related($request)
    {
        if ($this->notModified($request->getVocab())) {
            return null;
        }
        $related = $request->getVocab()->getConceptRelateds($request->getUri(), $request->getLang());
        if ($related === null) {
            return $this->returnError('404', 'Not Found', "Could not find concept <{$request->getUri()}>");
        }
        $ret = $this->transformPropertyResults($request->getUri(), $request->getLang(), $related, "related", "skos:related");
        return $this->returnJson($ret);
    }

    /**
     * Used for querying new concepts in the vocabulary
     * @param Request $request
     * @return object json-ld wrapped list of changed concepts
     */
    public function newConcepts($request)
    {
        $offset = ($request->getQueryParam('offset') && is_numeric($request->getQueryParam('offset')) && $request->getQueryParam('offset') >= 0) ? $request->getQueryParam('offset') : 0;
        $limit = ($request->getQueryParam('limit') && is_numeric($request->getQueryParam('limit')) && $request->getQueryParam('limit') >= 0) ? $request->getQueryParam('limit') : 200;

        return $this->changedConcepts($request, 'dc:created', $offset, $limit);
    }

    /**
     * Used for querying modified concepts in the vocabulary
     * @param Request $request
     * @return object json-ld wrapped list of changed concepts
     */
    public function modifiedConcepts($request)
    {
        $offset = ($request->getQueryParam('offset') && is_numeric($request->getQueryParam('offset')) && $request->getQueryParam('offset') >= 0) ? $request->getQueryParam('offset') : 0;
        $limit = ($request->getQueryParam('limit') && is_numeric($request->getQueryParam('limit')) && $request->getQueryParam('limit') >= 0) ? $request->getQueryParam('limit') : 200;

        return $this->changedConcepts($request, 'dc:modified', $offset, $limit);
    }

    /**
     * Used for querying changed concepts in the vocabulary
     * @param Request $request
     * @param int $offset starting index offset
     * @param int $limit maximum number of concepts to return
     * @return object json-ld wrapped list of changed concepts
     */
    private function changedConcepts($request, $prop, $offset, $limit)
    {
        $changeList = $request->getVocab()->getChangeList($prop, $request->getLang(), $offset, $limit);

        $simpleChangeList = array();
        foreach($changeList as $conceptInfo) {
            if (array_key_exists('date', $conceptInfo)) {
                $concept = array(
                    'uri' => $conceptInfo['uri'],
                    'prefLabel' => $conceptInfo['prefLabel'],
                    'date' => $conceptInfo['date']->format("Y-m-d\TH:i:sO") );
                if (array_key_exists('replacedBy', $conceptInfo)) {
                    $concept['replacedBy'] = $conceptInfo['replacedBy'];
                    if (array_key_exists('replacingLabel', $conceptInfo)) {
                        $concept['replacingLabel'] = $conceptInfo['replacingLabel'];
                    }
                }
                $simpleChangeList[] = $concept;
            }
        }
        return $this->returnJson(array_merge_recursive(
            $this->context,
            array('@context' => array( '@language' => $request->getLang(),
                                                                                     'prefLabel' => 'skos:prefLabel',
                                                                                     'xsd' => 'http://www.w3.org/2001/XMLSchema#',
                                                                                     'date' => array( '@id' => 'http://purl.org/dc/terms/date', '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime') )
                                                        ),
            array('changeList' => $simpleChangeList)
        ));

    }
}
