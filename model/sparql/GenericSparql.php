<?php
/**
 * Copyright (c) 2012-2013 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/**
 * Generates SPARQL queries and provides access to the SPARQL endpoint.
 */
class GenericSparql
{
  /**
   * A SPARQL Client eg. an EasyRDF instance.
   * @property EasyRdf_Sparql_Client $client
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
   * Requires the following three parameters.
   * @param string $endpoint SPARQL endpoint address.
   * @param object $graph an EasyRDF SPARQL graph instance.
   * @param object $model a Model instance.
   */
  public function __construct($endpoint, $graph, $model)
  {
    // if special cache control (typically no-cache) was requested by the
    // client, set the same type of cache control headers also in subsequent
    // in the SPARQL requests (this is useful for performance testing)
    if (isset($_SERVER['HTTP_CACHE_CONTROL']) || isset($_SERVER['HTTP_PRAGMA'])) {
      $val = isset($_SERVER['HTTP_PRAGMA']) ?
        $_SERVER['HTTP_PRAGMA'] :
        $_SERVER['HTTP_CACHE_CONTROL'];
      // configure the HTTP client used by EasyRdf_Sparql_Client
      $httpclient = EasyRdf_Http::getDefaultHttpClient();
      $httpclient->setHeaders('Cache-Control', $val);
      EasyRdf_Http::setDefaultHttpClient($httpclient); // actually redundant..
    }

    // create the EasyRDF SPARQL client instance to use
    $this->client = new EasyRdf_Sparql_Client($endpoint);
    $this->graph = $graph;
    $this->model = $model;

    // set graphClause so that it can be used by all queries
    if ($this->isDefaultEndpoint()) // default endpoint; query any graph (and catch it in a variable)
      $this->graphClause = "GRAPH $graph";
    elseif ($graph)       // query a specific graph
      $this->graphClause = "GRAPH <$graph>";
    else                  // query the default graph
      $this->graphClause = "";
  }

  /**
   * Return true if this is the default SPARQL endpoint, used as the facade to query
   * all vocabularies.
   */

  private function isDefaultEndpoint()
  {
    return $this->graph[0] == '?';
  }

  /**
   * If there is no vocabulary id available use this to guess it from the uri.
   * @param string $uri
   */
  private function guessVocabID($uri)
  {
    try {
      $exvoc = $this->model->guessVocabularyFromURI($uri);
    } catch (Exception $e) {
      trigger_error($e->getMessage(), E_USER_WARNING);

      return "???";
    }
    $exvocab = $exvoc->getId();

    return $exvocab;
  }

  /**
   * Returns the graph instance
   * @return object EasyRDF graph instance.
   */
  public function getGraph()
  {
    return $this->graph;
  }

  /**
   * Used for counting number of concepts in a vocabulary.
   * @return int number of concepts in this vocabulary
   */
  public function countConcepts()
  {
    $gc = $this->graphClause;
    $query = <<<EOQ
      SELECT (COUNT(?conc) as ?c) WHERE {
        $gc {
          { ?conc a skos:Concept }
        }
      }
EOQ;
    $result = $this->client->query($query);
    foreach ($result as $row) {
      return $row->c->getValue();
    }
  }

  /**
   * Counts the number of concepts in a easyRDF graph with a specific language.
   * @param array $langs Languages to query for
   * @return Array containing count of concepts for each language and property.
   */
  public function countLangConcepts($langs)
  {
    $gc = $this->graphClause;
    $ret = array();

    $props = array('skos:prefLabel', 'skos:altLabel', 'skos:hiddenLabel');
    $values_lang = $this->formatValues('?lang', $langs, 'literal');
    $values_prop = $this->formatValues('?prop', $props, null);

    $query = <<<EOQ
SELECT ?lang ?prop
  (COUNT(?label) as ?count)
WHERE {
  $gc {
    ?conc a skos:Concept .
    ?conc ?prop ?label .
    FILTER (langMatches(lang(?label), ?lang))
    $values_lang
    $values_prop
  }
}
GROUP BY ?lang ?prop
EOQ;
    // Count the number of terms in each language
    $result = $this->client->query($query);
    // set default count to zero; overridden below if query found labels
    foreach ($langs as $lang) {
      foreach ($props as $prop) {
        $ret[$lang][$prop] = 0;
      }
    }
    foreach ($result as $row) {
      if (isset($row->lang) && isset($row->prop) && isset($row->count))
        $ret[$row->lang->getValue()][$row->prop->shorten()] =
          $row->count->getValue();
    }
    ksort($ret);

    return $ret;
  }

