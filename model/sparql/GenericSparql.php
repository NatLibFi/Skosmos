<?php

/**
 * Generates SPARQL queries and provides access to the SPARQL endpoint.
 */
class GenericSparql {
    /**
     * A SPARQL Client eg. an EasyRDF instance.
     * @property EasyRdf\Sparql\Client $client
     */
    protected $client;
    /**
     * Graph uri.
     * @property string $graph
     */
    protected $graph;
    /**
     * A SPARQL query graph part template.
     * @property string $graph
     */
    protected $graphClause;
    /**
     * Model instance.
     * @property Model $model
     */
    protected $model;

    /**
     * Cache used to avoid expensive shorten() calls
     * @property array $qnamecache
     */
    private $qnamecache = array();

    /**
     * Requires the following three parameters.
     * @param string $endpoint SPARQL endpoint address.
     * @param object $graph an EasyRDF SPARQL graph instance.
     * @param object $model a Model instance.
     */
    public function __construct($endpoint, $graph, $model) {
        $this->graph = $graph;
        $this->model = $model;

        // create the EasyRDF SPARQL client instance to use
        $this->initializeHttpClient();
        $this->client = new EasyRdf\Sparql\Client($endpoint);

        // set graphClause so that it can be used by all queries
        if ($this->isDefaultEndpoint()) // default endpoint; query any graph (and catch it in a variable)
        {
            $this->graphClause = "GRAPH $graph";
        } elseif ($graph) // query a specific graph
        {
            $this->graphClause = "GRAPH <$graph>";
        } else // query the default graph
        {
            $this->graphClause = "";
        }

    }
    
    /**
     * Returns prefix-definitions for a query
     *
     * @param string $query
     * @return string
    */
    protected function generateQueryPrefixes($query)
    {
        // Check for undefined prefixes
        $prefixes = '';
        foreach (EasyRdf\RdfNamespace::namespaces() as $prefix => $uri) {
            if (strpos($query, "{$prefix}:") !== false and
                strpos($query, "PREFIX {$prefix}:") === false
            ) {
                $prefixes .= "PREFIX {$prefix}: <{$uri}>\n";
            }
        }
        return $prefixes;
    }

    /**
     * Execute the SPARQL query using the SPARQL client, logging it as well.
     * @param string $query SPARQL query to perform
     * @return Result|\EasyRdf\Graph query result
     */
    protected function query($query) {
        $queryId = sprintf("%05d", rand(0, 99999));
        $logger = $this->model->getLogger();
        $logger->info("[qid $queryId] SPARQL query:\n" . $this->generateQueryPrefixes($query) . "\n$query\n");
        $starttime = microtime(true);
        $result = $this->client->query($query);
        $elapsed = intval(round((microtime(true) - $starttime) * 1000));
        if(method_exists($result, 'numRows')) {
            $numRows = $result->numRows();
            $logger->info("[qid $queryId] result: $numRows rows returned in $elapsed ms");
        } else { // graph result
            $numTriples = $result->countTriples();
            $logger->info("[qid $queryId] result: $numTriples triples returned in $elapsed ms");
        }
        return $result;
    }
    
    
    /**
     * Generates FROM clauses for the queries 
     * @param Vocabulary[]|null $vocabs
     * @return string
     */
    protected function generateFromClause($vocabs=null) {
        $graphs = array();
        $clause = '';
        if (!$vocabs) {
            return $this->graph !== '?graph' && $this->graph !== NULL ? "FROM <$this->graph>" : '';
        }
        foreach($vocabs as $vocab) {
            $graph = $vocab->getGraph();
            if (!in_array($graph, $graphs)) {
                array_push($graphs, $graph);
            }
        }
        foreach ($graphs as $graph) {
            if($graph !== NULL)
                $clause .= "FROM NAMED <$graph> "; 
        }
        return $clause;
    }

    protected function initializeHttpClient() {
        // configure the HTTP client used by EasyRdf\Sparql\Client
        $httpclient = EasyRdf\Http::getDefaultHttpClient();
        $httpclient->setConfig(array('timeout' => $this->model->getConfig()->getSparqlTimeout()));

        // if special cache control (typically no-cache) was requested by the
        // client, set the same type of cache control headers also in subsequent
        // in the SPARQL requests (this is useful for performance testing)
        // @codeCoverageIgnoreStart
        $cacheControl = filter_input(INPUT_SERVER, 'HTTP_CACHE_CONTROL', FILTER_SANITIZE_STRING);
        $pragma = filter_input(INPUT_SERVER, 'HTTP_PRAGMA', FILTER_SANITIZE_STRING);
        if ($cacheControl !== null || $pragma !== null) {
            $val = $pragma !== null ? $pragma : $cacheControl;
            $httpclient->setHeaders('Cache-Control', $val);
        }
        // @codeCoverageIgnoreEnd

        EasyRdf\Http::setDefaultHttpClient($httpclient); // actually redundant..
    }

    /**
     * Return true if this is the default SPARQL endpoint, used as the facade to query
     * all vocabularies.
     */

    protected function isDefaultEndpoint() {
        return $this->graph[0] == '?';
    }

    /**
     * Returns the graph instance
     * @return object EasyRDF graph instance.
     */
    public function getGraph() {
        return $this->graph;
    }
    
    /**
     * Shorten a URI
     * @param string $uri URI to shorten
     * @return string shortened URI, or original URI if it cannot be shortened
     */
    private function shortenUri($uri) {
        if (!array_key_exists($uri, $this->qnamecache)) {
            $res = new EasyRdf\Resource($uri);
            $qname = $res->shorten(); // returns null on failure
            $this->qnamecache[$uri] = ($qname !== null) ? $qname : $uri;
        }
        return $this->qnamecache[$uri];
    }


    /**
     * Generates the sparql query for retrieving concept and collection counts in a vocabulary.
     * @return string sparql query
     */
    private function generateCountConceptsQuery($array, $group) {
        $fcl = $this->generateFromClause();
        $optional = $array ? "UNION { ?type rdfs:subClassOf* <$array> }" : '';
        $optional .= $group ? "UNION { ?type rdfs:subClassOf* <$group> }" : '';
        $query = <<<EOQ
      SELECT (COUNT(?conc) as ?c) ?type ?typelabel $fcl WHERE {
        { ?conc a ?type .
        { ?type rdfs:subClassOf* skos:Concept . } UNION { ?type rdfs:subClassOf* skos:Collection . } $optional }
        OPTIONAL { ?type rdfs:label ?typelabel . }
      }
GROUP BY ?type ?typelabel
EOQ;
        return $query;
    }

    /**
     * Used for transforming the concept count query results.
     * @param EasyRdf\Sparql\Result $result query results to be transformed
     * @param string $lang language of labels
     * @return Array containing the label counts
     */
    private function transformCountConceptsResults($result, $lang) {
        $ret = array();
        foreach ($result as $row) {
            if (!isset($row->type)) {
                continue;
            }
            $ret[$row->type->getUri()]['type'] = $row->type->getUri();
            $ret[$row->type->getUri()]['count'] = $row->c->getValue();
            if (isset($row->typelabel) && $row->typelabel->getLang() === $lang) {
                $ret[$row->type->getUri()]['label'] = $row->typelabel->getValue();
            }

        }
        return $ret;
    }

    /**
     * Used for counting number of concepts and collections in a vocabulary.
     * @param string $lang language of labels
     * @return int number of concepts in this vocabulary
     */
    public function countConcepts($lang = null, $array = null, $group = null) {
        $query = $this->generateCountConceptsQuery($array, $group);
        $result = $this->query($query);
        return $this->transformCountConceptsResults($result, $lang);
    }

    /**
     * @param array $langs Languages to query for
     * @param string[] $props property names
     * @return string sparql query
     */
    private function generateCountLangConceptsQuery($langs, $classes, $props) {
        $gcl = $this->graphClause;
        $classes = ($classes) ? $classes : array('http://www.w3.org/2004/02/skos/core#Concept');

	$quote_string = function($val) { return "'$val'"; };
	$quoted_values = array_map($quote_string, $langs);
	$langFilter = "FILTER(?lang IN (" . implode(',', $quoted_values) . "))";

        $values = $this->formatValues('?type', $classes, 'uri');
        $valuesProp = $this->formatValues('?prop', $props, null);

        $query = <<<EOQ
SELECT ?lang ?prop
  (COUNT(?label) as ?count)
WHERE {
  $gcl {
    $values
    $valuesProp
    ?conc a ?type .
    ?conc ?prop ?label .
    BIND(LANG(?label) AS ?lang)
    $langFilter
  }
}
GROUP BY ?lang ?prop ?type
EOQ;
        return $query;
    }
	
    /**
     * Transforms the CountLangConcepts results into an array of label counts.
     * @param EasyRdf\Sparql\Result $result query results to be transformed
     * @param array $langs Languages to query for
     * @param string[] $props property names
     */
    private function transformCountLangConceptsResults($result, $langs, $props) {
        $ret = array();
        // set default count to zero; overridden below if query found labels
        foreach ($langs as $lang) {
            foreach ($props as $prop) {
                $ret[$lang][$prop] = 0;
            }
        }
        foreach ($result as $row) {
            if (isset($row->lang) && isset($row->prop) && isset($row->count)) {
                $ret[$row->lang->getValue()][$row->prop->shorten()] += 
                $row->count->getValue();
            }

        }
        ksort($ret);
        return $ret;
    }

