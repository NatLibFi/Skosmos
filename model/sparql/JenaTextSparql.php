<?php

/* Register text: namespace needed for jena-text queries */
EasyRdf_Namespace::set('text', 'http://jena.apache.org/text#'); // @codeCoverageIgnore

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
    const MAX_N = 100000;

    /*
     * Characters that need to be quoted for the Lucene query parser.
     * See http://lucene.apache.org/core/4_10_1/queryparser/org/apache/lucene/queryparser/classic/package-summary.html#Escaping_Special_Characters
     */
    const LUCENE_ESCAPE_CHARS = ' +-&|!(){}[]^"~?:\\/'; /* note: don't include * because we want wildcard expansion

    /**
     * Make a jena-text query condition that narrows the amount of search
     * results in term searches
     *
     * @param string $term search term
     * @param string $property property to search (e.g. 'skos:prefLabel'), or '' for default
     * @return string SPARQL text search clause
     */

    private function createTextQueryCondition($term, $property = '', $lang = '')
    {
        // construct the lucene search term for jena-text

        // 1. Ensure characters with special meaning in Lucene are escaped
        $lucenemap = array();
        foreach (str_split(self::LUCENE_ESCAPE_CHARS) as $char) {
            $lucenemap[$char] = '\\' . $char; // escape with a backslash
        }
        $term = strtr($term, $lucenemap);

        // 2. Ensure proper SPARQL quoting
        $term = str_replace('\\', '\\\\', $term); // escape backslashes
        $term = str_replace("'", "\\'", $term); // escape single quotes

        $langClause = empty($lang) ? '' : "'lang:$lang'";

        $maxResults = self::MAX_N;

        return "(?s ?score ?match) text:query ($property '$term' $langClause $maxResults) .";
    }

    /**
     * Generate jena-text search condition for matching labels in SPARQL
     * @param string $term search term
     * @param string $searchLang language code used for matching labels (null means any language)
     * @return string sparql query snippet
     */
    protected function generateConceptSearchQueryCondition($term, $searchLang)
    {
        # make text query clauses
        $textcond = $this->createTextQueryCondition($term, '?prop', $searchLang);
        
        if ($this->isDefaultEndpoint()) {
            # if doing a global search, we should target the union graph instead of a specific graph
            $textcond = "GRAPH <urn:x-arq:UnionGraph> { $textcond }";
        }
        
        return $textcond;    
    }

    /**
     * Generates the jena-text-specific sparql query used for rendering the alphabetical index.
     * @param string $letter the letter (or special class) to search for
     * @param string $lang language of labels
     * @param integer $limit limits the amount of results
     * @param integer $offset offsets the result set
     * @param array|null $classes
     * @return string sparql query
     */

    public function generateAlphabeticalListQuery($letter, $lang, $limit = null, $offset = null, $classes = null)
    {
        if ($letter == '*' || $letter == '0-9' || $letter == '!*') {
            // text index cannot support special character queries, use the generic implementation for these
            return parent::generateAlphabeticalListQuery($letter, $lang, $limit, $offset, $classes);
        }

        $fcl = $this->generateFromClause();
        $classes = ($classes) ? $classes : array('http://www.w3.org/2004/02/skos/core#Concept');
        $values = $this->formatValues('?type', $classes, 'uri');
        $limitandoffset = $this->formatLimitAndOffset($limit, $offset);

        # make text query clause
        $lcletter = mb_strtolower($letter, 'UTF-8'); // convert to lower case, UTF-8 safe
        $textcondPref = $this->createTextQueryCondition($letter . '*', 'skos:prefLabel', $lang);
        $textcondAlt = $this->createTextQueryCondition($letter . '*', 'skos:altLabel', $lang);

        $query = <<<EOQ
SELECT DISTINCT ?s ?label ?alabel $fcl
WHERE {
  {
    $textcondPref
    FILTER(STRSTARTS(LCASE(STR(?match)), '$lcletter'))
    FILTER EXISTS { ?s skos:prefLabel ?match }
    BIND(?match as ?label)
  }
  UNION
  {
    $textcondAlt
    FILTER(STRSTARTS(LCASE(STR(?match)), '$lcletter'))
    FILTER EXISTS { ?s skos:altLabel ?match }
    BIND(?match as ?alabel)
    {
      ?s skos:prefLabel ?label .
      FILTER (langMatches(LANG(?label), '$lang'))
    }
  }
  ?s a ?type .
  FILTER NOT EXISTS { ?s owl:deprecated true }
  $values
}
ORDER BY LCASE(?match) $limitandoffset
EOQ;
        return $query;
    }

}
