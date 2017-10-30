<?php

class ConceptPropertyValueLiteralTest extends PHPUnit_Framework_TestCase
{
  private $model; 
  private $concept;
  private $vocab;

  protected function setUp() {
    require_once 'testconfig.inc';
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    bindtextdomain('skosmos', 'resource/translations');
    bind_textdomain_codeset('skosmos', 'UTF-8');
    textdomain('skosmos');

    $this->model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $this->vocab = $this->model->getVocabulary('test');
    $results = $this->vocab->getConceptInfo('http://www.skosmos.skos/test/ta112', 'en');
    $this->concept = reset($results);
  }

  /**
   * @covers ConceptPropertyValueLiteral::__construct
   */
  public function testConstructor() {
    $litmock = $this->getMockBuilder('EasyRdf\Literal')->disableOriginalConstructor()->getMock();
    $prop = new ConceptPropertyValueLiteral($litmock, 'skosmos:someProperty');
    $this->assertEquals(null, $prop->__toString());
  }

  /**
   * @covers ConceptPropertyValueLiteral::getLabel
   */
  public function testGetLabel() {
    $props = $this->concept->getProperties();
    $propvals = $props['skos:scopeNote']->getValues();
    $this->assertEquals('Carp are oily freshwater fish', $propvals['Carp are oily freshwater fish']->getLabel());
  }

  /**
   * @covers ConceptPropertyValueLiteral::getLabel
   */
  public function testGetLabelThatIsADate() {
    $vocab = $this->model->getVocabulary('dates');
    $concepts = $vocab->getConceptInfo("http://www.skosmos.skos/date/d1", "en");
    $props = $concepts[0]->getProperties();
    $propvals = $props['http://www.skosmos.skos/date/ownDate']->getValues();
    $this->assertContains('8/8/15', $propvals['8/8/15']->getLabel());
  }

  /**
   * @covers ConceptPropertyValueLiteral::getLabel
   * @expectedException PHPUnit_Framework_Error
   */
  public function testGetLabelThatIsABrokenDate() {
    $vocab = $this->model->getVocabulary('dates');
    $concepts = $vocab->getConceptInfo("http://www.skosmos.skos/date/d2", "en");
    $props = $concepts[0]->getProperties();
    $propvals = $props['http://www.skosmos.skos/date/ownDate']->getValues();
  }

  /**
   * @covers ConceptPropertyValueLiteral::getLang
   */
  public function testGetLang() {
    $props = $this->concept->getProperties();
    $propvals = $props['skos:scopeNote']->getValues();
    $this->assertEquals('en', $propvals['Carp are oily freshwater fish']->getLang());
  }

  /**
   * @covers ConceptPropertyValueLiteral::getType
   */
  public function testGetType() {
    $props = $this->concept->getProperties();
    $propvals = $props['skos:scopeNote']->getValues();
    $this->assertEquals('skos:scopeNote', $propvals['Carp are oily freshwater fish']->getType());
  }

  /**
   * @covers ConceptPropertyValueLiteral::getUri
   */
  public function testGetUri() {
    $props = $this->concept->getProperties();
    $propvals = $props['skos:scopeNote']->getValues();
    $this->assertEquals(null, $propvals['Carp are oily freshwater fish']->getUri());
  }

  /**
   * @covers ConceptPropertyValueLiteral::__toString
   */
  public function testToString() {
    $props = $this->concept->getProperties();
    $propvals = $props['skos:scopeNote']->getValues();
    $this->assertEquals('Carp are oily freshwater fish', $propvals['Carp are oily freshwater fish']);
  }

  /**
   * @covers ConceptPropertyValueLiteral::__toString
   */
  public function testToStringEmpty() {
    $litmock = $this->getMockBuilder('EasyRdf\Literal')->disableOriginalConstructor()->getMock();
    $lit = new ConceptPropertyValueLiteral($litmock, 'skosmos:testType');
    $this->assertEquals('', $lit);
  }

  /**
   * @covers ConceptPropertyValueLiteral::getNotation
   */
  public function testGetNotation() {
    $litmock = $this->getMockBuilder('EasyRdf\Literal')->disableOriginalConstructor()->getMock();
    $lit = new ConceptPropertyValueLiteral($litmock, 'skosmos:testType');
    $this->assertEquals(null, $lit->getNotation());
  }

  /**
   * @covers ConceptPropertyValueLiteral::getContainsHtml
   */
  public function testGetContainsHtmlWhenThereIsNone() {
    $litmock = $this->getMockBuilder('EasyRdf\Literal')->disableOriginalConstructor()->getMock();
    $litmock->method('getValue')->will($this->returnValue('a regular literal'));
    $lit = new ConceptPropertyValueLiteral($litmock, 'skosmos:testType');
    $this->assertFalse($lit->getContainsHtml());
  }
  
  /**
   * @covers ConceptPropertyValueLiteral::getContainsHtml
   */
  public function testGetContainsHtmlWhenThereIsOnlyAOpeningTag() {
    $litmock = $this->getMockBuilder('EasyRdf\Literal')->disableOriginalConstructor()->getMock();
    $litmock->method('getValue')->will($this->returnValue('a <a href=\"http://skosmos.org\"> literal with broken html'));
    $lit = new ConceptPropertyValueLiteral($litmock, 'skosmos:testType');
    $this->assertFalse($lit->getContainsHtml());
  }
  
  /**
   * @covers ConceptPropertyValueLiteral::getContainsHtml
   */
  public function testGetContainsHtml() {
    $litmock = $this->getMockBuilder('EasyRdf\Literal')->disableOriginalConstructor()->getMock();
    $litmock->method('getValue')->will($this->returnValue('a <a href=\"http://skosmos.org\">literal</a> with valid html'));
    $lit = new ConceptPropertyValueLiteral($litmock, 'skosmos:testType');
    $this->assertTrue($lit->getContainsHtml());
  }
}
