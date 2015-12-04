<?php

class RequestTest extends PHPUnit_Framework_TestCase
{
  private $model;
  private $request;
  
  protected function setUp() {
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    bindtextdomain('skosmos', 'resource/translations');
    bind_textdomain_codeset('skosmos', 'UTF-8');
    textdomain('skosmos');

    $config = new GlobalConfig('/../tests/testconfig.inc');
    $this->model = new Model($config);
    $this->request = new Request($this->model);
    $search_results = $this->model->searchConceptsAndInfo('carp', 'test', 'en', 'en'); 
  }
  
  
  /**
   * @covers Request::setVocab
   * @covers Request::getVocab
   */
  public function testSetVocab() {
    $this->request->setVocab('test');
    $this->assertInstanceOf('Vocabulary', $this->request->getVocab());
  }
  
  /**
   * @covers Request::getVocabid
   */
  public function testGetVocabid() {
    $this->request->setVocab('test');
    $this->assertEquals('test', $this->request->getVocabId());
  }
  
  /**
   * @covers Request::setUri
   * @covers Request::getUri
   */
  public function testSetAndGetUri() {
    $this->request->setVocab('test');
    $this->request->setUri('www.skosmos.org');
    $this->assertEquals('www.skosmos.org', $this->request->getUri());
  }
  
  /**
   * @covers Request::setContentLang
   * @covers Request::getContentLang
   * @covers Request::verifyContentLang
   */
  public function testSetContentLang() {
    $this->request->setVocab('test');
    $clang = $this->request->setContentLang('en');
    $this->assertEquals('en', $this->request->getContentLang());
  }
  
  /**
   * @covers Request::setContentLang
   * @covers Request::verifyContentLang
   */
  public function testSetContentLangWhenNoVocabularyAvailable() {
    $clang = $this->request->setContentLang('fi');
    $this->assertEquals('fi', $this->request->getContentLang());
  }
  
  /**
   * @covers Request::setContentLang
   * @covers Request::getContentLang
   * @covers Request::verifyContentLang
   */
  public function testSetContentLangWithUnsupportedLanguage() {
    $this->request->setVocab('test');
    $clang = $this->request->setContentLang('ru');
    $this->assertEquals('en', $this->request->getContentLang());
  }
  
  /**
   * @covers Request::setLang
   * @covers Request::getLang
   */
  public function testSetAndGetLang() {
    $this->request->setVocab('test');
    $clang = $this->request->setLang('en');
    $this->assertEquals('en', $this->request->getLang());
  }
  
  /**
   * @covers Request::getLetter
   */
  public function testGetLetterWhenNotSet() {
    $this->request->setVocab('test');
    $this->assertEquals('A', $this->request->getLetter());
  }
  
  /**
   * @covers Request::setLetter
   * @covers Request::getLetter
   */
  public function testSetAndGetLetter() {
    $this->request->setVocab('test');
    $this->request->setLetter('X');
    $this->assertEquals('X', $this->request->getLetter());
  }
  
  /**
   * @covers Request::setPage
   * @covers Request::getPage
   */
  public function testSetAndGetPage() {
    $this->request->setPage('index');
    $this->assertEquals('index', $this->request->getPage());
  }
  
}
