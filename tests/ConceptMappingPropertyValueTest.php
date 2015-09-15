<?php

class ConceptMappingPropertyValueTest extends PHPUnit_Framework_TestCase
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

    $this->model = new Model();
    $search_results = $this->model->searchConceptsAndInfo('carp', 'test', 'en', 'en'); 
    $this->concept = $search_results['results'][0];
    $this->vocab = $this->model->getVocabulary('mapping');
  }

  /**
   * @covers ConceptMappingPropertyValue::getLabel
   * @covers DataObject::getExternalLabel
   */
  public function testGetLabelFromExternalVocabulary() {
    $concepts = $this->vocab->getConceptInfo('http://www.skosmos.skos/mapping/m1', 'en');
    $concept = $concepts[0];
    $props = $concept->getMappingProperties();
    $propvals = $props['skos:exactMatch']->getValues();
    $this->assertEquals('Eel', $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getLabel()->getValue());
  }

  /**
   * @covers ConceptMappingPropertyValue::getExVocab
   */
  public function testGetExVocab() {
    $concepts = $this->vocab->getConceptInfo('http://www.skosmos.skos/mapping/m1', 'en');
    $concept = $concepts[0];
    $props = $concept->getMappingProperties();
    $propvals = $props['skos:exactMatch']->getValues();
    $this->assertInstanceOf('Vocabulary', $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getExVocab());
    $this->assertEquals('test', $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getExVocab()->getId());
  }
  
  /**
   * @covers ConceptMappingPropertyValue::getVocabName
   */
  public function testGetVocabNameWithExternalVocabulary() {
    $concepts = $this->vocab->getConceptInfo('http://www.skosmos.skos/mapping/m1', 'en');
    $concept = $concepts[0];
    $props = $concept->getMappingProperties();
    $propvals = $props['skos:exactMatch']->getValues();
    $this->assertEquals('Test ontology', $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getVocabName());
  }
  
  /**
   * @covers ConceptMappingPropertyValue::getUri
   */
  public function testGetUri() {
    $concepts = $this->vocab->getConceptInfo('http://www.skosmos.skos/mapping/m1', 'en');
    $concept = $concepts[0];
    $props = $concept->getMappingProperties();
    $propvals = $props['skos:exactMatch']->getValues();
    $this->assertEquals('http://www.skosmos.skos/test/ta115', $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getUri());
  }
  
  /**
   * @covers ConceptMappingPropertyValue::getVocab
   */
  public function testGetVocab() {
    $concepts = $this->vocab->getConceptInfo('http://www.skosmos.skos/mapping/m1', 'en');
    $concept = $concepts[0];
    $props = $concept->getMappingProperties();
    $propvals = $props['skos:exactMatch']->getValues();
    $this->assertEquals($this->vocab, $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getVocab());
  }
  
  /**
   * @covers ConceptMappingPropertyValue::getType
   */
  public function testGetType() {
    $concepts = $this->vocab->getConceptInfo('http://www.skosmos.skos/mapping/m1', 'en');
    $concept = $concepts[0];
    $props = $concept->getMappingProperties();
    $propvals = $props['skos:exactMatch']->getValues();
    $this->assertEquals('skos:exactMatch', $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getType());
  }
}
