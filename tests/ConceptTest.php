<?php

class ConceptTest extends PHPUnit_Framework_TestCase
{
  private $model; 
  private $concept;

  protected function setUp() {
    require_once 'testconfig.inc';
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    $this->model = new Model();
    $search_results = $this->model->searchConceptsAndInfo('carp', 'test', 'en', 'en'); 
    $this->concept = $search_results['results'][0];
  }
  
  /**
   * @covers Concept::getUri
   */
  public function testGetUri()
  {
    $uri = $this->concept->getURI();
    $this->assertEquals('http://www.skosmos.skos/test/ta112', $uri);
  }
  
  /**
   * @covers Concept::getDeprecated
   */
  public function testGetConceptNotDeprecated()
  {
    $deprecated = $this->concept->getDeprecated();
    $this->assertEquals(false, $deprecated);
  }
  
  /**
   * @covers Concept::getLabel
   */
  public function testGetLabelInUILang()
  {
    $label = $this->concept->getLabel();
    $this->assertEquals('Carp', $label);
  }
  
  /**
   * @covers Concept::getLabel
   */
  public function testGetLabelUILangNotAvailable()
  {
    $search_results = $this->model->searchConceptsAndInfo('hauki', 'test', 'en', null); 
    $this->concept = $search_results['results'][0];
    $label = $this->concept->getLabel();
    $this->assertEquals('Hauki (fi)', $label);
  }
  
  /**
   * @covers Concept::getVocab
   */
  public function testGetLabel()
  {
    $id = $this->concept->getVocab();
    $this->assertEquals('test', $id);
  }
  
  /**
   * @covers Concept::getShortName
   */
  public function testGetShortName()
  {
    $short = $this->concept->getShortName();
    $this->assertEquals('Test short', $short);
  }

  /**
   * @covers Concept::getFoundBy
   */
  public function testGetFoundByWhenNotSet()
  {
    $fb = $this->concept->getFoundBy();
    $this->assertEquals(null, $fb);
  }
  
  /**
   * @covers Concept::setFoundBy
   * @covers Concept::getFoundByType
   */
  public function testSetFoundBy()
  {
    $fb = $this->concept->getFoundBy();
    $this->assertEquals(null, $fb);
    $this->concept->setFoundBy('testing matched label', 'alt');
    $fb = $this->concept->getFoundBy();
    $fbtype = $this->concept->getFoundByType();
    $this->assertEquals('testing matched label', $fb);
    $this->assertEquals('alt', $fbtype);
  }
  
  /**
   * @covers Concept::getProperties
   * @covers ConceptProperty::getValues
   * @covers ConceptPropertyValue::getLabel
   */
  public function testGetProperties()
  {
    $props = $this->concept->getProperties();
    $propvals = $props['http://www.skosmos.skos/testprop']->getValues();
    $this->assertEquals('Skosmos test property', $props['http://www.skosmos.skos/testprop']->getLabel()->getValue());
    $this->assertEquals('Test property value', $propvals[0]->getLabel());
  }

}
