<?php

class ConceptPropertyValueLiteralTest extends PHPUnit_Framework_TestCase
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
   * @covers ConceptPropertyValueLiteral::getLabel
   */
  public function testGetLabel() {
    $props = $this->concept->getProperties();
    $propvals = $props['skos:scopeNote']->getValues();
    $this->assertEquals('Carp are oily freshwater fish', $propvals['Carp are oily freshwater fish']->getLabel());
  }

  /**
   * @covers ConceptPropertyValueLiteral::__toString
   */
  public function testGetToString() {
    $props = $this->concept->getProperties();
    $propvals = $props['skos:scopeNote']->getValues();
    $this->assertEquals('Carp are oily freshwater fish', $propvals['Carp are oily freshwater fish']);
  }
}
