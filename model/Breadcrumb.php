<?php
/**
 * Copyright (c) 2012 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/**
 * A class for breadcrumb concept hierarchy paths
 */
class Breadcrumb
{
  /** a label for the term doesn't need to be a prefLabel */
  public $prefLabel;
  /** uri of the concept */
  public $uri;
  /** an array of narrower concepts */
  public $narrowerConcepts;
  /** used for storing the hidden labels */
  public $hiddenLabel;

  /**
   * Creating a new breadcrumb object requires a uri and a preflabel.
   * @param string $uri
   * @param string $prefLabel
   */
  public function __construct($uri, $prefLabel)
  {
    $this->narrowerConcepts = array();
    $this->uri = $uri;
    $this->prefLabel = $prefLabel;
  }

  /**
   * Hides the prefLabel and stores the value as a hiddenLabel.
   */
  public function hideLabel()
  {
    if(!$this->hiddenLabel)
      $this->hiddenLabel = $this->prefLabel;
    $this->prefLabel = '...';
  }

  /**
   * Used for adding narrower relationships to a Breadcrumb concept
   * @param array $concept
   */
  public function addNarrower($concept)
  {
    $this->narrowerConcepts[$concept->uri] = $concept;
  }
}
