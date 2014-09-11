
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
  
  /**
   * @covers DataObject::arbitrarySort
   * @expectedException \Exception
   */
  public function testArbitrarySort()
  {
    $model = new Model();
    $resource = new EasyRdf_Resource('http://www.yso.fi/onto/test/');
    $do = new DataObject($model, $resource); 
    $do->arbitrarySort();
  }

}
