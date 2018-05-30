<?php

class ConceptMappingPropertyValueTest extends PHPUnit\Framework\TestCase
{
  private $model;
  private $concept;
  private $vocab;
  private $props;

  protected function setUp() {
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    bindtextdomain('skosmos', 'resource/translations');
    bind_textdomain_codeset('skosmos', 'UTF-8');
    textdomain('skosmos');

    $this->model = new Model(new GlobalConfig('/../tests/testconfig.ttl'));
    $this->vocab = $this->model->getVocabulary('mapping');
    $concepts = $this->vocab->getConceptInfo('http://www.skosmos.skos/mapping/m1', 'en');
    $this->concept = $concepts[0];
    $this->props = $this->concept->getMappingProperties();
  }

  /**
   * @covers ConceptMappingPropertyValue::__construct
   */
  public function testConstructor() {
    $resourcestub = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
    $mapping = new ConceptMappingPropertyValue($this->model, $this->vocab, $resourcestub, 'skos:exactMatch');
    $this->assertEquals('skos:exactMatch', $mapping->getType());
  }

  /**
   * @covers ConceptMappingPropertyValue::getLabel
   * @covers ConceptMappingPropertyValue::queryLabel
   * @covers DataObject::getExternalLabel
   */
  public function testGetLabelFromExternalVocabulary() {
    $propvals = $this->props['skos:exactMatch']->getValues();
    $this->assertEquals('Eel', $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getLabel()->getValue());
  }

  /**
   * @covers ConceptMappingPropertyValue::getLabel
   * @covers ConceptMappingPropertyValue::queryLabel
   */
  public function testGetLabelResortsToUri() {
    $mockres = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
    $labelmap = array(
      array('en', null),
      array(null, null)
    );
    $mockres->method('label')->will($this->returnValueMap($labelmap));
    $litmap = array(
      array('rdf:value', 'en', null),
      array('rdf:value', null)
    );
    $mockres->method('getLiteral')->will($this->returnValueMap($litmap));
    $mockres->method('getUri')->will($this->returnValue('http://thisdoesntexistatalland.sefsf/2j2h4/'));
    $mapping = new ConceptMappingPropertyValue($this->model, $this->vocab, $mockres, null);
    $this->assertEquals('http://thisdoesntexistatalland.sefsf/2j2h4/', $mapping->getLabel());
  }

  /**
   * @covers ConceptMappingPropertyValue::getLabel
   * @covers ConceptMappingPropertyValue::queryLabel
   */
  public function testGetLabelWithAndWithoutLang() {
    $mockres = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
    $labelmap = array(
      array('en', 'english'),
      array(null, 'default')
    );
    $mockres->method('label')->will($this->returnValueMap($labelmap));
    $mockres->method('getUri')->will($this->returnValue('http://thisdoesntexistatalland.sefsf/2j2h4/'));
    $mapping = new ConceptMappingPropertyValue($this->model, $this->vocab, $mockres, null);
    $this->assertEquals('english', $mapping->getLabel('en'));
    $this->assertEquals('default', $mapping->getLabel());
  }

  /**
   * @covers ConceptMappingPropertyValue::getLabel
   * @covers ConceptMappingPropertyValue::queryLabel
   */
  public function testGetLabelWithLiteralAndLang() {
    $mockres = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
    $labelmap = array(
      array('en', null),
      array(null, null)
    );
    $mockres->method('label')->will($this->returnValueMap($labelmap));
    $litmap = array(
      array('rdf:value', 'en', 'english lit'),
      array('rdf:value', null, 'default lit')
    );
    $mockres->method('getLiteral')->will($this->returnValueMap($litmap));
    $mockres->method('getUri')->will($this->returnValue('http://thisdoesntexistatalland.sefsf/2j2h4/'));
    $mapping = new ConceptMappingPropertyValue($this->model, $this->vocab, $mockres, null);
    $this->assertEquals('english lit', $mapping->getLabel('en'));
    $this->assertEquals('default lit', $mapping->getLabel());
  }

  /**
   * @covers ConceptMappingPropertyValue::getNotation
   */
  public function testGetNotation() {
    $mockres = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
    $mocklit = $this->getMockBuilder('EasyRdf\Literal')->disableOriginalConstructor()->getMock();
    $mocklit->method('getValue')->will($this->returnValue('666'));
    $map = array(
        array('skos:notation', null, null, $mocklit),
        array(null,null,null,null),
    );
    $mockres->method('get')->will($this->returnValueMap($map));
    $mapping = new ConceptMappingPropertyValue($this->model, $this->vocab, $mockres, null);
    $this->assertEquals(666, $mapping->getNotation());
  }

  /**
   * @covers ConceptMappingPropertyValue::getExVocab
   */
  public function testGetExVocab() {
    $propvals = $this->props['skos:exactMatch']->getValues();
    $this->assertInstanceOf('Vocabulary', $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getExVocab());
    $this->assertEquals('test', $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getExVocab()->getId());
  }

  /**
   * @covers ConceptMappingPropertyValue::getVocabName
   */
  public function testGetVocabNameWithExternalVocabulary() {
    $propvals = $this->props['skos:exactMatch']->getValues();
    $this->assertEquals('Test ontology', $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getVocabName());
  }

  /**
   * @covers ConceptMappingPropertyValue::getUri
   */
  public function testGetUri() {
    $propvals = $this->props['skos:exactMatch']->getValues();
    $this->assertEquals('http://www.skosmos.skos/test/ta115', $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getUri());
  }

  /**
   * @covers ConceptMappingPropertyValue::getVocab
   */
  public function testGetVocab() {
    $propvals = $this->props['skos:exactMatch']->getValues();
    $this->assertEquals($this->vocab, $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getVocab());
  }

  /**
   * @covers ConceptMappingPropertyValue::getType
   */
  public function testGetType() {
    $propvals = $this->props['skos:exactMatch']->getValues();
    $this->assertEquals('skos:exactMatch', $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getType());
  }

  /**
   * @covers ConceptMappingPropertyValue::__toString
   */
  public function testToString() {
    $propvals = $this->props['skos:exactMatch']->getValues();
    $this->assertEquals('Eel', $propvals['Eelhttp://www.skosmos.skos/test/ta115']->__toString());
  }

}
