
<?php

class DataObjectTest extends PHPUnit\Framework\TestCase
{
    /**
     * @covers DataObject::__construct
     * @uses DataObject
     */
    public function testConstructorNoArguments()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid constructor parameter given to DataObject");
        $obj = new DataObject(null, null);
        $this->assertNotNull($obj);
    }

}
