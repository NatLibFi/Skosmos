
<?php

class DataObjectTest extends PHPUnit\Framework\TestCase
{
    /**
     * @covers DataObject::__construct
     * @uses DataObject
     */
    public function testConstructorNoArguments()
    {
        $this->expectException(TypeError::class);
        $obj = new DataObject(null, null);
        $this->assertNotNull($obj);
    }

}
