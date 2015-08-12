<?php

class ConceptPropertyValueTest extends PHPUnit_Framework_TestCase
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
}
