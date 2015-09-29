<?php

class ConceptMappingPropertyValueTest extends PHPUnit_Framework_TestCase
{
  private $model; 
  private $concept;
  private $vocab;
  private $props;

  protected function setUp() {
    require_once 'testconfig.inc';
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    bindtextdomain('skosmos', 'resource/translations');
    bind_textdomain_codeset('skosmos', 'UTF-8');
    textdomain('skosmos');

    $this->model = new Model();
    $this->vocab = $this->model->getVocabulary('mapping');
    $concepts = $this->vocab->getConceptInfo('http://www.skosmos.skos/mapping/m1', 'en');
    $this->concept = $concepts[0];
    $this->props = $this->concept->getMappingProperties();
  }

  /**
   * @covers ConceptMappingPropertyValue::getLabel
   * @covers DataObject::getExternalLabel
   */
  public function testGetLabelFromExternalVocabulary() {
    $propvals = $this->props['skos:exactMatch']->getValues();
    $this->assertEquals('Eel', $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getLabel()->getValue());
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