  /**
   * Formats a VALUES clause (SPARQL 1.1) which states that the variable should be bound to one
   * of the constants given.
   * @param string $varname variable name, e.g. "?uri"
   * @param array $values the values
   * @param string $type type of values: "uri", "literal" or null (determines quoting style)
   */
  protected function formatValues($varname, $values, $type = null)
  {
    $constants = array();
    foreach ($values as $val) {
      if ($type == 'uri') $val = "<$val>";
      if ($type == 'literal') $val = "'$val'";
      $constants[] = "($val)";
    }
    $values = implode(" ", $constants);

    return "VALUES ($varname) { $values }";
  }

  /**
   * Returns information (as a graph) for one or more concept URIs
   * @param mixed $uris concept URI (string) or array of URIs
   * @param string $arrayClass the URI for thesaurus array class, or null if not used
   * @param string $vocabs array of Vocabulary object
   * @param boolean $as_graph whether to return a graph (true) or array of Concepts (false)
   * @return mixed query result graph (EasyRdf_Graph), or array of Concept objects
   */ 
  public function queryConceptInfo($uris, $arrayClass = null, $vocabs = null, $as_graph = false)
  {
    $gc = $this->graphClause;

    // if just a single URI is given, put it in an array regardless
    if (!is_array($uris))
      $uris = array($uris);

    $values = $this->formatValues('?uri', $uris, 'uri');
    $values_graph = $this->formatValuesGraph($vocabs);

    if (!$arrayClass) {
      $construct = $optional = "";
    } else {
      // add information that can be used to format narrower concepts by
      // the array they belong to ("milk by source animal" use case)
      $construct = "\n ?x skos:member ?o . ?x skos:prefLabel ?xl . ";
      $optional  = "\n OPTIONAL {
                      ?x skos:member ?o .
                      ?x a <$arrayClass> .
                      ?x skos:prefLabel ?xl .
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
 ?o rdf:type ?ot .
 ?o skos:prefLabel ?opl .
 ?o rdfs:label ?ol .
 ?group skos:member ?uri .
 ?group skos:prefLabel ?grouplabel .
 ?group rdf:type ?grouptype . $construct
} WHERE {
 $gc {
  { ?s ?p ?uri . }
  UNION
  { ?sp ?uri ?op . }
  UNION
  { ?group skos:member ?uri .
    ?group skos:prefLabel ?grouplabel .
    ?group rdf:type ?grouptype . }
  UNION
  {
   ?uri ?p ?o .
   OPTIONAL {
     { ?p rdfs:label ?proplabel . } 
     UNION
     { ?p rdfs:subPropertyOf ?pp . }
     UNION
     { ?o rdf:type ?ot . }
     UNION
     { ?o skos:prefLabel ?opl . }
     UNION
     { ?o rdfs:label ?ol . }
   } $optional
  }
 }
 $values
}
$values_graph
EOQ;
    $result = $this->client->query($query);
    if ($as_graph)
      return $result;

    if ($result->isEmpty())
      return;

    $conceptArray = array();
    foreach ($uris as $uri) {
      $conc = $result->resource($uri);
      $vocab = sizeof($vocabs) == 1 ? $vocabs[0] : $this->model->guessVocabularyFromUri($uri);
      $conceptArray[] = new Concept($this->model, $vocab, $conc, $result);
    }

    return $conceptArray;
  }

  /**
   * Retrieve information about types from the endpoint
   * @param string $lang
   * @return array Array with URIs (string) as key and array of (label, superclassURI) as value
   */

