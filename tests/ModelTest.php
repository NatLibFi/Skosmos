<?php

require_once 'model/Model.php';

class ModelTest extends PHPUnit_Framework_TestCase
{
  /**
   * @covers \model\Model::__construct
   * @expectedException \Exception
   * @expectedExceptionMessage Use of undefined constant VOCABULARIES_FILE - assumed 'VOCABULARIES_FILE'
   */
  public function testConstructorNoVocabulariesConfigFile()
  {
    new Model(); 
  }
  
  /**
   * @covers \model\Model::__construct
   * @depends testConstructorNoVocabulariesConfigFile
   */
  public function testConstructorWithConfig()
  {
    require_once 'testconfig.inc';
    new Model(); 
  }
  
  /**
   * @covers \model\Model::getVocabularyList
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyList() {
    $b = new Model(); 
    $vocabs = $b->getVocabularyList();
    foreach($vocabs as $vocab)
      $this->assertInstanceOf('Vocabulary', $vocab);
  }
  
  /**
   * @covers \model\Model::getVocabulary
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyById() {
    $b = new Model(); 
    $vocab = $b->getVocabulary('test');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }

  /**
   * @covers \model\Model::__getVocabularyByGraph
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyByGraphUri() {
    $b = new Model(); 
    $vocab = $b->getVocabularyByGraph('http://www.yso.fi/onto/test/');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }
  
  /**
   * @covers \model\Model::guessVocabularyFromURI
   * @depends testConstructorWithConfig
   */
  public function testGuessVocabularyFromURI() {
    $b = new Model();
    $vocab = $b->guessVocabularyFromURI('http://www.yso.fi/onto/test/T21329');
    $this->assertInstanceOf('Vocabulary', $vocab);
    $this->assertEquals('test', $vocab->getId());
  }

}
