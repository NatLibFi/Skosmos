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
  private $prefLabel;
  /** uri of the concept */
  private $uri;
  /** an array of narrower concepts */
  private $narrowerConcepts;
  /** used for storing the hidden labels */
  private $hiddenLabel;

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

  /**
   * Getter for the prefLabel value
   */
  public function getPrefLabel() {
    return $this->prefLabel;
  }
  
  /**
   * Getter for the URI value
   */
  public function getUri() {
    return $this->uri;
  }
  
  /**
   * Getter for the narrower concepts array 
   */
  public function getNarrowerConcepts() {
    return $this->narrowerConcepts;
  }
  
  /**
   * Getter for the hidden prefLabel value
   */
  public function getHiddenLabel() {
    return $this->hiddenLabel;
  }
}
