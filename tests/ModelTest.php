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
   * @expectedException \Exception
   * @expectedExceptionMessage thiswillnotbefound.ttl is missing, please provide one.
   */
  public function testConstructorNoArguments()
  {
    require_once 'testconfig.inc';
    new Model(); 
  }

}
