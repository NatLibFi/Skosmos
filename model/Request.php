<?php
/**
 * Copyright (c) 2012-2013 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/**
 * Stores session state information
 */
class Request 
{

  private $lang;
  private $clang;
  private $page;
  private $vocabid;
  private $uri;
  private $letter;

  /**
   * Initializes the Request Object
   */
  public function __construct()
  {
  }

  public function getLang() {
    return $this->lang;
  }

  /**
   * Sets the language variable
   * @param string $lang
   */
  public function setLang($lang) {
    if ($lang !== '')
      $this->lang = $lang;
  }

  public function getContentLang() {
    return $this->clang;
  }

  /**
   * Sets the language variable
   * @param string $clang
   */
  public function setContentLang($clang) {
    $this->clang = $clang;
  }

  public function getPage() {
    return $this->page;
  }

  /**
   * Sets the page id variable eg. 'groups'
   * @param string $lang
   */
  public function setPage($page) {
    if ($page !== '')
      $this->page = $page;
  }

  public function getLetter() {
    return $this->letter;
  }

  /**
   * Sets the page id variable eg. 'B'
   * @param string $letter
   */
  public function setLetter($letter) {
    if ($letter !== '')
      $this->letter = $letter;
  }
  
  public function getURI() {
    return $this->uri;
  }

  /**
   * Sets the page id variable eg. 'groups'
   * @param string $lang
   */
  public function setURI($uri) {
    if ($uri !== '')
      $this->uri = $uri;
  }

  public function getVocabid() {
    return $this->vocabid;
  }

  /**
   * Sets the vocab id variable eg. 'stw'
   * @param string $lang
   */
  public function setVocabid($vocabid) {
    $this->vocabid = $vocabid;
  }

}