  public function queryTypes($lang)
  {
    $gc = $this->graphClause;
    $query = <<<EOQ
SELECT DISTINCT ?type ?label ?superclass
WHERE {
  $gc {
    {
      { ?type rdfs:subClassOf* skos:Concept . }
      UNION
      { ?type rdfs:subClassOf* skos:Collection . }
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
}
EOQ;
    $result = array();
    foreach ($this->client->query($query) as $row) {
      $type = array();
      if (isset($row->label)) $type['label'] = $row->label->getValue();
      if (isset($row->superclass)) $type['superclass'] = $row->superclass->getUri();
      $result[$row->type->getURI()] = $type;
    }

    return $result;
  }

  /**
   * Retrieves conceptScheme information from the endpoint.
   * @param string $cs concept scheme URI
   * @return EasyRDF_Graph query result graph
   */
  public function queryConceptScheme($cs)
  {
    $gc = $this->graphClause;
    $query = <<<EOQ
CONSTRUCT {
  <$cs> ?property ?value .
} WHERE {
  $gc {
    <$cs> ?property ?value .
    FILTER (?property != skos:hasTopConcept)
  }
}
EOQ;

    return $this->client->query($query);
  }

  /**
   * return a list of skos:ConceptScheme instances in the given graph
   * @param string $lang language of labels
   * @return array Array with concept scheme URIs (string) as keys and labels (string) as values
   */
  public function queryConceptSchemes($lang)
  {
    $gc = $this->graphClause;
    $query = <<<EOQ
SELECT ?cs ?label
WHERE {
 $gc {
   ?cs a skos:ConceptScheme .
   OPTIONAL {
     ?cs rdfs:label ?label .
     FILTER(langMatches(lang(?label), '$lang'))
   }
   OPTIONAL {
     ?cs skos:prefLabel ?preflabel .
     FILTER(langMatches(lang(?prefLabel), '$lang'))
   }
   OPTIONAL {
     { ?cs dc11:title ?title }
     UNION
     { ?cs dc:title ?title }
     FILTER(langMatches(lang(?title), '$lang'))
   }
 }
} ORDER BY ?cs
EOQ;
    $ret = array();
    foreach ($this->client->query($query) as $row) {
      $cs = array();
      if (isset($row->label))
        $cs['label'] = $row->label->getValue();
      if (isset($row->prefLabel))
        $cs['prefLabel'] = $row->prefLabel->getValue();
      if (isset($row->title))
        $cs['title'] = $row->title->getValue();
      $ret[$row->cs->getURI()] = $cs;
    }

    return $ret;
  }

  /**
   * Make a text query condition that narrows the amount of search
   * results in term searches. This is a stub implementation,
   * intended to be overridden in subclasses to enable the use of
   * tet indexes in SPARQL dialects that support them.
   *
   * @param string $term search term
   * @return string SPARQL text search clause
   */
  protected function createTextQueryCondition($term)
  {
    return '# generic SPARQL dialect, no text index support';
  }

  /**
   * Generate a VALUES clause for limiting the targeted graphs.
   * @param array $vocabs array of Vocabulary objects to target
   * @return string VALUES clause, or "" if not necessary to limit
   */
  protected function formatValuesGraph($vocabs) {
    if ($this->isDefaultEndpoint() && $vocabs != null && sizeof($vocabs) > 0) {
      $graphs = array();
      foreach ($vocabs as $voc) {
        $graphs[] = $voc->getGraph();
      }
      return $this->formatValues('?graph', $graphs, 'uri');
    } else {
      return "";
    }
  }

  /**
   * Query for concepts using a search term.
   * @param string $term search term
   * @param array $vocabs array of Vocabulary objects to search; empty for global search
   * @param string $lang language code of the returned labels
   * @param string $search_lang language code used for matching labels (null means any language)
   * @param int $limit maximum number of hits to retrieve; 0 for unlimited
   * @param int $offset offset of results to retrieve; 0 for beginning of list
   * @param string $arrayClass the URI for thesaurus array class, or null if not used
   * @param array $types limit search to concepts of the given type(s)
   * @param string $parent limit search to concepts which have the given concept as parent in the transitive broader hierarchy
   * @param string $group limit search to concepts which are in the given group
   * @param boolean $hidden include matches on hidden labels (default: true)
   * @param array $fields extra fields to include in the result (array of strings). (default: null = none)
   * @return array query result object
   */
  public function queryConcepts($term, $vocabs, $lang, $search_lang, $limit, $offset, $arrayClass, $types, $parent=null, $group=null, $hidden=true, $fields=null)
  {
    $gc = $this->graphClause;
    $limit = ($limit) ? 'LIMIT ' . $limit : '';
    $offset = ($offset) ? 'OFFSET ' . $offset : '';
    $unprefixed_types;
    foreach($types as $type)
      $unprefixed_types[] = EasyRdf_Namespace::expand($type);

    // extra variable expressions to request
    $extravars = '';
    // extra fields to query for
    $extrafields = '';

    if ($fields !== null && in_array('broader', $fields)) {
      # This expression creates a CSV row containing pairs of (uri,prefLabel) values.
      # The REPLACE is performed for quotes (" -> "") so they don't break the CSV format.
      $extravars = <<<EOV
(GROUP_CONCAT(DISTINCT CONCAT(
 '"', STR(?broad), '"', ',',
 '"', REPLACE(IF(BOUND(?broadlab),?broadlab,''), '"', '""'), '"'
); separator='\\n') as ?broaders)
EOV;
      $extrafields = <<<EOF
OPTIONAL {
  ?s skos:broader ?broad .
  OPTIONAL { ?broad skos:prefLabel ?broadlab . FILTER(langMatches(lang(?broadlab), '$lang')) }
}
EOF;
    }

    // extra types to query, if using thesaurus arrays
    $extratypes = $arrayClass ? "UNION { ?s a <$arrayClass> }" : "";

    if (sizeof($unprefixed_types) === 1) // if only one type limitation set no UNION needed
      $type = $unprefixed_types[0];
    else { // multiple type limitations require setting a UNION for each of those
      foreach($unprefixed_types as $utype)
        $extratypes .= "\nUNION { ?s a <$utype> }";
    }      

    // extra conditions for label language, if specified
    $labelcond_match = ($search_lang) ? "&& langMatches(lang(?match), '$search_lang')" : "";
    $labelcond_label = ($lang) ? "langMatches(lang(?label), '$lang')" : "langMatches(lang(?label), lang(?match))";
    // if search language and UI/display language differ, must also consider case where there is no prefLabel in
    // the display language; in that case, should use the label with the same language as the matched label
    $labelcond_fallback = ($search_lang != $lang) ? 
      "OPTIONAL { # in case previous OPTIONAL block gives no labels
       ?s skos:prefLabel ?label .
       FILTER (langMatches(lang(?label), lang(?match))) }" : "";

    // extra conditions for parent and group, if specified
    $parentcond = ($parent) ? "?s skos:broader+ <$parent> ." : "";
    $groupcond = ($group) ? "<$group> skos:member ?s ." : "";
    
    // eliminating whitespace and line changes when the conditions aren't needed.
    $pgcond = '';
    if ($parentcond && $groupcond)
      $pgcond = "\n" . $parentcond . "\n" . $groupcond;
    elseif ($parentcond !== '')
      $pgcond = "\n" . $parentcond;
    elseif ($groupcond !== '')
      $pgcond = "\n" . $groupcond;
    
    // eliminating whitespace and line changes when the conditions aren't needed.
    $limitandoffset = '';
    if ($limit && $offset)
      $limitandoffset = "\n" . $limit . "\n" . $offset;
    elseif ($limit)
      $limitandoffset = "\n" . $limit;
    elseif ($offset)
      $limitandoffset = "\n" . $offset;

    $orderextra = $this->isDefaultEndpoint() ? $this->graph : '';

    # make VALUES clauses
    $props = array('skos:prefLabel','skos:altLabel');
    if ($hidden) $props[] = 'skos:hiddenLabel';
    $values_prop = $this->formatValues('?prop', $props);

    $values_graph = $this->formatValuesGraph($vocabs);

    while (strpos($term, '**') !== false)
      $term = str_replace('**', '*', $term); // removes futile asterisks

    # make text query clauses
    $textcond_pref = $this->createTextQueryCondition($term, 'skos:prefLabel');
    $textcond_alt = $this->createTextQueryCondition($term, 'skos:altLabel');
    $textcond_hidden = $this->createTextQueryCondition($term, 'skos:hiddenLabel');
    $textcond = "{{ $textcond_pref \n} UNION { $textcond_alt \n} UNION { $textcond_hidden \n}}";

    # use appropriate matching function depending on query type: =, strstarts, strends or full regex
    if (preg_match('/^[^\*]+$/', $term)) { // exact query
      $term = str_replace('\\', '\\\\', $term); // quote slashes
      $term = str_replace('\'', '\\\'', mb_strtolower($term, 'UTF-8')); // make lowercase and escape single quotes
      $filtercond = "lcase(str(?match)) = '$term'";
    } elseif (preg_match('/^[^\*]+\*$/', $term)) { // prefix query
      $term = substr($term, 0, -1); // remove the final asterisk
      $term = str_replace('\\', '\\\\', $term); // quote slashes
      $term = str_replace('\'', '\\\'', mb_strtolower($term, 'UTF-8')); // make lowercase and escape single quotes
      $filtercond = "strstarts(lcase(str(?match)), '$term')" . // avoid matches on both altLabel and prefLabel
                    " && !(?match != ?label && strstarts(lcase(str(?label)), '$term'))";
    } elseif (preg_match('/^\*[^\*]+$/', $term)) { // suffix query
      $term = substr($term, 1); // remove the preceding asterisk
      $term = str_replace('\\', '\\\\', $term); // quote slashes
      $term = str_replace('\'', '\\\'', mb_strtolower($term, 'UTF-8')); // make lowercase and escape single quotes
      $filtercond = "strends(lcase(str(?match)), '$term')";
    } else { // too complicated - have to use a regex
      # make sure regex metacharacters are not passed through
      $term = str_replace('\\', '\\\\', preg_quote($term));
      $term = str_replace('\\\\*', '.*', $term); // convert asterisk to regex syntax
      $term = str_replace('\'', '\\\'', $term); // ensure single quotes are quoted
      $filtercond = "regex(str(?match), '^$term$', 'i')";
    }

    # order of graph clause and text query depends on whether we are performing global search
    # global search: text query first, then process by graph
    # local search: limit by graph first, then graph-specific text query
    $graph_text = $this->isDefaultEndpoint() ? "$textcond \n $gc {" : "$gc { $textcond \n";

    $query = <<<EOQ
SELECT DISTINCT ?s ?label ?plabel ?alabel ?hlabel ?graph (GROUP_CONCAT(DISTINCT ?type) as ?types)
$extravars
WHERE {
 $graph_text
  { ?s rdf:type <$type> } $extratypes
  { $pgcond
   ?s rdf:type ?type .
   ?s ?prop ?match .
   FILTER (
    $filtercond
    $labelcond_match
   )
   OPTIONAL {
    ?s skos:prefLabel ?label .
    FILTER ($labelcond_label)
   } $labelcond_fallback $extrafields
  }
  FILTER NOT EXISTS { ?s owl:deprecated true }
 }
 BIND(IF(?prop = skos:prefLabel && ?match != ?label, ?match, ?unbound) as ?plabel)
 BIND(IF(?prop = skos:altLabel, ?match, ?unbound) as ?alabel)
 BIND(IF(?prop = skos:hiddenLabel, ?match, ?unbound) as ?hlabel)
 $values_prop
}
GROUP BY ?match ?s ?label ?plabel ?alabel ?hlabel ?graph ?prop
ORDER BY lcase(str(?match)) lang(?match) $orderextra $limitandoffset
$values_graph
EOQ;

    $results = $this->client->query($query);
    $ret = array();
    $qnamecache = array(); // optimization to avoid expensive shorten() calls

    foreach ($results as $row) {
      if (!isset($row->s)) continue; // don't break if query returns a single dummy result

      $hit = array();
      $hit['uri'] = $row->s->getUri();

      if (isset($row->graph))
        $hit['graph'] = $row->graph->getUri();

      foreach (explode(" ", $row->types->getValue()) as $typeuri) {
        if (!array_key_exists($typeuri, $qnamecache)) {
          $res = new EasyRdf_Resource($typeuri);
          $qname = $res->shorten(); // returns null on failure
          $qnamecache[$typeuri] = $qname != null ? $qname : $typeuri;
        }
        $hit['type'][] = $qnamecache[$typeuri];
      }
      
      if (isset($row->broaders)) {
        foreach (explode("\n", $row->broaders->getValue()) as $line) {
          $brdata = str_getcsv($line, ',', '"', '"');
          $broader = array('uri' => $brdata[0]);
          if ($brdata[1] != '') $broader['prefLabel'] = $brdata[1];
          $hit['broader'][] = $broader;
        }
      }

      foreach ($vocabs as $vocab) { // looping the vocabulary objects and asking these for a localname for the concept.
        $localname = $vocab->getLocalName($hit['uri']);
        if ($localname !== $hit['uri']) { // only passing the result forward if the uri didn't boomerang right back.
          $hit['localname'] = $localname;
          break; // stopping the search when we find one that returns something valid.
        }
      }

      $hit['prefLabel'] = $row->label->getValue();
      $hit['lang'] = $row->label->getLang();

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

      $ret[] = $hit;
    }

    return $ret;
  }

