<?php
/**
 * Copyright (c) 2012-2013 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/**
 * Provides functions tailored to the Bigdata SPARQL index.
 */
class BigdataSparql extends GenericSparql
{
  /**
   * Formats a BINDINGS clause (obsolete SPARQL 1.1 draft, supported by Bigdata)
   * which states that the variable should be bound to one of the constants given.
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

    return "BINDINGS $varname { $values }";
  }

}
