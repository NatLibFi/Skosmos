
<?php

require_once('model/Model.php');

class VocabularyTest extends PHPUnit_Framework_TestCase
{

  /**
   * @covers \model\Vocabulary::__construct
   * @uses \model\DataObject
   * @expectedException \Exception
   * @expectedExceptionMessage Invalid constructor parameter given to DataObject.
   */
  public function testConstructorNoArguments()
  {
    new DataObject(null, null); 
  }
}
