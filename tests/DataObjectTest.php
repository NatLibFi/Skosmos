
<?php

class DataObjectTest extends PHPUnit_Framework_TestCase
{

  /**
   * @covers DataObject::__construct
   * @uses DataObject
   * @expectedException \Exception
   * @expectedExceptionMessage Invalid constructor parameter given to DataObject.
   */
  public function testConstructorNoArguments()
  {
    new DataObject(null, null); 
  }
}
