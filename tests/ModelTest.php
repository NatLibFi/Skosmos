<?php

require_once 'model/Model.php';

class ModelTest extends PHPUnit_Framework_TestCase
{
  /**
   * @covers Model::__construct
   * @expectedException \Exception
   * @expectedExceptionMessage Use of undefined constant VOCABULARIES_FILE - assumed 'VOCABULARIES_FILE'
   */
  public function testConstructorNoVocabulariesConfigFile()
  {
    new Model(); 
  }
  
  /**
   * @covers Model::__construct
   * @depends testConstructorNoVocabulariesConfigFile
   */
  public function testConstructorWithConfig()
  {
    require_once 'testconfig.inc';
    new Model(); 
  }
  
  /**
   * @covers Model::getVocabularyList
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyList() {
    $model = new Model(); 
    $vocabs = $model->getVocabularyList();
    foreach($vocabs as $vocab)
      $this->assertInstanceOf('Vocabulary', $vocab);
  }
  
  /**
   * @covers Model::getVocabularyList
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyCategories() {
    $model = new Model(); 
    $vocabs = $model->getVocabularyList();
    foreach($vocabs as $vocab)
      $this->assertInstanceOf('VocabularyCategory', $vocab);
  }

  
  /**
   * @covers Model::getVocabulary
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyById() {
    $model = new Model(); 
    $vocab = $model->getVocabulary('test');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }
  
  /**
   * @covers Model::getVocabulary
   * @depends testConstructorWithConfig
   * @expectedException \Exception
   * @expectedExceptionMessage Vocabulary id 'thisshouldnotbefound' not found in configuration 
   */
  public function testGetVocabularyByFalseId() {
    $model = new Model(); 
    $vocab = $model->getVocabulary('thisshouldnotbefound');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }

  /**
   * @covers Model::getVocabularyByGraph
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyByGraphUri() {
    $model = new Model(); 
    $vocab = $model->getVocabularyByGraph('http://www.yso.fi/onto/test/');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }
  
  /**
   * @covers Model::getVocabularyByGraph
   * @depends testConstructorWithConfig
   * @expectedException \Exception
   * @expectedExceptionMessage no vocabulary found for graph http://no/address and endpoint http://api.dev.finto.fi/sparql
   */
  public function testGetVocabularyByInvalidGraphUri() {
    $model = new Model(); 
    $vocab = $model->getVocabularyByGraph('http://no/address');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }
  
  /**
   * @covers Model::guessVocabularyFromURI
   * @depends testConstructorWithConfig
   */
  public function testGuessVocabularyFromURI() {
    $model = new Model();
    $vocab = $model->guessVocabularyFromURI('http://www.yso.fi/onto/test/T21329');
    $this->assertInstanceOf('Vocabulary', $vocab);
    $this->assertEquals('test', $vocab->getId());
  }
  
  /**
   * @covers Model::guessVocabularyFromURI
   * @depends testConstructorWithConfig
   */
  public function testGuessVocabularyFromURIThatIsNotFound() {
    $model = new Model();
    $vocab = $model->guessVocabularyFromURI('http://doesnot/exist');
    $this->assertEquals(null, $vocab);
  }

  /**
   * @covers Model::getDefaultSparql
   * @depends testConstructorWithConfig
   */
  public function testGetDefaultSparql() {
    $model = new Model();
    $sparql = $model->getDefaultSparql();
    $this->assertInstanceOf('GenericSparql', $sparql);
  }
  
  /**
   * @covers Model::getBreadCrumbs
   * @covers Model::getCrumbs
   * @depends testConstructorWithConfig
   */
  public function testGetBreadCrumbs() {
    $model = new Model();
    $vocabstub = $this->getMock('Vocabulary', array('getConceptTransitiveBroaders'), array(null, null));
    $vocabstub->method('getConceptTransitiveBroaders')->willReturn(array ( 'http://www.yso.fi/onto/yso/p4762' => array ( 'label' => 'objects', ), 'http://www.yso.fi/onto/yso/p1674' => array ( 'label' => 'physical whole', 'direct' => array ( 0 => 'http://www.yso.fi/onto/yso/p4762', ), ), 'http://www.yso.fi/onto/yso/p14606' => array ( 'label' => 'layers', 'direct' => array ( 0 => 'http://www.yso.fi/onto/yso/p1674', ), ), ));
    $result = $model->getBreadCrumbs($vocabstub, 'en', 'http://www.yso.fi/onto/yso/p14606');
    foreach($result['breadcrumbs'][0] as $crumb)    
      $this->assertInstanceOf('Breadcrumb', $crumb);
  }

}
