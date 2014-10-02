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

  protected function createTextQueryCondition($term, $property='')
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

    return "{ ?s text:query ($property '$term' $this->MAX_N) }";
  }
}
