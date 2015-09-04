<?php
/**
 * Copyright (c) 2012-2013 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/**
 * Provides access to the http request information
 */
class Request 
{

  private $lang;
  private $clang;
  private $page;
  private $vocab;
  private $vocabids;
  private $uri;
  private $letter;
  private $model;
  private $request_uri;

  /**
   * Initializes the Request Object
   */
  public function __construct($model)
  {
    $this->model = $model;
  }

  public function getQueryParam($param_name) {
    return filter_input(INPUT_GET, $param_name, FILTER_SANITIZE_STRING);
  }

  public function getServerConstant($param_name) {
    return filter_input(INPUT_SERVER, $param_name, FILTER_SANITIZE_STRING);
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
    $this->clang = $this->verifyContentLang($clang);
  }

  private function verifyContentLang($lang) {
    if ($this->vocab)
      return $this->vocab->verifyVocabularyLanguage($lang);
    return $lang;
  }

  public function getPage() {
    return $this->page;
  }

  /**
   * Sets the page id variable eg. 'groups'
   * @param string $page
   */
  public function setPage($page) {
    if ($page !== '')
      $this->page = $page;
  }

  /**
   * Sets the reuquest uri variable
   * @param string $uri
   */
  public function setRequestUri($uri) {
    $this->request_uri = $uri;
  }
  
  public function getRequestUri() {
    return $this->request_uri;
  }

  public function getLetter() {
    return (isset($this->letter)) ? $this->letter : 'A';
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
   * @param string $uri
   */
  public function setURI($uri) {
    if ($uri !== '')
      $this->uri = $uri;
  }
  
  /**
   * Used to set the vocab id variable when multiple vocabularies have been chosen eg. 'lcsh+yso'
   * @param string $ids
   */
  public function setVocabids($ids) {
    $this->vocabids = $ids;
  }

  public function getVocabid() {
    if ($this->vocabids)
      return $this->vocabids;
    return isset($this->vocab) ? $this->vocab->getId() : '';
  }

  /**
   * Creates a Vocabulary object 
   * @param string $vocabid
   */
  public function setVocab($vocabid) {
    if (strpos($vocabid, ' ') !== false) // if there are multiple vocabularies just storing the string
      $this->setVocabids($vocabid);
    else
      $this->vocab = $this->model->getVocabulary($vocabid);
  }
  
  public function getVocab() {
    return $this->vocab;
  }

}
