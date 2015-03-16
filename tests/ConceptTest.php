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
  public function testGetPropertiesLiteralValue()
  {
    $props = $this->concept->getProperties();
    $propvals = $props['http://www.skosmos.skos/testprop']->getValues();

    $this->assertEquals('Skosmos test property', $props['http://www.skosmos.skos/testprop']->getLabel()->getValue());
    $this->assertEquals('Test property value', $propvals['Test property value']->getLabel());
  }

  /**
   * @covers Concept::getProperties
   * @covers ConceptProperty::getValues
   * @covers ConceptPropertyValue::getLabel
   */
  public function testGetPropertiesCorrectNumberOfProperties()
  {
    $props = $this->concept->getProperties();
 
    $this->assertEquals(6, sizeof($props));
  }

  /**
   * @covers Concept::getProperties
   * @covers ConceptProperty::getValues
   * @covers ConceptPropertyValue::getLabel
   */
  public function testGetPropertiesCorrectOrderOfProperties()
  {
    $props = $this->concept->getProperties();
    $expected = array (0 => 'skos:broader',1 => 'skos:narrower',2 => 'skos:altLabel',3 => 'skos:scopeNote',4 => 'skos:prefLabel',5 => 'http://www.skosmos.skos/testprop');
    $this->assertEquals(array_keys($props), $expected);
 
  }

  /**
   * @covers Concept::getProperties
   * @covers ConceptProperty::getValues
   * @covers ConceptPropertyValue::getLabel
   * @covers ConceptPropertyValue::getSubMembers
   */
  public function testGetPropertiesWithNarrowersPartOfACollection()
  {
    bindtextdomain('skosmos', 'resource/translations');
    bind_textdomain_codeset('skosmos', 'UTF-8');

    // Choose domain for translations
    textdomain('skosmos');
    $model = new Model();
    $vocab = $model->getVocabulary('groups');
    $concept = $vocab->getConceptInfo("http://www.skosmos.skos/groups/ta1", "en");
    $props = $concept[0]->getProperties();
    $narrowers = $props['skos:narrower']->getValues();
    $this->assertCount(3, $narrowers);
    foreach ($narrowers as $coll) {
      $subs = $coll->getSubMembers();
      if ($coll->getLabel() === "Freshwater fish") {
        $this->assertArrayHasKey("Carp", $subs);
      } elseif ($coll->getLabel() === "Saltwater Fish") {
        $this->assertArrayHasKey("Flatfish", $subs);
        $this->assertArrayHasKey("Tuna", $subs);
      } elseif ($coll->getLabel() === "Submarine-like fish") {
        $this->assertArrayHasKey("Tuna", $subs);
      }
    }
  }
  
  /**
   * @covers Concept::getProperties
   */
  
  public function testGetPropertiesDefinitionLiteral() {
    $vocab = $this->model->getVocabulary('test');
    $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta115', 'en');
    $concept = $concepts[0];
    $props = $concept->getProperties();
    $propvals = $props['skos:definition']->getValues();
    $this->assertEquals('any fish belonging to the order Anguilliformes', $propvals['any fish belonging to the order Anguilliformes']->getLabel());
  }

  /**
   * @covers Concept::getProperties
   */
  
  public function testGetPropertiesDefinitionResource() {
    $vocab = $this->model->getVocabulary('test');
    $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta122', 'en');
    $concept = $concepts[0];
    $props = $concept->getProperties();
    $propvals = $props['skos:definition']->getValues();
    $this->assertEquals('The black sea bass (Centropristis striata) is an exclusively marine fish.', $propvals['The black sea bass (Centropristis striata) is an exclusively marine fish.']->getLabel());
  }


}
