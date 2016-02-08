<?php

class ConceptPropertyValueTest extends PHPUnit_Framework_TestCase
{
  private $model; 
  private $concept;
  private $vocab;
    
  protected function setUp() {
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
   * @covers ConceptPropertyValue::__construct
   */
  public function testConstructor() {
    $mockres = $this->getMockBuilder('EasyRdf_Resource')->disableOriginalConstructor()->getMock();
    $propval = new ConceptPropertyValue($this->model, $this->vocab, $mockres, 'skosmos:testProp', 'en');
    $this->assertInstanceOf('ConceptPropertyValue', $propval);
  }

  /**
   * @covers ConceptPropertyValue::getLabel
   */
  public function testGetLabel() {
    $props = $this->concept->getProperties();
    $propvals = $props['skos:narrower']->getValues();
    $this->assertEquals('Crucian carp', $propvals['Crucian carphttp://www.skosmos.skos/test/ta121']->getLabel());
  }
  
  /**
   * @covers ConceptPropertyValue::getType
   */
  public function testGetType() {
    $props = $this->concept->getProperties();
    $propvals = $props['skos:narrower']->getValues();
    $this->assertEquals('skos:narrower', $propvals['Crucian carphttp://www.skosmos.skos/test/ta121']->getType());
  }
  
  /**
   * @covers ConceptPropertyValue::getUri
   */
  public function testGetUri() {
    $props = $this->concept->getProperties();
    $propvals = $props['skos:narrower']->getValues();
    $this->assertEquals('http://www.skosmos.skos/test/ta121', $propvals['Crucian carphttp://www.skosmos.skos/test/ta121']->getUri());
  }
  
  /**
   * @covers ConceptPropertyValue::getVocab
   */
  public function testGetVocab() {
    $props = $this->concept->getProperties();
    $vocab = $this->model->getVocabulary('test');
    $propvals = $props['skos:narrower']->getValues();
    $this->assertEquals($vocab, $propvals['Crucian carphttp://www.skosmos.skos/test/ta121']->getVocab());
  }

  /**
   * @covers ConceptPropertyValue::getVocabName
   */
  public function testGetVocabName() {
    $props = $this->concept->getProperties();
    $propvals = $props['skos:narrower']->getValues();
    $this->assertEquals('Test ontology', $propvals['Crucian carphttp://www.skosmos.skos/test/ta121']->getVocabName());
  }

  /**
   * @covers ConceptPropertyValue::getNotation
   */
  public function testGetNotation() {
    $results = $this->vocab->getConceptInfo('http://www.skosmos.skos/test/ta121', 'en');
    $concept = reset($results);
    $props = $concept->getProperties();
    $propvals = $props['skos:broader']->getValues();
    $this->assertEquals(665, $propvals['Carphttp://www.skosmos.skos/test/ta112']->getNotation());
  }
  
  /**
   * @covers ConceptPropertyValue::__toString
   */
  public function testGetToStringWhenSortByNotationNotSet() {
    $results = $this->vocab->getConceptInfo('http://www.skosmos.skos/test/ta121', 'en');
    $concept = reset($results);
    $props = $concept->getProperties();
    $propvals = $props['skos:broader']->getValues();
    $this->assertEquals('Carp', (string)$propvals['Carphttp://www.skosmos.skos/test/ta112']);
  }
}
