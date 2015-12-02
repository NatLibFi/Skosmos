<?php
/**
 * Copyright (c) 2012-2013 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/* Register text: namespace needed for jena-text queries */
EasyRdf_Namespace::set('text', 'http://jena.apache.org/text#');

/**
 * Provides functions tailored to the JenaTextSparql extensions for the Fuseki SPARQL index.
 */
class JenaTextSparql extends GenericSparql
{
  /**
   * How many results to ask from the jena-text index. jena-text defaults to
   * 10000, but that is too little in some cases.
   * See issue reports:
   * https://code.google.com/p/onki-light/issues/detail?id=109 (original, set to 1000000000)
   * https://github.com/NatLibFi/Skosmos/issues/41 (reduced to 100000 because of bad performance)
   */
  private $MAX_N = 100000;
  
  /*
   * Characters that need to be quoted for the Lucene query parser.
   * See http://lucene.apache.org/core/4_10_1/queryparser/org/apache/lucene/queryparser/classic/package-summary.html#Escaping_Special_Characters
   */
  private $LUCENE_ESCAPE_CHARS = ' +-&|!(){}[]^"~?:\\/'; /* note: don't include * because we want wildcard expansion

 /**
   * Make a jena-text query condition that narrows the amount of search
   * results in term searches
   *
   * @param string $term search term
   * @param string $property property to search (e.g. 'skos:prefLabel'), or '' for default
   * @return string SPARQL text search clause
   */

  private function createTextQueryCondition($term, $property='', $lang='')
  {
    // construct the lucene search term for jena-text
    
    // 1. Ensure characters with special meaning in Lucene are escaped
    $lucenemap = array();
    foreach (str_split($this->LUCENE_ESCAPE_CHARS) as $char) {
      $lucenemap[$char] = '\\' . $char; // escape with a backslash
    }
    $term = strtr($term, $lucenemap);
    
    // 2. Ensure proper SPARQL quoting
    $term = str_replace('\\', '\\\\', $term); // escape backslashes
    $term = str_replace("'", "\\'", $term); // escape single quotes
    
    $lang_clause = empty($lang) ? '' : "'lang:$lang'";
    
    $max_results = $this->MAX_N;

    return "{ (?s ?score ?literal) text:query ($property '$term' $lang_clause $max_results) }";
  }

    /**
     * Generates the jena-text-specific sparql query used for rendering the alphabetical index.
     * @param string $letter the letter (or special class) to search for
     * @param string $lang language of labels
     * @param integer $limit limits the amount of results
     * @param integer $offset offsets the result set
     * @param array $classes
     * @return string sparql query
     */

    /**
     * Query for concepts using a search term, with the jena-text index.
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
     * @return string sparql query
     */
    protected function generateConceptSearchQuery($term, $vocabs, $lang, $search_lang, $limit, $offset, $arrayClass, $types, $parent, $group, $hidden, $fields) {
        $gc = $this->graphClause;
        $limitandoffset = $this->formatLimitAndOffset($limit, $offset);

        $formattedtype = $this->formatTypes($types, $arrayClass);

        $formattedbroader = $this->formatBroader($lang, $fields);
        $extravars = $formattedbroader['extravars'];
        $extrafields = $formattedbroader['extrafields'];

        // extra conditions for label language, if specified
        $labelcond_label = ($lang) ? "langMatches(lang(?label), '$lang')" : "langMatches(lang(?label), lang(?literal))";
        // if search language and UI/display language differ, must also consider case where there is no prefLabel in
        // the display language; in that case, should use the label with the same language as the matched label
        $labelcond_fallback = ($search_lang != $lang) ?
        "OPTIONAL { # in case previous OPTIONAL block gives no labels
       ?s skos:prefLabel ?label .
       FILTER (langMatches(lang(?label), lang(?literal))) }" : "";

        // extra conditions for parent and group, if specified
        $parentcond = ($parent) ? "?s skos:broader+ <$parent> ." : "";
        $groupcond = ($group) ? "<$group> skos:member ?s ." : "";
        $pgcond = $parentcond . $groupcond;

        $orderextra = $this->isDefaultEndpoint() ? $this->graph : '';

        # make VALUES clauses
        $props = array('skos:prefLabel', 'skos:altLabel');
        if ($hidden) {
            $props[] = 'skos:hiddenLabel';
        }
        $values_prop = $this->formatValues('?prop', $props);

        $values_graph = $this->formatValuesGraph($vocabs);

        while (strpos($term, '**') !== false) {
            $term = str_replace('**', '*', $term);
        }
        // removes futile asterisks

        # make text query clauses
        $textcond = $this->createTextQueryCondition($term, '?prop', $search_lang);

        $query = <<<EOQ
SELECT DISTINCT ?s ?label ?plabel ?alabel ?hlabel ?graph (GROUP_CONCAT(DISTINCT ?type) as ?types)
$extravars
WHERE {
 $gc {
  $textcond
  $formattedtype
  { $pgcond
   ?s rdf:type ?type .
   OPTIONAL {
    ?s skos:prefLabel ?label .
    FILTER ($labelcond_label)
   } $labelcond_fallback $extrafields
  }
  FILTER NOT EXISTS { ?s owl:deprecated true }
  $values_prop
 }
 BIND(IF(?prop = skos:prefLabel && ?literal != ?label, ?literal, ?unbound) as ?plabel)
 BIND(IF(?prop = skos:altLabel, ?literal, ?unbound) as ?alabel)
 BIND(IF(?prop = skos:hiddenLabel, ?literal, ?unbound) as ?hlabel)
}
GROUP BY ?literal ?s ?label ?plabel ?alabel ?hlabel ?graph ?prop
ORDER BY lcase(str(?literal)) lang(?literal) $orderextra $limitandoffset
$values_graph
EOQ;
        return $query;
    }


  public function generateAlphabeticalListQuery($letter, $lang, $limit=null, $offset=null, $classes=null)
  {
    if ($letter == '*' || $letter == '0-9' || $letter == '!*') {
      // text index cannot support special character queries, use the generic implementation for these
      return parent::generateAlphabeticalListQuery($letter, $lang, $limit, $offset, $classes);
    }
  
    $gc = $this->graphClause;
    $classes = ($classes) ? $classes : array('http://www.w3.org/2004/02/skos/core#Concept');
    $values = $this->formatValues('?type', $classes, 'uri');
    $limitandoffset = $this->formatLimitAndOffset($limit, $offset);
    
    # make text query clause
    $textcond_pref = $this->createTextQueryCondition($letter . '*', 'skos:prefLabel', $lang);
    $textcond_alt = $this->createTextQueryCondition($letter . '*', 'skos:altLabel', $lang);

    $query = <<<EOQ
SELECT DISTINCT ?s ?label ?alabel
WHERE {
  $gc {
    {
      $textcond_pref
      BIND(?literal as ?label)
    }
    UNION
    {
      $textcond_alt
      BIND(?literal as ?alabel)
      {
        ?s skos:prefLabel ?label .
        FILTER (langMatches(lang(?label), '$lang'))
      }
    }
    ?s a ?type .
    FILTER NOT EXISTS { ?s owl:deprecated true }
  } $values
}
ORDER BY LCASE(?literal) $limitandoffset
EOQ;
    return $query;
  }

}
