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

  protected function createTextQueryCondition($term, $property='', $lang='')
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
   * Query for concepts with a term starting with the given letter. Also special classes '0-9' (digits),
   * '*!' (special characters) and '*' (everything) are accepted.
   * @param $letter the letter (or special class) to search for
   * @param $lang language of labels
   */
  public function queryConceptsAlphabetical($letter, $lang, $limit=null, $offset=null, $classes=null) {
    if ($letter == '*' || $letter == '0-9' || $letter == '!*') {
      // text index cannot support special character queries, use the generic implementation for these
      return parent::queryConceptAlphabetical($letter, $lang, $limit, $offset, $classes);
    }
  
    $gc = $this->graphClause;
    $limit = ($limit) ? 'LIMIT ' . $limit : '';
    $offset = ($offset) ? 'OFFSET ' . $offset : '';
    $classes = ($classes) ? $classes : array('http://www.w3.org/2004/02/skos/core#Concept');
    $values = $this->formatValues('?type', $classes, 'uri');
    
    // eliminating whitespace and line changes when the conditions aren't needed.
    $limitandoffset = '';
    if ($limit && $offset)
      $limitandoffset = "\n" . $limit . "\n" . $offset;
    elseif ($limit)
      $limitandoffset = "\n" . $limit;
    elseif ($offset)
      $limitandoffset = "\n" . $offset;

    # make text query clause
    $textcond_pref = $use_regex ? '# regex in use' : $this->createTextQueryCondition($letter . '*', 'skos:prefLabel', $lang);
    $textcond_alt = $use_regex ? '# regex in use' : $this->createTextQueryCondition($letter . '*', 'skos:altLabel', $lang);
    $lcletter = mb_strtolower($letter, 'UTF-8'); // convert to lower case, UTF-8 safe

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

}
