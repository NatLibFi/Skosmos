<?php

class VocabularyCategoryTest extends PHPUnit_Framework_TestCase
{
  private $model; 
  private $mockres;

  protected function setUp() {
    require_once 'testconfig.inc';
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    $this->model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $this->mockres = $this->getMockBuilder('EasyRdf_Resource')->disableOriginalConstructor()->getMock();
    $this->mockres->method('localName')->will($this->returnValue('local name'));
  }
  
  /**
   * @covers VocabularyCategory::__construct
   * @expectedException Exception
   * @expectedExceptionMessage Invalid constructor parameter given to DataObject. 
   */
  public function testConstructorWithInvalidParameters() {
    new VocabularyCategory('invalid', 'invalid');
  }
  
  /**
   * @covers VocabularyCategory::__construct
   */
  public function testConstructor() {
    $cat = new VocabularyCategory($this->model, $this->mockres);
    $this->assertInstanceOf('VocabularyCategory', $cat);
  }
  
  /**
   * @covers VocabularyCategory::getVocabularies
   */
  public function testGetVocabularies() {
    $cat = new VocabularyCategory($this->model, null);
    $vocs = $cat->getVocabularies();
    foreach ($vocs as $voc) {
      $this->assertInstanceOf('Vocabulary', $voc);
    }
  }
  
  /**
   * @covers VocabularyCategory::getTitle
   */
  public function testGetTitle() {
    $cat = new VocabularyCategory($this->model, $this->mockres);
    $this->assertEquals('local name', $cat->getTitle());
  }
  
  /**
   * @covers VocabularyCategory::getTitle
   */
  public function testGetTitleWhenNoResourceGiven() {
    $cat = new VocabularyCategory($this->model, null);
    $this->assertEquals('Vocabularies', $cat->getTitle());
  }

}
