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

 /**
   * Make a jena-text query condition that narrows the amount of search
   * results in term searches
   *
   * @param string $term search term
   * @return string SPARQL text search clause
   */

  protected function createTextQueryCondition($term)
  {
    // construct the lucene search term for jena-text
    $term = str_replace('-', ' ', $term); // split words with hyphens to separate words
    $term = str_replace(':', ' ', $term); // split words with colons to separate words
    $term = str_replace('/', ' ', $term); // split words with slashes to separate words
    $term = str_replace('(', ' ', $term); // split words with parentheses to separate words
    $term = str_replace(')', ' ', $term); // split words with parentheses to separate words
    $term = str_replace('\'', '\\\'', $term); // ensure single quotes are quoted
    $qwords = array();
    foreach (explode(' ', $term) as $word) {
      if (preg_match('/^\p{L}[\p{L}_.-]*\*?$/u', $word) == 1)
        $qwords[] = $word;
    }
    if (sizeof($qwords) == 0) return '# no suitable terms - text index disabled';
    $term = implode(' ', $qwords);

    return "{ ?s text:query ('$term' $this->MAX_N) }";
  }
}
