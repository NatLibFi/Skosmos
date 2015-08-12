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
    $propval = reset($props['skos:exactMatch']->getValues());
    $this->assertEquals('Eel', $propval->getLabel()->getValue());
  }
}
