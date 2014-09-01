<?php

require_once 'model/Model.php';

class ModelTest extends PHPUnit_Framework_TestCase
{
  /**
   * @expectedException \Exception
   * @expectedExceptionMessage Use of undefined constant VOCABULARIES_FILE - assumed 'VOCABULARIES_FILE'
   */
  public function testConstructorNoVocabulariesConfigFile()
  {
    new Model(); 
  }
  
  /**
   * @depends testConstructorNoVocabulariesConfigFile
   */
  public function testConstructorWithConfig()
  {
    require_once 'testconfig.inc';
    new Model(); 
  }
  
  /**
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyList() {
    $b = new Model(); 
    $vocabs = $b->getVocabularyList();
    foreach($vocabs as $vocab)
      $this->assertInstanceOf('Vocabulary', $vocab);
  }
  
  /**
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyCategories() {
    $b = new Model(); 
    $vocabs = $b->getVocabularyList();
    foreach($vocabs as $vocab)
      var_dump($vocab);
      //$this->assertInstanceOf('Vocabulary', $vocab);
  }

  
  /**
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyById() {
    $b = new Model(); 
    $vocab = $b->getVocabulary('test');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }
  
  /**
   * @depends testConstructorWithConfig
   * @expectedException \Exception
   * @expectedExceptionMessage Vocabulary id 'thisshouldnotbefound' not found in configuration 
   */
  public function testGetVocabularyByFalseId() {
    $b = new Model(); 
    $vocab = $b->getVocabulary('thisshouldnotbefound');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }

  /**
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyByGraphUri() {
    $b = new Model(); 
    $vocab = $b->getVocabularyByGraph('http://www.yso.fi/onto/test/');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }
  
  /**
   * @depends testConstructorWithConfig
   */
  public function testGuessVocabularyFromURI() {
    $b = new Model();
    $vocab = $b->guessVocabularyFromURI('http://www.yso.fi/onto/test/T21329');
    $this->assertInstanceOf('Vocabulary', $vocab);
    $this->assertEquals('test', $vocab->getId());
  }

}
