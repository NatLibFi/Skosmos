<?php

class ConceptMappingPropertyValueTest extends PHPUnit_Framework_TestCase
{
  private $model; 
  private $concept;

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
  }

  /**
   * @covers ConceptMappingPropertyValue::getLabel
   */
  public function testGetLabelFromExternalVocabulary() {
    $vocab = $this->model->getVocabulary('mapping');
    $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/mapping/m1', 'en');
    $concept = $concepts[0];
    $props = $concept->getMappingProperties();
    $propvals = $props['skos:exactMatch']->getValues();
    $this->assertEquals('Eel', $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getLabel()->getValue());
  }

  /**
   * @covers ConceptMappingPropertyValue::getExVocab
   */
  public function testGetExVocab() {
    $vocab = $this->model->getVocabulary('mapping');
    $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/mapping/m1', 'en');
    $concept = $concepts[0];
    $props = $concept->getMappingProperties();
    $propvals = $props['skos:exactMatch']->getValues();
    $this->assertEquals('test', $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getExVocab());
  }
  
  /**
   * @covers ConceptMappingPropertyValue::getVocabName
   */
  public function testGetVocabNameWithExternalVocabulary() {
    $vocab = $this->model->getVocabulary('mapping');
    $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/mapping/m1', 'en');
    $concept = $concepts[0];
    $props = $concept->getMappingProperties();
    $propvals = $props['skos:exactMatch']->getValues();
    $this->assertEquals('Test ontology', $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getVocabName());
  }
  
  /**
   * @covers ConceptMappingPropertyValue::getUri
   */
  public function testGetUri() {
    $vocab = $this->model->getVocabulary('mapping');
    $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/mapping/m1', 'en');
    $concept = $concepts[0];
    $props = $concept->getMappingProperties();
    $propvals = $props['skos:exactMatch']->getValues();
    $this->assertEquals('http://www.skosmos.skos/test/ta115', $propvals['Eelhttp://www.skosmos.skos/test/ta115']->getUri());
  }
}