    /**
     * Counts the number of concepts in a easyRDF graph with a specific language.
     * @param array $langs Languages to query for
     * @return Array containing count of concepts for each language and property.
     */
    public function countLangConcepts($langs, $classes = null) {
        $props = array('skos:prefLabel', 'skos:altLabel', 'skos:hiddenLabel');
        $query = $this->generateCountLangConceptsQuery($langs, $classes, $props);
        // Count the number of terms in each language
        $result = $this->query($query);
        return $this->transformCountLangConceptsResults($result, $langs, $props);
    }

    /**
     * Formats a VALUES clause (SPARQL 1.1) which states that the variable should be bound to one
     * of the constants given.
     * @param string $varname variable name, e.g. "?uri"
     * @param array $values the values
     * @param string $type type of values: "uri", "literal" or null (determines quoting style)
     */
    protected function formatValues($varname, $values, $type = null) {
        $constants = array();
        foreach ($values as $val) {
            if ($type == 'uri') {
                $val = "<$val>";
            }

            if ($type == 'literal') {
                $val = "'$val'";
            }

            $constants[] = "($val)";
        }
        $values = implode(" ", $constants);

        return "VALUES ($varname) { $values }";
    }

    /**
     * Filters multiple instances of the same vocabulary from the input array.
     * @param \Vocabulary[]|null $vocabs array of Vocabulary objects
     * @return \Vocabulary[]
     */
    private function filterDuplicateVocabs($vocabs) {
        // filtering duplicates
        $uniqueVocabs = array();
        if ($vocabs !== null && sizeof($vocabs) > 0) {
            foreach ($vocabs as $voc) {
                $uniqueVocabs[$voc->getId()] = $voc;
            }
        }

        return $uniqueVocabs;
    }