  /**
   * Query for concepts with a term starting with the given letter. Also special classes '0-9' (digits),
   * '*!' (special characters) and '*' (everything) are accepted.
   * @param $letter the letter (or special class) to search for
   * @param $lang language of labels
   */
  public function queryConceptsAlphabetical($letter, $lang, $limit=null, $offset=null, $class=null) {
    $gc = $this->graphClause;
    $limit = ($limit) ? 'LIMIT ' . $limit : '';
    $offset = ($offset) ? 'OFFSET ' . $offset : '';
    $class = ($class) ? $class : 'http://www.w3.org/2004/02/skos/core#Concept';
    $values = 'VALUES (?type) { (<' . $class . '>) }';
    
    // eliminating whitespace and line changes when the conditions aren't needed.
    $limitandoffset = '';
    if ($limit && $offset)
      $limitandoffset = "\n" . $limit . "\n" . $offset;
    elseif ($limit)
      $limitandoffset = "\n" . $limit;
    elseif ($offset)
      $limitandoffset = "\n" . $offset;

    $use_regex = false;

    if ($letter == '*') {
      $letter = '.*';
      $use_regex = true;
    } elseif ($letter == '0-9') {
      $letter = '[0-9].*';
      $use_regex = true;
    } elseif ($letter == '!*') {
      $letter = '[^\\\\p{L}\\\\p{N}].*';
      $use_regex = true;
    }

    # make text query clause
    $textcond_pref = $use_regex ? '# regex in use' : $this->createTextQueryCondition($letter . '*', 'skos:prefLabel');
    $textcond_alt = $use_regex ? '# regex in use' : $this->createTextQueryCondition($letter . '*', 'skos:altLabel');
    $lcletter = mb_strtolower($letter, 'UTF-8'); // convert to lower case, UTF-8 safe
    if ($use_regex) {
      $filtercond_label = "regex(str(?label), '^$letter$', 'i')";
      $filtercond_alabel = "regex(str(?alabel), '^$letter$', 'i')";
    } else {
      $filtercond_label = "strstarts(lcase(str(?label)), '$lcletter')";
      $filtercond_alabel = "strstarts(lcase(str(?alabel)), '$lcletter')";
    }

    $query = <<<EOQ
SELECT ?s ?label ?alabel
WHERE {
  $gc {
    {
      $textcond_pref
      ?s skos:prefLabel ?label .
      FILTER (
        $filtercond_label
        && langMatches(lang(?label), '$lang')
      )
    }
    UNION
    {
      $textcond_alt
      {
        ?s skos:altLabel ?alabel .
        FILTER (
          $filtercond_alabel
          && langMatches(lang(?alabel), '$lang')
        )
      }
      {
        ?s skos:prefLabel ?label .
        FILTER (langMatches(lang(?label), '$lang'))
      }
    }
    ?s a ?type .
    FILTER NOT EXISTS { ?s owl:deprecated true }
  } $values
}
ORDER BY LCASE(IF(BOUND(?alabel), STR(?alabel), STR(?label))) $limitandoffset
EOQ;

    $results = $this->client->query($query);
    $ret = array();

    foreach ($results as $row) {
      if (!isset($row->s)) continue; // don't break if query returns a single dummy result

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
   * Query for the first characters (letter or otherwise) of the labels in the particular language.
   * @param string $lang language
   * @return array array of characters
   */
   
  public function queryFirstCharacters($lang, $class=null) {
    $gc = $this->graphClause;
    $class = ($class) ? $class : 'http://www.w3.org/2004/02/skos/core#Concept' ;
    $values = 'VALUES (?type) { (<' . $class . '>) }';
    $query = <<<EOQ
SELECT DISTINCT (substr(ucase(str(?label)), 1, 1) as ?l) WHERE {
  $gc {
    ?c skos:prefLabel ?label .
    ?c a ?type
    FILTER(langMatches(lang(?label), '$lang'))
  }
  $values
}
EOQ;
    $result = $this->client->query($query);
    $ret = array();
    foreach ($result as $row) {
      $ret[] = $row->l->getValue();
    } 
    return $ret;
  }

  /**
   * Query for a label (skos:prefLabel, rdfs:label, dc:title, dc11:title) of a resource.
   * @param string $uri
   * @param string $lang
   * @return array array of labels (key: lang, val: label), or null if resource doesn't exist
   */
  public function queryLabel($uri, $lang)
  {
    $gc = $this->graphClause;
    $labelcond_label = ($lang) ? "FILTER( langMatches(lang(?label), '$lang') )" : "";
    $query = <<<EOQ
SELECT ?label 
WHERE {
  $gc {
    <$uri> rdf:type ?type .
    OPTIONAL {
      <$uri> skos:prefLabel ?label .
      $labelcond_label
    }
    OPTIONAL {
      <$uri> rdfs:label ?label .
      $labelcond_label
    }
    OPTIONAL {
      <$uri> dc:title ?label .
      $labelcond_label
    }
    OPTIONAL {
      <$uri> dc11:title ?label .
      $labelcond_label
    }
  }
}
EOQ;
    $result = $this->client->query($query);
    $ret = array();
    foreach ($result as $row) {
      if (!isset($row->label))
        return array(); // existing concept but no labels
      $ret[$row->label->getLang()] = $row->label->getValue();
    }

    if (sizeof($ret) > 0)
      return $ret; // existing concept, with label(s)
    else
      return null; // nonexistent concept
  }


  /**
   * Query a single property of a concept.
   * @param string $uri
   * @param string $prop the name of the property eg. 'skos:broader'.
   * @param string $lang
   * @param boolean $anylang if you want a label even when it isn't available in the language you requested.
   * @return array array of property values (key: URI, val: label), or null if concept doesn't exist
   */
  public function queryProperty($uri, $prop, $lang, $anylang = false)
  {
    $uri = is_array($uri) ? $uri[0] : $uri;
    $gc = $this->graphClause;
    $anylang = $anylang ? "OPTIONAL { ?object skos:prefLabel ?label }" : "";

    $query = <<<EOQ
SELECT *
WHERE {
  $gc {
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
}
EOQ;
    $result = $this->client->query($query);
    $ret = array();
    foreach ($result as $row) {
      if (!isset($row->object))
        return array(); // existing concept but no properties
      if (isset($row->label)) {
        if ($row->label->getLang() === $lang || array_key_exists($row->object->getUri(), $ret) === false)
          $ret[$row->object->getUri()]['label'] = $row->label->getValue();
      } else {
        $ret[$row->object->getUri()]['label'] = null;
      }
    }
    if (sizeof($ret) > 0)
      return $ret; // existing concept, with properties
    else
      return null; // nonexistent concept
  }

  /**
   * Query a single transitive property of a concept.
   * @param string $uri
   * @param string $prop the name of the property eg. 'skos:broader'.
   * @param string $lang
   * @param integer $limit
   * @param boolean $anylang if you want a label even when it isn't available in the language you requested.
   * @return array array of property values (key: URI, val: label), or null if concept doesn't exist
   */
  public function queryTransitiveProperty($uri, $prop, $lang, $limit, $anylang=false)
  {
    $uri = is_array($uri) ? $uri[0] : $uri;
    $gc = $this->graphClause;
    $filter = $anylang ? "" : "FILTER (langMatches(lang(?label), \"$lang\"))";
    // need to do a SPARQL subquery because LIMIT needs to be applied /after/
    // the direct relationships have been collapsed into one string
    $query = <<<EOQ
SELECT *
WHERE {
  SELECT ?object ?label (GROUP_CONCAT(?dir) as ?direct)
  WHERE {
    $gc {
      <$uri> a skos:Concept .
      OPTIONAL {
        <$uri> $prop* ?object .
        OPTIONAL {
          ?object $prop ?dir .
        }
      }
      OPTIONAL {
        ?object skos:prefLabel ?label .
        $filter
      }
    }
  }
  GROUP BY ?object ?label
}
LIMIT $limit
EOQ;
    $result = $this->client->query($query);
    $ret = array();
    foreach ($result as $row) {
      if (!isset($row->object))
        return array(); // existing concept but no properties
      if (isset($row->label)) {
        $val = array('label'=>$row->label->getValue());
        if ($row->label->getLang() !== $lang)
          $val['label'] .= ' (' . $row->label->getLang() . ')';
      } else {
        $val = array('label'=>null);
      }
      if (isset($row->direct) && $row->direct->getValue() != '') {
        $val['direct'] = explode(' ', $row->direct->getValue());
      }
      // Preventing labels in a non preferred language overriding the preferred language.
      if (isset($row->label) && $row->label->getLang() === $lang || array_key_exists($row->object->getUri(), $ret) === false)
        $ret[$row->object->getUri()] = $val;
    }
    if (sizeof($ret) > 0)
      return $ret; // existing concept, with properties
    else
      return null; // nonexistent concept
  }

  /**
   * Query the narrower concepts of a concept.
   * @param string $uri
   * @param string $lang
   * @return array array of arrays describing each child concept, or null if concept doesn't exist
   */
  public function queryChildren($uri, $lang)
  {
    $uri = is_array($uri) ? $uri[0] : $uri;
    $gc = $this->graphClause;
    $query = <<<EOQ
SELECT ?child ?label ?child ?grandchildren WHERE {
  $gc {
    <$uri> a skos:Concept .
    OPTIONAL {
      <$uri> skos:narrower ?child .
      OPTIONAL {
        ?child skos:prefLabel ?label .
        FILTER (langMatches(lang(?label), "$lang"))
      }
      OPTIONAL { # other language case
        ?child skos:prefLabel ?label .
      }
      BIND ( EXISTS { ?child skos:narrower ?a . } AS ?grandchildren )
    }
  }
}
EOQ;
    $ret = array();
    $result = $this->client->query($query);
    foreach ($result as $row) {
      if (!isset($row->child))
        return array(); // existing concept but no children
        
      $label = null;
      if (isset($row->label)) {
        if ($row->label->getLang() == $lang)
          $label = $row->label->getValue();
        else
          $label = $row->label->getValue() . " (" . $row->label->getLang() . ")";
      }
        
      $ret[] = array(
        'uri' => $row->child->getUri(),
        'prefLabel' => $label,
        'hasChildren' => filter_var($row->grandchildren->getValue(), FILTER_VALIDATE_BOOLEAN),
      );
    }
    if (sizeof($ret) > 0)
      return $ret; // existing concept, with children
    else
      return null; // nonexistent concept
  }

  /**
   * Query the top concepts of a vocabulary.
   * @param string $conceptScheme
   * @param string $lang
   */
  public function queryTopConcepts($conceptScheme='?concept', $lang)
  {
        $gc = $this->graphClause;
    $query = <<<EOQ
SELECT ?top ?label WHERE {
  $gc {
  ?top skos:topConceptOf <$conceptScheme> .
  ?top skos:prefLabel ?label .
  FILTER (langMatches(lang(?label), "$lang"))
  }
}
EOQ;
    $result = $this->client->query($query);
    $ret = array();
    foreach ($result as $row) {
      if (isset($row->top) && isset($row->label)) {
        $ret[$row->top->getUri()] = $row->label->getValue();
      }
    }

    return $ret;
  }

  /**
     * Query for finding the hierarchy for a concept.
     * @param string $uri concept uri.
   * @param string $lang
     * @return an array for the REST controller to encode.
     */
  public function queryParentList($uri, $lang)
  {
    $orig_uri = $uri;
        $gc = $this->graphClause;
    $query = <<<EOQ
SELECT ?broad ?parent ?member ?children ?grandchildren 
(SAMPLE(?lab) as ?label) (SAMPLE(?childlab) as ?childlabel) (SAMPLE(?topcs) AS ?top)
WHERE {
    $gc {
      <$uri> a skos:Concept .
      OPTIONAL {
      <$uri> skos:broader* ?broad .
      OPTIONAL {
        ?broad skos:prefLabel ?lab .
        FILTER (langMatches(lang(?lab), "$lang"))
      }
      OPTIONAL { # fallback - other language case
        ?broad skos:prefLabel ?lab .
      }
      OPTIONAL { ?broad skos:broader ?parent . }
      OPTIONAL { ?broad skos:narrower ?children .
        OPTIONAL {
          ?children skos:prefLabel ?childlab .
          FILTER (langMatches(lang(?childlab), "$lang"))
        }
        OPTIONAL { # fallback - other language case
          ?children skos:prefLabel ?childlab .
        }
      }
      BIND ( EXISTS { ?children skos:narrower ?a . } AS ?grandchildren )
      OPTIONAL { ?broad skos:topConceptOf ?topcs . }
    }
}
}
GROUP BY ?broad ?parent ?member ?children ?grandchildren
EOQ;
    $result = $this->client->query($query);
    $ret = array();
    foreach ($result as $row) {
      if (!isset($row->broad))
        return array(); // existing concept but no broaders
      $uri = $row->broad->getUri();
      if (!isset($ret[$uri])) {
    $ret[$uri] = array('uri'=>$uri);
      }
      if (isset($row->exact)) {
        $ret[$uri]['exact'] = $row->exact->getUri();
      }
      if (isset($row->top)) {
        $ret[$uri]['top'] = $row->top->getUri();
      }
      if (isset($row->children)) {
        if(!isset($ret[$uri]['narrower']))
          $ret[$uri]['narrower'] = array();
        
        $label = null;
        if (isset($row->childlabel)) {
          if ($row->childlabel->getLang() == $lang)
            $label = $row->childlabel->getValue();
          else
            $label = $row->childlabel->getValue() . " (" . $row->childlabel->getLang() . ")";
        }
        
        $child_arr = array(
          'uri' => $row->children->getUri(),
          'label' => $label,
          'hasChildren' => filter_var($row->grandchildren->getValue(), FILTER_VALIDATE_BOOLEAN),
        );
        if(!in_array($child_arr, $ret[$uri]['narrower']))
          $ret[$uri]['narrower'][] = $child_arr;
      }
      $ret[$uri]['prefLabel'] = isset($row->label) ? $row->label->getValue() : null;
      if (isset($row->parent) && (isset($ret[$uri]['broader']) && !in_array($row->parent->getUri(), $ret[$uri]['broader']))) {
        $ret[$uri]['broader'][] = $row->parent->getUri();
      } elseif (isset($row->parent) && !isset($ret[$uri]['broader'])) {
        $ret[$uri]['broader'][] = $row->parent->getUri();
      }
    }
    if (sizeof($ret) > 0)
      return $ret; // existing concept, with children
    else
      return null; // nonexistent concept
  }

  /**
   * return a list of concept group instances, sorted by label
   * @param string $groupClass URI of concept group class
   * @param string $lang language of labels to return
   * @return array Result array with group URI as key and group label as value
   */
  public function listConceptGroups($groupClass, $lang, $flat)
  {
    $gc = $this->graphClause;
    $query = <<<EOQ
SELECT ?group ?super ?label
WHERE {
 $gc {
   ?group a <$groupClass> .
   OPTIONAL { ?group isothes:superGroup ?super . }
   { ?group skos:prefLabel ?label } UNION { ?group rdfs:label ?label }
   FILTER (langMatches(lang(?label), '$lang'))
 }
} ORDER BY lcase(?label)
EOQ;
    $ret = array();
    $result = $this->client->query($query);
    foreach ($result as $row) {
      if (isset($row->super) && !$flat) {
        $superuri = $row->super->getURI();
        if (!isset($ret[$superuri]))
          $ret[$superuri] = array();
        $ret[$superuri]['children'][$row->group->getURI()] = $row->label->getValue();
      } else {
        $ret[$row->group->getURI()]['label'] = $row->label->getValue();
        if ($flat && isset($row->super))
          $ret[$row->group->getURI()]['super'] = $row->super->getURI();
      }
    }
    return $ret;
  }

  /**
   * return a list of concepts in a concept group
   * @param string $groupClass URI of concept group class
   * @param string $group URI of the concept group instance
   * @param string $lang language of labels to return
   * @return array Result array with concept URI as key and concept label as value
   */
  public function listConceptGroupContents($groupClass, $group, $lang)
  {
    $gc = $this->graphClause;
    $query = <<<EOQ
SELECT ?conc ?label
WHERE {
 $gc {
   <$group> a <$groupClass> .
   { <$group> skos:member ?conc . } UNION { ?conc isothes:superGroup <$group> }
   FILTER NOT EXISTS { ?conc owl:deprecated true }
   ?conc skos:prefLabel ?label .
   FILTER (langMatches(lang(?label), '$lang'))
 }
} ORDER BY lcase(?label)
EOQ;
    $ret = array();
    $result = $this->client->query($query);
    foreach ($result as $row) {
      $ret[$row->conc->getURI()] = $row->label->getValue();
    }

    return $ret;
  }
}
