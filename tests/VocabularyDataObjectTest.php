
<?php

class VocabularyDataObjectTest extends PHPUnit\Framework\TestCase
{

  /**
   * @covers VocabularyDataObject::__construct
   */
  public function testConstructorNoArguments()
  {
    $mockmod = $this->getMockBuilder('Model')->disableOriginalConstructor()->getMock();
    $mockvoc = $this->getMockBuilder('Vocabulary')->disableOriginalConstructor()->getMock();
    $mockres = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
    $vocdao = new VocabularyDataObject($mockmod, $mockvoc, $mockres);
    $this->assertInstanceOf('VocabularyDataObject', $vocdao);
  }

}