    /**
     * Generates a sparql query for one or more concept URIs
     * @param mixed $uris concept URI (string) or array of URIs
     * @param string|null $arrayClass the URI for thesaurus array class, or null if not used
     * @param \Vocabulary[]|null $vocabs array of Vocabulary objects
     * @return string sparql query
     */
    private function generateConceptInfoQuery($uris, $arrayClass, $vocabs) {
        $gcl = $this->graphClause;
        $fcl = empty($vocabs) ? '' : $this->generateFromClause($vocabs);
        $values = $this->formatValues('?uri', $uris, 'uri');
        $uniqueVocabs = $this->filterDuplicateVocabs($vocabs);
        $valuesGraph = empty($vocabs) ? $this->formatValuesGraph($uniqueVocabs) : '';

        if ($arrayClass === null) {
            $construct = $optional = "";
        } else {
            // add information that can be used to format narrower concepts by
            // the array they belong to ("milk by source animal" use case)
            $construct = "\n ?x skos:member ?o . ?x skos:prefLabel ?xl . ?x a <$arrayClass> .";
            $optional = "\n OPTIONAL {
                      ?x skos:member ?o .
                      ?x a <$arrayClass> .
                      ?x skos:prefLabel ?xl .
                      FILTER NOT EXISTS {
                        ?x skos:member ?other .
                        MINUS { ?other skos:broader ?uri }
                      }
                    }";
        }
        $query = <<<EOQ
CONSTRUCT {
 ?s ?p ?uri .
 ?sp ?uri ?op .
 ?uri ?p ?o .
 ?p rdfs:label ?proplabel .
 ?p rdfs:subPropertyOf ?pp .
 ?pp rdfs:label ?plabel .
 ?o a ?ot .
 ?o skos:prefLabel ?opl .
 ?o rdfs:label ?ol .
 ?o rdf:value ?ov .
 ?o skos:notation ?on .
 ?o ?oprop ?oval .
 ?o ?xlprop ?xlval .
 ?directgroup skos:member ?uri .
 ?parent skos:member ?group .
 ?group skos:prefLabel ?grouplabel .
 ?b1 rdf:first ?item .
 ?b1 rdf:rest ?b2 .
 ?item a ?it .
 ?item skos:prefLabel ?il .
 ?group a ?grouptype . $construct
} $fcl WHERE {
 $values
 $gcl {
  {
    ?s ?p ?uri .
    FILTER(!isBlank(?s))
    FILTER(?p != skos:inScheme)
  }
  UNION
  { ?sp ?uri ?op . }
  UNION
  {
    ?directgroup skos:member ?uri .
    ?group skos:member+ ?uri .
    ?group skos:prefLabel ?grouplabel .
    ?group a ?grouptype .
    OPTIONAL { ?parent skos:member ?group }
  }
  UNION
  {
   ?uri ?p ?o .
   OPTIONAL {
     ?o rdf:rest* ?b1 .
     ?b1 rdf:first ?item .
     ?b1 rdf:rest ?b2 .
     OPTIONAL { ?item a ?it . }
     OPTIONAL { ?item skos:prefLabel ?il . }
   }
   OPTIONAL {
     { ?p rdfs:label ?proplabel . }
     UNION
     { ?p rdfs:subPropertyOf ?pp . }
   }
   OPTIONAL {
     { ?o a ?ot . }
     UNION
     { ?o skos:prefLabel ?opl . }
     UNION
     { ?o rdfs:label ?ol . }
     UNION
     { ?o rdf:value ?ov . 
       OPTIONAL { ?o ?oprop ?oval . }
     }
     UNION
     { ?o skos:notation ?on . }
     UNION
     { ?o a skosxl:Label .
       ?o ?xlprop ?xlval }
   } $optional
  }
 }
}
$valuesGraph
EOQ;
        return $query;
    }

    /**
     * Transforms ConceptInfo query results into an array of Concept objects
     * @param EasyRdf\Graph $result query results to be transformed
     * @param array $uris concept URIs
     * @param \Vocabulary[] $vocabs array of Vocabulary object
     * @param string|null $clang content language
     * @return Concept[] array of Concept objects
     */
    private function transformConceptInfoResults($result, $uris, $vocabs, $clang) {
        $conceptArray = array();
        foreach ($uris as $index => $uri) {
            $conc = $result->resource($uri);
            $vocab = (isset($vocabs) && sizeof($vocabs) == 1) ? $vocabs[0] : $vocabs[$index];
            $conceptArray[] = new Concept($this->model, $vocab, $conc, $result, $clang);
        }
        return $conceptArray;
    }

    /**
     * Returns information (as a graph) for one or more concept URIs
     * @param mixed $uris concept URI (string) or array of URIs
     * @param string|null $arrayClass the URI for thesaurus array class, or null if not used
     * @param \Vocabulary[]|null $vocabs vocabularies to target
     * @return \EasyRdf\Graph
     */
    public function queryConceptInfoGraph($uris, $arrayClass = null, $vocabs = array()) {
        // if just a single URI is given, put it in an array regardless
        if (!is_array($uris)) {
            $uris = array($uris);
        }

        $query = $this->generateConceptInfoQuery($uris, $arrayClass, $vocabs);
        $result = $this->query($query);
        return $result;
    }

    /**
     * Returns information (as an array of Concept objects) for one or more concept URIs
     * @param mixed $uris concept URI (string) or array of URIs
     * @param string|null $arrayClass the URI for thesaurus array class, or null if not used
     * @param \Vocabulary[] $vocabs vocabularies to target
     * @param string|null $clang content language
     * @return Concept[]
     */
    public function queryConceptInfo($uris, $arrayClass = null, $vocabs = array(), $clang = null) {
        // if just a single URI is given, put it in an array regardless
        if (!is_array($uris)) {
            $uris = array($uris);
        }
        $result = $this->queryConceptInfoGraph($uris, $arrayClass, $vocabs);
        if ($result->isEmpty()) {
            return [];
        }
        return $this->transformConceptInfoResults($result, $uris, $vocabs, $clang);
    }

    /**
     * Generates the sparql query for queryTypes
     * @param string $lang
     * @return string sparql query
     */
    private function generateQueryTypesQuery($lang) {
        $fcl = $this->generateFromClause();
        $query = <<<EOQ
SELECT DISTINCT ?type ?label ?superclass $fcl
WHERE {
  {
    { BIND( skos:Concept as ?type ) }
    UNION
    { BIND( skos:Collection as ?type ) }
    UNION
    { BIND( isothes:ConceptGroup as ?type ) }
    UNION
    { BIND( isothes:ThesaurusArray as ?type ) }
    UNION
    { ?type rdfs:subClassOf/rdfs:subClassOf* skos:Concept . }
    UNION
    { ?type rdfs:subClassOf/rdfs:subClassOf* skos:Collection . }
  }
  OPTIONAL {
    ?type rdfs:label ?label .
    FILTER(langMatches(lang(?label), '$lang'))
  }
  OPTIONAL {
    ?type rdfs:subClassOf ?superclass .
  }
  FILTER EXISTS {
    ?s a ?type .
    ?s skos:prefLabel ?prefLabel .
  }
}
EOQ;
        return $query;
    }

    /**
     * Transforms the results into an array format.
     * @param EasyRdf\Sparql\Result $result
     * @return array Array with URIs (string) as key and array of (label, superclassURI) as value
     */
    private function transformQueryTypesResults($result) {
        $ret = array();
        foreach ($result as $row) {
            $type = array();
            if (isset($row->label)) {
                $type['label'] = $row->label->getValue();
            }

            if (isset($row->superclass)) {
                $type['superclass'] = $row->superclass->getUri();
            }

            $ret[$row->type->getURI()] = $type;
        }
        return $ret;
    }

    /**
     * Retrieve information about types from the endpoint
     * @param string $lang
     * @return array Array with URIs (string) as key and array of (label, superclassURI) as value
     */
    public function queryTypes($lang) {
        $query = $this->generateQueryTypesQuery($lang);
        $result = $this->query($query);
        return $this->transformQueryTypesResults($result);
    }

    /**
     * Generates the concept scheme query.
     * @param string $conceptscheme concept scheme URI
     * @return string sparql query
     */
    private function generateQueryConceptSchemeQuery($conceptscheme) {
        $fcl = $this->generateFromClause();
        $query = <<<EOQ
CONSTRUCT {
  <$conceptscheme> ?property ?value .
} $fcl WHERE {
  <$conceptscheme> ?property ?value .
  FILTER (?property != skos:hasTopConcept)
}
EOQ;
        return $query;
    }

    /**
     * Retrieves conceptScheme information from the endpoint.
     * @param string $conceptscheme concept scheme URI
     * @return EasyRDF_Graph query result graph
     */
    public function queryConceptScheme($conceptscheme) {
        $query = $this->generateQueryConceptSchemeQuery($conceptscheme);
        return $this->query($query);
    }

    /**
     * Generates the queryConceptSchemes sparql query.
     * @param string $lang language of labels
     * @return string sparql query
     */
    private function generateQueryConceptSchemesQuery($lang) {
        $fcl = $this->generateFromClause();
        $query = <<<EOQ
SELECT ?cs ?label ?preflabel ?title ?domain ?domainLabel $fcl
WHERE {
 ?cs a skos:ConceptScheme .
 OPTIONAL{
    ?cs dcterms:subject ?domain.
    ?domain skos:prefLabel ?domainLabel.
    FILTER(langMatches(lang(?domainLabel), '$lang'))
}
 OPTIONAL {
   ?cs rdfs:label ?label .
   FILTER(langMatches(lang(?label), '$lang'))
 }
 OPTIONAL {
   ?cs skos:prefLabel ?preflabel .
   FILTER(langMatches(lang(?preflabel), '$lang'))
 }
 OPTIONAL {
   { ?cs dc11:title ?title }
   UNION
   { ?cs dc:title ?title }
   FILTER(langMatches(lang(?title), '$lang'))
 }
} 
ORDER BY ?cs
EOQ;
        return $query;
    }

    /**
     * Transforms the queryConceptScheme results into an array format.
     * @param EasyRdf\Sparql\Result $result
     * @return array
     */
    private function transformQueryConceptSchemesResults($result) {
        $ret = array();
        foreach ($result as $row) {
            $conceptscheme = array();
            if (isset($row->label)) {
                $conceptscheme['label'] = $row->label->getValue();
            }

            if (isset($row->preflabel)) {
                $conceptscheme['prefLabel'] = $row->preflabel->getValue();
            }

            if (isset($row->title)) {
                $conceptscheme['title'] = $row->title->getValue();
            }
            // add dct:subject and their labels in the result
            if(isset($row->domain) && isset($row->domainLabel)){
                $conceptscheme['subject']['uri']=$row->domain->getURI();
                $conceptscheme['subject']['prefLabel']=$row->domainLabel->getValue();
            }

            $ret[$row->cs->getURI()] = $conceptscheme;
        }
        return $ret;
    }

    /**
     * return a list of skos:ConceptScheme instances in the given graph
     * @param string $lang language of labels
     * @return array Array with concept scheme URIs (string) as keys and labels (string) as values
     */
    public function queryConceptSchemes($lang) {
        $query = $this->generateQueryConceptSchemesQuery($lang);
        $result = $this->query($query);
        return $this->transformQueryConceptSchemesResults($result);
    }

    /**
     * Generate a VALUES clause for limiting the targeted graphs.
     * @param Vocabulary[]|null $vocabs the vocabularies to target 
     * @return string[] array of graph URIs
     */
    protected function getVocabGraphs($vocabs) {
        if ($vocabs === null || sizeof($vocabs) == 0) {
            // searching from all vocabularies - limit to known graphs
            $vocabs = $this->model->getVocabularies();
        }
        $graphs = array();
        foreach ($vocabs as $voc) {
            $graphs[] = $voc->getGraph();
        }
        return $graphs;
    }

    /**
     * Generate a VALUES clause for limiting the targeted graphs.
     * @param Vocabulary[]|null $vocabs array of Vocabulary objects to target
     * @return string VALUES clause, or "" if not necessary to limit
     */
    protected function formatValuesGraph($vocabs) {
        if (!$this->isDefaultEndpoint()) {
            return "";
        }
        $graphs = $this->getVocabGraphs($vocabs);
        return $this->formatValues('?graph', $graphs, 'uri');
    }

    /**
     * Generate a FILTER clause for limiting the targeted graphs.
     * @param array $vocabs array of Vocabulary objects to target
     * @return string FILTER clause, or "" if not necessary to limit
     */
    protected function formatFilterGraph($vocabs) {
        if (!$this->isDefaultEndpoint()) {
            return "";
        }
        $graphs = $this->getVocabGraphs($vocabs);
        $values = array();
        foreach ($graphs as $graph) {
          $values[] = "<$graph>";
        }
        return "FILTER (?graph IN (" . implode(',', $values) . "))";
    }

    /**
     * Formats combined limit and offset clauses for the sparql query
     * @param int $limit maximum number of hits to retrieve; 0 for unlimited
     * @param int $offset offset of results to retrieve; 0 for beginning of list
     * @return string sparql query clauses
     */
    protected function formatLimitAndOffset($limit, $offset) {
        $limit = ($limit) ? 'LIMIT ' . $limit : '';
        $offset = ($offset) ? 'OFFSET ' . $offset : '';
        // eliminating whitespace and line changes when the conditions aren't needed.
        $limitandoffset = '';
        if ($limit && $offset) {
            $limitandoffset = "\n" . $limit . "\n" . $offset;
        } elseif ($limit) {
            $limitandoffset = "\n" . $limit;
        } elseif ($offset) {
            $limitandoffset = "\n" . $offset;
        }

        return $limitandoffset;
    }

    /**
     * Formats a sparql query clause for limiting the search to specific concept types.
     * @param array $types limit search to concepts of the given type(s)
     * @return string sparql query clause
     */
    protected function formatTypes($types) {
        $typePatterns = array();
        if (!empty($types)) {
            foreach ($types as $type) {
                $unprefixed = EasyRdf\RdfNamespace::expand($type);
                $typePatterns[] = "{ ?s a <$unprefixed> }";
            }
        }

        return implode(' UNION ', $typePatterns);
    }

    /**
     * @param string $prop property to include in the result eg. 'broader' or 'narrower'
     * @return string sparql query clause
     */
    private function formatPropertyCsvClause($prop) {
        # This expression creates a CSV row containing pairs of (uri,prefLabel) values.
        # The REPLACE is performed for quotes (" -> "") so they don't break the CSV format.
        $clause = <<<EOV
(GROUP_CONCAT(DISTINCT CONCAT(
 '"', IF(isIRI(?$prop),STR(?$prop),''), '"', ',',
 '"', REPLACE(IF(BOUND(?{$prop}lab),?{$prop}lab,''), '"', '""'), '"', ',',
 '"', REPLACE(IF(isLiteral(?{$prop}),?{$prop},''), '"', '""'), '"'
); separator='\\n') as ?{$prop}s)
EOV;
        return $clause;
    }
    
    /**
     * @return string sparql query clause
     */
    private function formatPrefLabelCsvClause() {
        # This expression creates a CSV row containing pairs of (prefLabel, lang) values.
        # The REPLACE is performed for quotes (" -> "") so they don't break the CSV format.
        $clause = <<<EOV
(GROUP_CONCAT(DISTINCT CONCAT(
 '"', STR(?pref), '"', ',', '"', lang(?pref), '"'
); separator='\\n') as ?preflabels)
EOV;
        return $clause;
    }

    /**
     * @param string $lang language code of the returned labels
     * @param array|null $fields extra fields to include in the result (array of strings). (default: null = none)
     * @return string sparql query clause
     */
    protected function formatExtraFields($lang, $fields) {
        // extra variable expressions to request and extra fields to query for
        $ret = array('extravars' => '', 'extrafields' => '');

        if ($fields === null) {
            return $ret; 
        }

        if (in_array('prefLabel', $fields)) {
            $ret['extravars'] .= $this->formatPreflabelCsvClause();
            $ret['extrafields'] .= <<<EOF
OPTIONAL {
  ?s skos:prefLabel ?pref .
}
EOF;
            // removing the prefLabel from the fields since it has been handled separately
            $fields = array_diff($fields, array('prefLabel'));
        }

        foreach ($fields as $field) {
            $ret['extravars'] .= $this->formatPropertyCsvClause($field);
            $ret['extrafields'] .= <<<EOF
OPTIONAL {
  ?s skos:$field ?$field .
  FILTER(!isLiteral(?$field)||langMatches(lang(?{$field}), '$lang'))
  OPTIONAL { ?$field skos:prefLabel ?{$field}lab . FILTER(langMatches(lang(?{$field}lab), '$lang')) }
}
EOF;
        }

        return $ret;
    }

    /**
     * Generate condition for matching labels in SPARQL
     * @param string $term search term
     * @param string $searchLang language code used for matching labels (null means any language)
     * @return string sparql query snippet
     */
    protected function generateConceptSearchQueryCondition($term, $searchLang)
    {
        # use appropriate matching function depending on query type: =, strstarts, strends or full regex
        if (preg_match('/^[^\*]+$/', $term)) { // exact query
            $term = str_replace('\\', '\\\\', $term); // quote slashes
            $term = str_replace('\'', '\\\'', mb_strtolower($term, 'UTF-8')); // make lowercase and escape single quotes
            $filtercond = "LCASE(STR(?match)) = '$term'";
        } elseif (preg_match('/^[^\*]+\*$/', $term)) { // prefix query
            $term = substr($term, 0, -1); // remove the final asterisk
            $term = str_replace('\\', '\\\\', $term); // quote slashes
            $term = str_replace('\'', '\\\'', mb_strtolower($term, 'UTF-8')); // make lowercase and escape single quotes
            $filtercond = "STRSTARTS(LCASE(STR(?match)), '$term')";
        } elseif (preg_match('/^\*[^\*]+$/', $term)) { // suffix query
            $term = substr($term, 1); // remove the preceding asterisk
            $term = str_replace('\\', '\\\\', $term); // quote slashes
            $term = str_replace('\'', '\\\'', mb_strtolower($term, 'UTF-8')); // make lowercase and escape single quotes
            $filtercond = "STRENDS(LCASE(STR(?match)), '$term')";
        } else { // too complicated - have to use a regex
            # make sure regex metacharacters are not passed through
            $term = str_replace('\\', '\\\\', preg_quote($term));
            $term = str_replace('\\\\*', '.*', $term); // convert asterisk to regex syntax
            $term = str_replace('\'', '\\\'', $term); // ensure single quotes are quoted
            $filtercond = "REGEX(STR(?match), '^$term$', 'i')";
        }

        $labelcondMatch = ($searchLang) ? "&& LANGMATCHES(lang(?match), '$searchLang')" : "";
        
        return "?s ?prop ?match . FILTER ($filtercond $labelcondMatch)";
    }


    /**
     * Inner query for concepts using a search term.
     * @param string $term search term
     * @param string $lang language code of the returned labels
     * @param string $searchLang language code used for matching labels (null means any language)
     * @param string[] $props properties to target e.g. array('skos:prefLabel','skos:altLabel')
     * @param boolean $unique restrict results to unique concepts (default: false)
     * @return string sparql query
     */
    protected function generateConceptSearchQueryInner($term, $lang, $searchLang, $props, $unique, $filterGraph)
    {
        $valuesProp = $this->formatValues('?prop', $props);
        $textcond = $this->generateConceptSearchQueryCondition($term, $searchLang);

        $rawterm = str_replace(array('\\', '*', '"'), array('\\\\', '', '\"'), $term);
        // graph clause, if necessary
        $graphClause = $filterGraph != '' ? 'GRAPH ?graph' : '';

        // extra conditions for label language, if specified
        $labelcondLabel = ($lang) ? "LANGMATCHES(lang(?label), '$lang')" : "lang(?match) = '' || LANGMATCHES(lang(?label), lang(?match))";
        // if search language and UI/display language differ, must also consider case where there is no prefLabel in
        // the display language; in that case, should use the label with the same language as the matched label
        $labelcondFallback = ($searchLang != $lang) ?
          "OPTIONAL { # in case previous OPTIONAL block gives no labels\n" .
          "?s skos:prefLabel ?label . FILTER (LANGMATCHES(LANG(?label), LANG(?match))) }" : "";
          
        //  Including the labels if there is no query term given.
        if ($rawterm === '') {
          $labelClause = "?s skos:prefLabel ?label .";
          $labelClause = ($lang) ? $labelClause . " FILTER (LANGMATCHES(LANG(?label), '$lang'))" : $labelClause . "";
          return $labelClause . " BIND(?label AS ?match)";
        }

        /*
         * This query does some tricks to obtain a list of unique concepts.
         * From each match generated by the text index, a string such as
         * "1en@example" is generated, where the first character is a number
         * encoding the property and priority, then comes the language tag and
         * finally the original literal after an @ sign. Of these, the MIN
         * function is used to pick the best match for each concept. Finally,
         * the structure is unpacked to get back the original string. Phew!
         */
        $hitvar = $unique ? '(MIN(?matchstr) AS ?hit)' : '(?matchstr AS ?hit)';
        $hitgroup = $unique ? 'GROUP BY ?s ?label ?notation' : '';
         
        $query = <<<EOQ
   SELECT DISTINCT ?s ?label ?notation $hitvar
   WHERE {
    $graphClause {
     { 
     $valuesProp
     VALUES (?prop ?pri) { (skos:prefLabel 1) (skos:altLabel 3) (skos:hiddenLabel 5)}
     $textcond
     ?s ?prop ?match }
     UNION
     { ?s skos:notation "$rawterm" }
     OPTIONAL {
      ?s skos:prefLabel ?label .
      FILTER ($labelcondLabel)
     } $labelcondFallback
     BIND(IF(langMatches(LANG(?match),'$lang'), ?pri, ?pri+1) AS ?npri)
     BIND(CONCAT(STR(?npri), LANG(?match), '@', STR(?match)) AS ?matchstr)
     OPTIONAL { ?s skos:notation ?notation }
    }
    $filterGraph
   }
   $hitgroup
EOQ;

        return $query;
    }

    /**
     * Query for concepts using a search term.
     * @param array|null $fields extra fields to include in the result (array of strings). (default: null = none)
     * @param boolean $unique restrict results to unique concepts (default: false)
     * @param boolean $showDeprecated whether to include deprecated concepts in search results (default: false)
     * @param ConceptSearchParameters $params 
     * @return string sparql query
     */
    protected function generateConceptSearchQuery($fields, $unique, $params, $showDeprecated = false) {
        $vocabs = $params->getVocabs();
        $gcl = $this->graphClause;
        $fcl = empty($vocabs) ? '' : $this->generateFromClause($vocabs);
        $formattedtype = $this->formatTypes($params->getTypeLimit());
        $formattedfields = $this->formatExtraFields($params->getLang(), $fields);
        $extravars = $formattedfields['extravars'];
        $extrafields = $formattedfields['extrafields'];
        $schemes = $params->getSchemeLimit();

        // limit the search to only requested concept schemes
        $schemecond = '';
        if (!empty($schemes)) {
            $conditions = array();
            foreach($schemes as $scheme) {
                $conditions[] = "{?s skos:inScheme <$scheme>}";
            }
            $schemecond = '{'.implode(" UNION ",$conditions).'}';
        }
        $filterDeprecated="";
        //show or hide deprecated concepts
        if(!$showDeprecated){
            $filterDeprecated="FILTER NOT EXISTS { ?s owl:deprecated true }";
        }
        // extra conditions for parent and group, if specified
        $parentcond = ($params->getParentLimit()) ? "?s skos:broader+ <" . $params->getParentLimit() . "> ." : "";
        $groupcond = ($params->getGroupLimit()) ? "<" . $params->getGroupLimit() . "> skos:member ?s ." : "";
        $pgcond = $parentcond . $groupcond;

        $orderextra = $this->isDefaultEndpoint() ? $this->graph : '';

        # make VALUES clauses
        $props = array('skos:prefLabel', 'skos:altLabel');
        if ($params->getHidden()) {
            $props[] = 'skos:hiddenLabel';
        }

        //add notation into searchable data for the vocabularies which have been configured for it 
        if ($vocabs) {
            $searchByNotation = false;
            foreach ($vocabs as $vocab) {
                if ($vocab->getConfig()->searchByNotation()) {
                    $searchByNotation = true;
                }
            }
            if ($searchByNotation) {
                $props[] = 'skos:notation';
            }
        }
        $filterGraph = empty($vocabs) ? $this->formatFilterGraph($vocabs) : '';

        // remove futile asterisks from the search term
        $term = $params->getSearchTerm();
        while (strpos($term, '**') !== false) {
            $term = str_replace('**', '*', $term);
        }

        $labelpriority = <<<EOQ
  FILTER(BOUND(?s))
  BIND(STR(SUBSTR(?hit,1,1)) AS ?pri)
  BIND(IF((SUBSTR(STRBEFORE(?hit, '@'),1) != ?pri), STRLANG(STRAFTER(?hit, '@'), SUBSTR(STRBEFORE(?hit, '@'),2)), STRAFTER(?hit, '@')) AS ?match)
  BIND(IF((?pri = "1" || ?pri = "2") && ?match != ?label, ?match, ?unbound) as ?plabel)
  BIND(IF((?pri = "3" || ?pri = "4"), ?match, ?unbound) as ?alabel)
  BIND(IF((?pri = "5" || ?pri = "6"), ?match, ?unbound) as ?hlabel)
EOQ;
        $innerquery = $this->generateConceptSearchQueryInner($params->getSearchTerm(), $params->getLang(), $params->getSearchLang(), $props, $unique, $filterGraph);
        if ($params->getSearchTerm() === '*' || $params->getSearchTerm() === '') { 
          $labelpriority = ''; 
        }
        $query = <<<EOQ
SELECT DISTINCT ?s ?label ?plabel ?alabel ?hlabel ?graph ?notation (GROUP_CONCAT(DISTINCT STR(?type);separator=' ') as ?types) $extravars 
$fcl
WHERE {
 $gcl {
  {
  $innerquery
  }
  $labelpriority
  $formattedtype
  { $pgcond 
   ?s a ?type .
   $extrafields $schemecond
  }
  $filterDeprecated
 }
 $filterGraph
}
GROUP BY ?s ?match ?label ?plabel ?alabel ?hlabel ?notation ?graph
ORDER BY LCASE(STR(?match)) LANG(?match) $orderextra
EOQ;
        return $query;
    }

    /**
     * Transform a single concept search query results into the skosmos desired return format.
     * @param $row SPARQL query result row
     * @param array $vocabs array of Vocabulary objects to search; empty for global search
     * @return array query result object
     */
    private function transformConceptSearchResult($row, $vocabs, $fields)
    {
        $hit = array();
        $hit['uri'] = $row->s->getUri();

        if (isset($row->graph)) {
            $hit['graph'] = $row->graph->getUri();
        }

        foreach (explode(" ", $row->types->getValue()) as $typeuri) {
            $hit['type'][] = $this->shortenUri($typeuri);
        }

        if(!empty($fields)) {
            foreach ($fields as $prop) {
                $propname = $prop . 's';
                if (isset($row->$propname)) {
                    foreach (explode("\n", $row->$propname->getValue()) as $line) {
                        $rdata = str_getcsv($line, ',', '"', '"');
                        $propvals = array();
                        if ($rdata[0] != '') {
                            $propvals['uri'] = $rdata[0];
                        }
                        if ($rdata[1] != '') {
                            $propvals['prefLabel'] = $rdata[1];
                        }
                        if ($rdata[2] != '') {
                            $propvals = $rdata[2];
                        }

                        $hit['skos:' . $prop][] = $propvals;
                    }
                }
            }
        }

        
        if (isset($row->preflabels)) {
            foreach (explode("\n", $row->preflabels->getValue()) as $line) {
                $pref = str_getcsv($line, ',', '"', '"');
                $hit['prefLabels'][$pref[1]] = $pref[0];
            }
        }

        foreach ($vocabs as $vocab) { // looping the vocabulary objects and asking these for a localname for the concept.
            $localname = $vocab->getLocalName($hit['uri']);
            if ($localname !== $hit['uri']) { // only passing the result forward if the uri didn't boomerang right back.
                $hit['localname'] = $localname;
                break; // stopping the search when we find one that returns something valid.
            }
        }

        if (isset($row->label)) {
            $hit['prefLabel'] = $row->label->getValue();
        }

        if (isset($row->label)) {
            $hit['lang'] = $row->label->getLang();
        }

        if (isset($row->notation)) {
            $hit['notation'] = $row->notation->getValue();
        }

        if (isset($row->plabel)) {
            $hit['matchedPrefLabel'] = $row->plabel->getValue();
            $hit['lang'] = $row->plabel->getLang();
        } elseif (isset($row->alabel)) {
            $hit['altLabel'] = $row->alabel->getValue();
            $hit['lang'] = $row->alabel->getLang();
        } elseif (isset($row->hlabel)) {
            $hit['hiddenLabel'] = $row->hlabel->getValue();
            $hit['lang'] = $row->hlabel->getLang();
        }
        return $hit;
    }

    /**
     * Transform the concept search query results into the skosmos desired return format.
     * @param EasyRdf\Sparql\Result $results
     * @param array $vocabs array of Vocabulary objects to search; empty for global search
     * @return array query result object
     */
    private function transformConceptSearchResults($results, $vocabs, $fields) {
        $ret = array();

        foreach ($results as $row) {
            if (!isset($row->s)) {
                // don't break if query returns a single dummy result
                continue;
            }
            $ret[] = $this->transformConceptSearchResult($row, $vocabs, $fields);
        }
        return $ret;
    }

    /**
     * Query for concepts using a search term.
     * @param array $vocabs array of Vocabulary objects to search; empty for global search
     * @param array $fields extra fields to include in the result (array of strings). (default: null = none)
     * @param boolean $unique restrict results to unique concepts (default: false)
     * @param boolean $showDeprecated whether to include deprecated concepts in the result (default: false)
     * @param ConceptSearchParameters $params 
     * @return array query result object
     */
    public function queryConcepts($vocabs, $fields = null, $unique = false, $params, $showDeprecated = false) {
        $query = $this->generateConceptSearchQuery($fields, $unique, $params,$showDeprecated);
        $results = $this->query($query);
        return $this->transformConceptSearchResults($results, $vocabs, $fields);
    }

    /**
     * Generates sparql query clauses used for creating the alphabetical index.
     * @param string $letter the letter (or special class) to search for
     * @return array of sparql query clause strings
     */
    private function formatFilterConditions($letter, $lang) {
        $useRegex = false;

        if ($letter == '*') {
            $letter = '.*';
            $useRegex = true;
        } elseif ($letter == '0-9') {
            $letter = '[0-9].*';
            $useRegex = true;
        } elseif ($letter == '!*') {
            $letter = '[^\\\\p{L}\\\\p{N}].*';
            $useRegex = true;
        }

        # make text query clause
        $lcletter = mb_strtolower($letter, 'UTF-8'); // convert to lower case, UTF-8 safe
        if ($useRegex) {
            $filtercondLabel = $lang ? "regex(str(?label), '^$letter$', 'i') && langMatches(lang(?label), '$lang')" : "regex(str(?label), '^$letter$', 'i')";
            $filtercondALabel = $lang ? "regex(str(?alabel), '^$letter$', 'i') && langMatches(lang(?alabel), '$lang')" : "regex(str(?alabel), '^$letter$', 'i')";
        } else {
            $filtercondLabel = $lang ? "strstarts(lcase(str(?label)), '$lcletter') && langMatches(lang(?label), '$lang')" : "strstarts(lcase(str(?label)), '$lcletter')";
            $filtercondALabel = $lang ? "strstarts(lcase(str(?alabel)), '$lcletter') && langMatches(lang(?alabel), '$lang')" : "strstarts(lcase(str(?alabel)), '$lcletter')";
        }
        return array('filterpref' => $filtercondLabel, 'filteralt' => $filtercondALabel);
    }

    /**
     * Generates the sparql query used for rendering the alphabetical index.
     * @param string $letter the letter (or special class) to search for
     * @param string $lang language of labels
     * @param integer $limit limits the amount of results
     * @param integer $offset offsets the result set
     * @param array|null $classes
     * @param boolean $showDeprecated whether to include deprecated concepts in the result (default: false)
     * @return string sparql query
     */
    protected function generateAlphabeticalListQuery($letter, $lang, $limit, $offset, $classes, $showDeprecated = false) {
        $fcl = $this->generateFromClause();
        $classes = ($classes) ? $classes : array('http://www.w3.org/2004/02/skos/core#Concept');
        $values = $this->formatValues('?type', $classes, 'uri');
        $limitandoffset = $this->formatLimitAndOffset($limit, $offset);
        $conditions = $this->formatFilterConditions($letter, $lang);
        $filtercondLabel = $conditions['filterpref'];
        $filtercondALabel = $conditions['filteralt'];
        $filterDeprecated="";
        if(!$showDeprecated){
            $filterDeprecated="FILTER NOT EXISTS { ?s owl:deprecated true }";
        }
        $query = <<<EOQ
SELECT DISTINCT ?s ?label ?alabel $fcl
WHERE {
  {
    ?s skos:prefLabel ?label .
    FILTER (
      $filtercondLabel
    )
  }
  UNION
  {
    {
      ?s skos:altLabel ?alabel .
      FILTER (
        $filtercondALabel
      )
    }
    {
      ?s skos:prefLabel ?label .
      FILTER (langMatches(lang(?label), '$lang'))
    }
  }
  ?s a ?type .
  $filterDeprecated
  $values
}
ORDER BY STR(LCASE(COALESCE(?alabel, ?label))) $limitandoffset
EOQ;
        return $query;
    }

    /**
     * Transforms the alphabetical list query results into an array format.
     * @param EasyRdf\Sparql\Result $results
     * @return array
     */
    private function transformAlphabeticalListResults($results) {
        $ret = array();

        foreach ($results as $row) {
            if (!isset($row->s)) {
                continue;
            }
            // don't break if query returns a single dummy result

            $hit = array();
            $hit['uri'] = $row->s->getUri();

            $hit['localname'] = $row->s->localName();

            $hit['prefLabel'] = $row->label->getValue();
            $hit['lang'] = $row->label->getLang();

            if (isset($row->alabel)) {
                $hit['altLabel'] = $row->alabel->getValue();
                $hit['lang'] = $row->alabel->getLang();
            }

            $ret[] = $hit;
        }

        return $ret;
    }

    /**
     * Query for concepts with a term starting with the given letter. Also special classes '0-9' (digits),
     * '*!' (special characters) and '*' (everything) are accepted.
     * @param string $letter the letter (or special class) to search for
     * @param string $lang language of labels
     * @param integer $limit limits the amount of results
     * @param integer $offset offsets the result set
     * @param array $classes
     * @param boolean $showDeprecated whether to include deprecated concepts in the result (default: false)
     */
    public function queryConceptsAlphabetical($letter, $lang, $limit = null, $offset = null, $classes = null,$showDeprecated = false) {
        $query = $this->generateAlphabeticalListQuery($letter, $lang, $limit, $offset, $classes,$showDeprecated);
        $results = $this->query($query);
        return $this->transformAlphabeticalListResults($results);
    }

    /**
     * Creates the query used for finding out which letters should be displayed in the alphabetical index.
     * Note that we force the datatype of the result variable otherwise Virtuoso does not properly interpret the DISTINCT and we have duplicated results
     * @param string $lang language
     * @return string sparql query
     */
    private function generateFirstCharactersQuery($lang, $classes) {
        $fcl = $this->generateFromClause();
        $classes = (isset($classes) && sizeof($classes) > 0) ? $classes : array('http://www.w3.org/2004/02/skos/core#Concept');
        $values = $this->formatValues('?type', $classes, 'uri');
        $query = <<<EOQ
SELECT DISTINCT (ucase(str(substr(?label, 1, 1))) as ?l) $fcl WHERE {
  ?c skos:prefLabel ?label .
  ?c a ?type
  FILTER(langMatches(lang(?label), '$lang'))
  $values
}
EOQ;
        return $query;
    }

    /**
     * Transforms the first characters query results into an array format.
     * @param EasyRdf\Sparql\Result $result
     * @return array
     */
    private function transformFirstCharactersResults($result) {
        $ret = array();
        foreach ($result as $row) {
            $ret[] = $row->l->getValue();
        }
        return $ret;
    }

    /**
     * Query for the first characters (letter or otherwise) of the labels in the particular language.
     * @param string $lang language
     * @return array array of characters
     */
    public function queryFirstCharacters($lang, $classes = null) {
        $query = $this->generateFirstCharactersQuery($lang, $classes);
        $result = $this->query($query);
        return $this->transformFirstCharactersResults($result);
    }

    /**
     * @param string $uri
     * @param string $lang
     * @return string sparql query string
     */
    private function generateLabelQuery($uri, $lang) {
        $fcl = $this->generateFromClause();
        $labelcondLabel = ($lang) ? "FILTER( langMatches(lang(?label), '$lang') )" : "";
        $query = <<<EOQ
SELECT ?label $fcl
WHERE {
  <$uri> a ?type .
  OPTIONAL {
    <$uri> skos:prefLabel ?label .
    $labelcondLabel
  }
  OPTIONAL {
    <$uri> rdfs:label ?label .
    $labelcondLabel
  }
  OPTIONAL {
    <$uri> dc:title ?label .
    $labelcondLabel
  }
  OPTIONAL {
    <$uri> dc11:title ?label .
    $labelcondLabel
  }
}
EOQ;
        return $query;
    }

    /**
     * Query for a label (skos:prefLabel, rdfs:label, dc:title, dc11:title) of a resource.
     * @param string $uri
     * @param string $lang
     * @return array array of labels (key: lang, val: label), or null if resource doesn't exist
     */
    public function queryLabel($uri, $lang) {
        $query = $this->generateLabelQuery($uri, $lang);
        $result = $this->query($query);
        $ret = array();
        foreach ($result as $row) {
            if (!isset($row->label)) {
                // existing concept but no labels
                return array();
            }
            $ret[$row->label->getLang()] = $row->label;
        }

        if (sizeof($ret) > 0) {
            // existing concept, with label(s)
            return $ret;
        } else {
            // nonexistent concept
            return null;
        }
    }
    
    /**
     * Generates a SPARQL query to retrieve the super properties of a given property URI.
     * Note this must be executed in the graph where this information is available.
     * @param string $uri
     * @return string sparql query string
     */
    private function generateSubPropertyOfQuery($uri) {
        $fcl = $this->generateFromClause();
        $query = <<<EOQ
SELECT ?superProperty $fcl
WHERE {
  <$uri> rdfs:subPropertyOf ?superProperty
}
EOQ;
        return $query;
    }
    
    /**
     * Query the super properties of a provided property URI.
     * @param string $uri URI of a propertyes
     * @return array array super properties, or null if none exist
     */
    public function querySuperProperties($uri) {
        $query = $this->generateSubPropertyOfQuery($uri);
        $result = $this->query($query);
        $ret = array();
        foreach ($result as $row) {
            if (isset($row->superProperty)) {
                $ret[] = $row->superProperty->getUri();
            }
            
        }
        
        if (sizeof($ret) > 0) {
            // return result
            return $ret;
        } else {
            // no result, return null
            return null;
        }
    }


    /**
     * Generates a sparql query for queryNotation.
     * @param string $uri
     * @return string sparql query
     */
    private function generateNotationQuery($uri) {
        $fcl = $this->generateFromClause();

        $query = <<<EOQ
SELECT * $fcl
WHERE {
  <$uri> skos:notation ?notation .
}
EOQ;
        return $query;
    }

    /**
     * Query for the notation of the concept (skos:notation) of a resource.
     * @param string $uri
     * @return string notation or null if it doesn't exist
     */
    public function queryNotation($uri) {
        $query = $this->generateNotationQuery($uri);
        $result = $this->query($query);
        foreach ($result as $row) {
            if (isset($row->notation)) {
                return $row->notation->getValue();
            }
        }
        return null;
    }

    /**
     * Generates a sparql query for queryProperty.
     * @param string $uri
     * @param string $prop the name of the property eg. 'skos:broader'.
     * @param string $lang
     * @param boolean $anylang if you want a label even when it isn't available in the language you requested.
     * @return string sparql query
     */
    private function generatePropertyQuery($uri, $prop, $lang, $anylang) {
        $fcl = $this->generateFromClause();
        $anylang = $anylang ? "OPTIONAL { ?object skos:prefLabel ?label }" : "";

        $query = <<<EOQ
SELECT * $fcl
WHERE {
  <$uri> a skos:Concept .
  OPTIONAL {
    <$uri> $prop ?object .
    OPTIONAL {
      ?object skos:prefLabel ?label .
      FILTER (langMatches(lang(?label), "$lang"))
    }
    OPTIONAL {
      ?object skos:prefLabel ?label .
      FILTER (lang(?label) = "")
    }
    $anylang
  }
}
EOQ;
        return $query;
    }

    /**
     * Transforms the sparql query result into an array or null if the concept doesn't exist.
     * @param EasyRdf\Sparql\Result $result
     * @param string $lang
     * @return array array of property values (key: URI, val: label), or null if concept doesn't exist
     */
    private function transformPropertyQueryResults($result, $lang) {
        $ret = array();
        foreach ($result as $row) {
            if (!isset($row->object)) {
                return array();
            }
            // existing concept but no properties
            if (isset($row->label)) {
                if ($row->label->getLang() === $lang || array_key_exists($row->object->getUri(), $ret) === false) {
                    $ret[$row->object->getUri()]['label'] = $row->label->getValue();
                }

            } else {
                $ret[$row->object->getUri()]['label'] = null;
            }
        }
        if (sizeof($ret) > 0) {
            return $ret;
        }
        // existing concept, with properties
        else {
            return null;
        }
        // nonexistent concept
    }

    /**
     * Query a single property of a concept.
     * @param string $uri
     * @param string $prop the name of the property eg. 'skos:broader'.
     * @param string $lang
     * @param boolean $anylang if you want a label even when it isn't available in the language you requested.
     * @return array array of property values (key: URI, val: label), or null if concept doesn't exist
     */
    public function queryProperty($uri, $prop, $lang, $anylang = false) {
        $uri = is_array($uri) ? $uri[0] : $uri;
        $query = $this->generatePropertyQuery($uri, $prop, $lang, $anylang);
        $result = $this->query($query);
        return $this->transformPropertyQueryResults($result, $lang);
    }

    /**
     * Query a single transitive property of a concept.
     * @param string $uri
     * @param array $props the name of the property eg. 'skos:broader'.
     * @param string $lang
     * @param integer $limit
     * @param boolean $anylang if you want a label even when it isn't available in the language you requested.
     * @return string sparql query
     */
    private function generateTransitivePropertyQuery($uri, $props, $lang, $limit, $anylang) {
        $uri = is_array($uri) ? $uri[0] : $uri;
        $fcl = $this->generateFromClause();
        $propertyClause = implode('|', $props);
        $otherlang = $anylang ? "OPTIONAL { ?object skos:prefLabel ?label }" : "";
        // need to do a SPARQL subquery because LIMIT needs to be applied /after/
        // the direct relationships have been collapsed into one string
        $query = <<<EOQ
SELECT * $fcl
WHERE {
  SELECT ?object ?label (GROUP_CONCAT(STR(?dir);separator=' ') as ?direct)
  WHERE {
    <$uri> a skos:Concept .
    OPTIONAL {
      <$uri> $propertyClause* ?object .
      OPTIONAL {
        ?object $propertyClause ?dir .
      }
    }
    OPTIONAL {
      ?object skos:prefLabel ?label .
      FILTER (langMatches(lang(?label), "$lang"))
    }
    $otherlang
  }
  GROUP BY ?object ?label
}
LIMIT $limit
EOQ;
        return $query;
    }

    /**
     * Transforms the sparql query result object into an array.
     * @param EasyRdf\Sparql\Result $result
     * @param string $lang
     * @param string $fallbacklang language to use if label is not available in the preferred language
     * @return array of property values (key: URI, val: label), or null if concept doesn't exist
     */
    private function transformTransitivePropertyResults($result, $lang, $fallbacklang) {
        $ret = array();
        foreach ($result as $row) {
            if (!isset($row->object)) {
                return array();
            }
            // existing concept but no properties
            if (isset($row->label)) {
                $val = array('label' => $row->label->getValue());
            } else {
                $val = array('label' => null);
            }
            if (isset($row->direct) && $row->direct->getValue() != '') {
                $val['direct'] = explode(' ', $row->direct->getValue());
            }
            // Preventing labels in a non preferred language overriding the preferred language.
            if (isset($row->label) && $row->label->getLang() === $lang || array_key_exists($row->object->getUri(), $ret) === false) {
                if (!isset($row->label) || $row->label->getLang() === $lang) {
                    $ret[$row->object->getUri()] = $val;
                } elseif ($row->label->getLang() === $fallbacklang) {
                    $val['label'] .= ' (' . $row->label->getLang() . ')';
                    $ret[$row->object->getUri()] = $val;
                }
            }
        }

        // second iteration of results to find labels for the ones that didn't have one in the preferred languages
        foreach ($result as $row) {
            if (isset($row->object) && array_key_exists($row->object->getUri(), $ret) === false) {
                $val = array('label' => $row->label->getValue());
                if (isset($row->direct) && $row->direct->getValue() != '') {
                    $val['direct'] = explode(' ', $row->direct->getValue());
                }
                $ret[$row->object->getUri()] = $val;
            }
        }

        if (sizeof($ret) > 0) {
            return $ret;
        }
        // existing concept, with properties
        else {
            return null;
        }
        // nonexistent concept
    }

    /**
     * Query a single transitive property of a concept.
     * @param string $uri
     * @param array $props the property/properties.
     * @param string $lang
     * @param string $fallbacklang language to use if label is not available in the preferred language
     * @param integer $limit
     * @param boolean $anylang if you want a label even when it isn't available in the language you requested.
     * @return array array of property values (key: URI, val: label), or null if concept doesn't exist
     */
    public function queryTransitiveProperty($uri, $props, $lang, $limit, $anylang = false, $fallbacklang = '') {
        $query = $this->generateTransitivePropertyQuery($uri, $props, $lang, $limit, $anylang);
        $result = $this->query($query);
        return $this->transformTransitivePropertyResults($result, $lang, $fallbacklang);
    }

    /**
     * Generates the query for a concepts skos:narrowers.
     * @param string $uri
     * @param string $lang
     * @param string $fallback
     * @return string sparql query
     */
    private function generateChildQuery($uri, $lang, $fallback, $props) {
        $uri = is_array($uri) ? $uri[0] : $uri;
        $fcl = $this->generateFromClause();
        $propertyClause = implode('|', $props);
        $query = <<<EOQ
SELECT ?child ?label ?child ?grandchildren ?notation $fcl WHERE {
  <$uri> a skos:Concept .
  OPTIONAL {
    ?child $propertyClause <$uri> .
    OPTIONAL {
      ?child skos:prefLabel ?label .
      FILTER (langMatches(lang(?label), "$lang"))
    }
    OPTIONAL {
      ?child skos:prefLabel ?label .
      FILTER (langMatches(lang(?label), "$fallback"))
    }
    OPTIONAL { # other language case
      ?child skos:prefLabel ?label .
    }
    OPTIONAL {
      ?child skos:notation ?notation .
    }
    BIND ( EXISTS { ?a $propertyClause ?child . } AS ?grandchildren )
  }
}
EOQ;
        return $query;
    }

    /**
     * Transforms the sparql result object into an array.
     * @param EasyRdf\Sparql\Result $result
     * @param string $lang
     * @return array array of arrays describing each child concept, or null if concept doesn't exist
     */
    private function transformNarrowerResults($result, $lang) {
        $ret = array();
        foreach ($result as $row) {
            if (!isset($row->child)) {
                return array();
            }
            // existing concept but no children

            $label = null;
            if (isset($row->label)) {
                if ($row->label->getLang() == $lang || strpos($row->label->getLang(), $lang . "-") == 0) {
                    $label = $row->label->getValue();
                } else {
                    $label = $row->label->getValue() . " (" . $row->label->getLang() . ")";
                }

            }
            $childArray = array(
                'uri' => $row->child->getUri(),
                'prefLabel' => $label,
                'hasChildren' => filter_var($row->grandchildren->getValue(), FILTER_VALIDATE_BOOLEAN),
            );
            if (isset($row->notation)) {
                $childArray['notation'] = $row->notation->getValue();
            }

            $ret[] = $childArray;
        }
        if (sizeof($ret) > 0) {
            return $ret;
        }
        // existing concept, with children
        else {
            return null;
        }
        // nonexistent concept
    }

    /**
     * Query the narrower concepts of a concept.
     * @param string $uri
     * @param string $lang
     * @param string $fallback
     * @return array array of arrays describing each child concept, or null if concept doesn't exist
     */
    public function queryChildren($uri, $lang, $fallback, $props) {
        $query = $this->generateChildQuery($uri, $lang, $fallback, $props);
        $result = $this->query($query);
        return $this->transformNarrowerResults($result, $lang);
    }

    /**
     * Query the top concepts of a vocabulary.
     * @param string $conceptSchemes concept schemes whose top concepts to query for
     * @param string $lang language of labels
     * @param string $fallback language to use if label is not available in the preferred language
     */
    public function queryTopConcepts($conceptSchemes, $lang, $fallback) {
        if (!is_array($conceptSchemes)) {
            $conceptSchemes = array($conceptSchemes);
        }

        $values = $this->formatValues('?topuri', $conceptSchemes, 'uri');

        $fcl = $this->generateFromClause();
        $query = <<<EOQ
SELECT DISTINCT ?top ?topuri ?label ?notation ?children $fcl WHERE {
  ?top skos:topConceptOf ?topuri .
  OPTIONAL {
    ?top skos:prefLabel ?label .
    FILTER (langMatches(lang(?label), "$lang"))
  }
  OPTIONAL {
    ?top skos:prefLabel ?label .
    FILTER (langMatches(lang(?label), "$fallback"))
  }
  OPTIONAL { # fallback - other language case
    ?top skos:prefLabel ?label .
  }
  OPTIONAL { ?top skos:notation ?notation . }
  BIND ( EXISTS { ?top skos:narrower ?a . } AS ?children )
  $values
}
EOQ;
        $result = $this->query($query);
        $ret = array();
        foreach ($result as $row) {
            if (isset($row->top) && isset($row->label)) {
                $label = $row->label->getValue();
                if ($row->label->getLang() && $row->label->getLang() !== $lang && strpos($row->label->getLang(), $lang . "-") !== 0) {
                    $label .= ' (' . $row->label->getLang() . ')';
                }
                $top = array('uri' => $row->top->getUri(), 'topConceptOf' => $row->topuri->getUri(), 'label' => $label, 'hasChildren' => filter_var($row->children->getValue(), FILTER_VALIDATE_BOOLEAN));
                if (isset($row->notation)) {
                    $top['notation'] = $row->notation->getValue();
                }

                $ret[] = $top;
            }
        }

        return $ret;
    }

    /**
     * Generates a sparql query for finding the hierarchy for a concept.
	 * A concept may be a top concept in multiple schemes, returned as a single whitespace-separated literal.
     * @param string $uri concept uri.
     * @param string $lang
     * @param string $fallback language to use if label is not available in the preferred language
     * @return string sparql query
     */
    private function generateParentListQuery($uri, $lang, $fallback, $props) {
        $fcl = $this->generateFromClause();
        $propertyClause = implode('|', $props);
        $query = <<<EOQ
SELECT ?broad ?parent ?children ?grandchildren
(SAMPLE(?lab) as ?label) (SAMPLE(?childlab) as ?childlabel) (GROUP_CONCAT(?topcs; separator=" ") as ?tops) 
(SAMPLE(?nota) as ?notation) (SAMPLE(?childnota) as ?childnotation) $fcl
WHERE {
  <$uri> a skos:Concept .
  OPTIONAL {
    <$uri> $propertyClause* ?broad .
    OPTIONAL {
      ?broad skos:prefLabel ?lab .
      FILTER (langMatches(lang(?lab), "$lang"))
    }
    OPTIONAL {
      ?broad skos:prefLabel ?lab .
      FILTER (langMatches(lang(?lab), "$fallback"))
    }
    OPTIONAL { # fallback - other language case
      ?broad skos:prefLabel ?lab .
    }
    OPTIONAL { ?broad skos:notation ?nota . }
    OPTIONAL { ?broad $propertyClause ?parent . }
    OPTIONAL { ?broad skos:narrower ?children .
      OPTIONAL {
        ?children skos:prefLabel ?childlab .
        FILTER (langMatches(lang(?childlab), "$lang"))
      }
      OPTIONAL {
        ?children skos:prefLabel ?childlab .
        FILTER (langMatches(lang(?childlab), "$fallback"))
      }
      OPTIONAL { # fallback - other language case
        ?children skos:prefLabel ?childlab .
      }
      OPTIONAL {
        ?children skos:notation ?childnota .
      }
    }
    BIND ( EXISTS { ?children skos:narrower ?a . } AS ?grandchildren )
    OPTIONAL { ?broad skos:topConceptOf ?topcs . }
  }
}
GROUP BY ?broad ?parent ?member ?children ?grandchildren
EOQ;
        return $query;
    }

    /**
     * Transforms the result into an array.
     * @param EasyRdf\Sparql\Result
     * @param string $lang
     * @return an array for the REST controller to encode.
     */
    private function transformParentListResults($result, $lang)
    {
        $ret = array();
        foreach ($result as $row) {
            if (!isset($row->broad)) {
                // existing concept but no broaders
                return array();
            }
            $uri = $row->broad->getUri();
            if (!isset($ret[$uri])) {
                $ret[$uri] = array('uri' => $uri);
            }
            if (isset($row->exact)) {
                $ret[$uri]['exact'] = $row->exact->getUri();
            }
            if (isset($row->tops)) {
               $topConceptsList=explode(" ", $row->tops->getValue());
               // sort to garantee an alphabetical ordering of the URI
               sort($topConceptsList);
               $ret[$uri]['tops'] = $topConceptsList;
            }
            if (isset($row->children)) {
                if (!isset($ret[$uri]['narrower'])) {
                    $ret[$uri]['narrower'] = array();
                }

                $label = null;
                if (isset($row->childlabel)) {
                    $label = $row->childlabel->getValue();
                    if ($row->childlabel->getLang() !== $lang && strpos($row->childlabel->getLang(), $lang . "-") !== 0) {
                        $label .= " (" . $row->childlabel->getLang() . ")";
                    }

                }

                $childArr = array(
                    'uri' => $row->children->getUri(),
                    'label' => $label,
                    'hasChildren' => filter_var($row->grandchildren->getValue(), FILTER_VALIDATE_BOOLEAN),
                );
                if (isset($row->childnotation)) {
                    $childArr['notation'] = $row->childnotation->getValue();
                }

                if (!in_array($childArr, $ret[$uri]['narrower'])) {
                    $ret[$uri]['narrower'][] = $childArr;
                }

            }
            if (isset($row->label)) {
                $preflabel = $row->label->getValue();
                if ($row->label->getLang() && $row->label->getLang() !== $lang && strpos($row->label->getLang(), $lang . "-") !== 0) {
                    $preflabel .= ' (' . $row->label->getLang() . ')';
                }

                $ret[$uri]['prefLabel'] = $preflabel;
            }
            if (isset($row->notation)) {
                $ret[$uri]['notation'] = $row->notation->getValue();
            }

            if (isset($row->parent) && (isset($ret[$uri]['broader']) && !in_array($row->parent->getUri(), $ret[$uri]['broader']))) {
                $ret[$uri]['broader'][] = $row->parent->getUri();
            } elseif (isset($row->parent) && !isset($ret[$uri]['broader'])) {
                $ret[$uri]['broader'][] = $row->parent->getUri();
            }
        }
        if (sizeof($ret) > 0) {
            // existing concept, with children
            return $ret;
        }
        else {
            // nonexistent concept
            return null;
        }
    }

    /**
     * Query for finding the hierarchy for a concept.
     * @param string $uri concept uri.
     * @param string $lang
     * @param string $fallback language to use if label is not available in the preferred language
     * @param array $props the hierarchy property/properties to use
     * @return an array for the REST controller to encode.
     */
    public function queryParentList($uri, $lang, $fallback, $props) {
        $query = $this->generateParentListQuery($uri, $lang, $fallback, $props);
        $result = $this->query($query);
        return $this->transformParentListResults($result, $lang);
    }

    /**
     * return a list of concept group instances, sorted by label
     * @param string $groupClass URI of concept group class
     * @param string $lang language of labels to return
     * @return string sparql query
     */
    private function generateConceptGroupsQuery($groupClass, $lang) {
        $fcl = $this->generateFromClause();
        $query = <<<EOQ
SELECT ?group (GROUP_CONCAT(DISTINCT STR(?child);separator=' ') as ?children) ?label ?members ?notation $fcl
WHERE {
  ?group a <$groupClass> .
  OPTIONAL { ?group skos:member|isothes:subGroup ?child .
             ?child a <$groupClass> }
  BIND(EXISTS{?group skos:member ?submembers} as ?members)
  OPTIONAL { ?group skos:prefLabel ?label }
  OPTIONAL { ?group rdfs:label ?label }
  FILTER (langMatches(lang(?label), '$lang'))
  OPTIONAL { ?group skos:notation ?notation }
}
GROUP BY ?group ?label ?members ?notation
ORDER BY lcase(?label)
EOQ;
        return $query;
    }

    /**
     * Transforms the sparql query result into an array.
     * @param EasyRdf\Sparql\Result $result
     * @return array
     */
    private function transformConceptGroupsResults($result) {
        $ret = array();
        foreach ($result as $row) {
            if (!isset($row->group)) {
                # no groups found, see issue #357
                continue;
            }
            $group = array('uri' => $row->group->getURI());
            if (isset($row->label)) {
                $group['prefLabel'] = $row->label->getValue();
            }

            if (isset($row->children)) {
                $group['childGroups'] = explode(' ', $row->children->getValue());
            }

            if (isset($row->members)) {
                $group['hasMembers'] = $row->members->getValue();
            }

            if (isset($row->notation)) {
                $group['notation'] = $row->notation->getValue();
            }

            $ret[] = $group;
        }
        return $ret;
    }

    /**
     * return a list of concept group instances, sorted by label
     * @param string $groupClass URI of concept group class
     * @param string $lang language of labels to return
     * @return array Result array with group URI as key and group label as value
     */
    public function listConceptGroups($groupClass, $lang) {
        $query = $this->generateConceptGroupsQuery($groupClass, $lang);
        $result = $this->query($query);
        return $this->transformConceptGroupsResults($result);
    }

    /**
     * Generates the sparql query for listConceptGroupContents
     * @param string $groupClass URI of concept group class
     * @param string $group URI of the concept group instance
     * @param string $lang language of labels to return
     * @param boolean $showDeprecated whether to include deprecated in the result
     * @return string sparql query
     */
    private function generateConceptGroupContentsQuery($groupClass, $group, $lang, $showDeprecated = false) {
        $fcl = $this->generateFromClause();
        $filterDeprecated="";
        if(!$showDeprecated){
            $filterDeprecated="  FILTER NOT EXISTS { ?conc owl:deprecated true }";
        }
        $query = <<<EOQ
SELECT ?conc ?super ?label ?members ?type ?notation $fcl
WHERE {
 <$group> a <$groupClass> .
 { <$group> skos:member ?conc . } UNION { ?conc isothes:superGroup <$group> }
$filterDeprecated
 ?conc a ?type .
 OPTIONAL { ?conc skos:prefLabel ?label .
  FILTER (langMatches(lang(?label), '$lang'))
 }
 OPTIONAL { ?conc skos:prefLabel ?label . }
 OPTIONAL { ?conc skos:notation ?notation }
 BIND(EXISTS{?submembers isothes:superGroup ?conc} as ?super)
 BIND(EXISTS{?conc skos:member ?submembers} as ?members)
} ORDER BY lcase(?label)
EOQ;
        return $query;
    }

    /**
     * Transforms the sparql query result into an array.
     * @param EasyRdf\Sparql\Result $result
     * @param string $lang language of labels to return
     * @return array
     */
    private function transformConceptGroupContentsResults($result, $lang) {
        $ret = array();
        $values = array();
        foreach ($result as $row) {
            if (!array_key_exists($row->conc->getURI(), $values)) {
                $values[$row->conc->getURI()] = array(
                    'uri' => $row->conc->getURI(),
                    'isSuper' => $row->super->getValue(),
                    'hasMembers' => $row->members->getValue(),
                    'type' => array($row->type->shorten()),
                );
                if (isset($row->label)) {
                    if ($row->label->getLang() == $lang || strpos($row->label->getLang(), $lang . "-") == 0) {
                        $values[$row->conc->getURI()]['prefLabel'] = $row->label->getValue();
                    } else {
                        $values[$row->conc->getURI()]['prefLabel'] = $row->label->getValue() . " (" . $row->label->getLang() . ")";
                    }

                }
                if (isset($row->notation)) {
                    $values[$row->conc->getURI()]['notation'] = $row->notation->getValue();
                }

            } else {
                $values[$row->conc->getURI()]['type'][] = $row->type->shorten();
            }
        }

        foreach ($values as $val) {
            $ret[] = $val;
        }

        return $ret;
    }

    /**
     * return a list of concepts in a concept group
     * @param string $groupClass URI of concept group class
     * @param string $group URI of the concept group instance
     * @param string $lang language of labels to return
     * @param boolean $showDeprecated whether to include deprecated concepts in search results
     * @return array Result array with concept URI as key and concept label as value
     */
    public function listConceptGroupContents($groupClass, $group, $lang,$showDeprecated = false) {
        $query = $this->generateConceptGroupContentsQuery($groupClass, $group, $lang,$showDeprecated);
        $result = $this->query($query);
        return $this->transformConceptGroupContentsResults($result, $lang);
    }

    /**
     * Generates the sparql query for queryChangeList.
     * @param string $lang language of labels to return.
     * @param int $offset offset of results to retrieve; 0 for beginning of list
     * @return string sparql query
     */
    private function generateChangeListQuery($lang, $offset, $prop) {
        $fcl = $this->generateFromClause();
        $offset = ($offset) ? 'OFFSET ' . $offset : '';

        $query = <<<EOQ
SELECT DISTINCT ?concept ?date ?label $fcl
WHERE {
  ?concept a skos:Concept .
  ?concept $prop ?date .
  ?concept skos:prefLabel ?label .
  FILTER (langMatches(lang(?label), '$lang'))
}
ORDER BY DESC(YEAR(?date)) DESC(MONTH(?date)) LCASE(?label)
LIMIT 200 $offset
EOQ;
        return $query;
    }

    /**
     * Transforms the sparql query result into an array.
     * @param EasyRdf\Sparql\Result $result
     * @return array
     */
    private function transformChangeListResults($result) {
        $ret = array();
        foreach ($result as $row) {
            $concept = array('uri' => $row->concept->getURI());
            if (isset($row->label)) {
                $concept['prefLabel'] = $row->label->getValue();
            }

            if (isset($row->date)) {
                $concept['date'] = $row->date->getValue();
            }

            $ret[] = $concept;
        }
        return $ret;
    }

    /**
     * return a list of recently changed or entirely new concepts
     * @param string $lang language of labels to return
     * @param int $offset offset of results to retrieve; 0 for beginning of list
     * @return array Result array
     */
    public function queryChangeList($lang, $offset, $prop) {
        $query = $this->generateChangeListQuery($lang, $offset, $prop);
        $result = $this->query($query);
        return $this->transformChangeListResults($result);
    }
}
