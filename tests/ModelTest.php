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
   * @covers \model\Model::__construct
   * @depends testConstructorWithConfig
   */
  public function DonottestGetVocabularyList() {
    $b = new Model(); 
    var_dump($b->getVocabularyList(true));
  }
  
  /**
   * @covers \model\Model::__construct
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyById() {
    $b = new Model(); 
    $vocab = $b->getVocabulary('test');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }

}
