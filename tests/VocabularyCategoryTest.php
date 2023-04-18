<?php

class VocabularyCategoryTest extends PHPUnit\Framework\TestCase
{
  private $model;
  private $mockres;

  protected function setUp() : void
  {
    putenv("LANGUAGE=en_GB.utf8");
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    $this->model = new Model(new GlobalConfig('/../../tests/testconfig.ttl'));
    $this->mockres = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
    $this->mockres->method('localName')->will($this->returnValue('local name'));
  }

  /**
   * @covers VocabularyCategory::__construct
   */
  public function testConstructorWithInvalidParameters() {
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage("Invalid constructor parameter given to DataObject");
    $vcat = new VocabularyCategory('invalid', 'invalid');
    $this->assertNotNull($vcat);
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
