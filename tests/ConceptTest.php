<?php

class ConceptTest extends PHPUnit_Framework_TestCase
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
  public function testGetVocab()
  {
    $voc = $this->concept->getVocab();
    $this->assertInstanceOf('Vocabulary', $voc);
  }
  
  /**
   * @covers Concept::getVocabTitle
   */
  public function testGetVocabTitle()
  {
    $title = $this->concept->getVocabTitle();
    $this->assertEquals('Test ontology', $title);
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
   * @covers Concept::getForeignLabels
   */
  public function testGetForeignLabels()
  {
    $labels = $this->concept->getForeignLabels();

    $this->assertEquals('Karppi', $labels['Finnish'][0]->getLabel());
  }
  
  /**
   * @covers Concept::getAllLabels
   */
  public function testGetAllLabels()
  {
    $search_results = $this->model->searchConceptsAndInfo('Eel', 'test', 'en', 'en'); 
    $concept = $search_results['results'][0];
    $labels = $concept->getAllLabels('skos:definition');
    $this->assertEquals('Iljettävä limanuljaska', $labels['Finnish'][0]->getLabel());
    $this->assertEquals('any fish belonging to the order Anguilliformes', $labels['English'][0]->getLabel());
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
   * @covers DataObject::arbitrarySort
   * @covers DataObject::mycompare
   * @covers Concept::getProperties
   * @covers ConceptProperty::getValues
   * @covers ConceptPropertyValue::getLabel
   */
  public function testGetPropertiesCorrectOrderOfProperties()
  {
    $props = $this->concept->getProperties();
    $expected = array (0 => 'rdf:type', 1=> 'skos:broader',2 => 'skos:narrower',3 => 'skos:altLabel',4 => 'skos:scopeNote',5 => 'http://www.skosmos.skos/testprop');
    $this->assertEquals($expected, array_keys($props));
 
  }
  
  /**
   * @covers Concept::getProperties
   * @covers ConceptProperty::getValues
   * @covers ConceptPropertyValue::getLabel
   */
  public function testGetPropertiesAlphabeticalSortingOfPropertyValues()
  {
    $search_results = $this->model->searchConceptsAndInfo('fish', 'test', 'en', 'en'); 
    $concept = $search_results['results'][0];
    $props = $concept->getProperties();
    $expected = array (0 => '3D Basshttp://www.skosmos.skos/test/ta117',1 => 'Basshttp://www.skosmos.skos/test/ta116',2 => 'Burihttp://www.skosmos.skos/test/ta114',3 => 'Carphttp://www.skosmos.skos/test/ta112',4 => 'Eelhttp://www.skosmos.skos/test/ta115',5 => 'Haukihttp://www.skosmos.skos/test/ta119',6 => 'Tunahttp://www.skosmos.skos/test/ta111',7 => 'test:ta113http://www.skosmos.skos/test/ta113',8 => 'test:ta120http://www.skosmos.skos/test/ta120');
    $this->assertEquals($expected, array_keys($props['skos:narrower']->getValues()));
 
  }
  
  /**
   * @covers Concept::getMappingProperties
   * @covers ConceptProperty::getValues
   */
  public function testGetMappingPropertiesWithIdenticalLabels() {
    $vocab = $this->model->getVocabulary('duplicates');
    $concepts = $vocab->getConceptInfo("http://www.skosmos.skos/dup/d3", "en");
    $concept = $concepts[0];
    $props = $concept->getMappingProperties();
    $values = $props['skos:closeMatch']->getValues();
    $this->assertCount(2, $values);
  }
  
  /**
   * @covers Concept::removeDuplicatePropertyValues
   * @covers Concept::getProperties
   */
  public function testRemoveDuplicatePropertyValues() {
    $vocab = $this->model->getVocabulary('duplicates');
    $concepts = $vocab->getConceptInfo("http://www.skosmos.skos/dup/d4", "en");
    $concept = $concepts[0];
    $props = $concept->getProperties();
    $this->assertCount(1, $props);
  }
  
  /**
   * @covers Concept::removeDuplicatePropertyValues
   * @covers Concept::getProperties
   */
  public function testRemoveDuplicatePropertyValuesOtherThanSubpropertyof() {
    $vocab = $this->model->getVocabulary('duplicates');
    $concepts = $vocab->getConceptInfo("http://www.skosmos.skos/dup/d5", "en");
    $concept = $concepts[0];
    $props = $concept->getProperties();
    $this->assertCount(2, $props);
  }

  /**
   * @covers Concept::getProperties
   * @covers ConceptProperty::getValues
   * @covers ConceptPropertyValueLiteral::getLabel
   */
  public function testGetTimestamp() {
    $vocab = $this->model->getVocabulary('test');
    $concepts = $vocab->getConceptInfo("http://www.skosmos.skos/test/ta123", "en");
    $concept = $concepts[0];
    $date = $concept->getDate();
    $this->assertContains('10/1/14', $date);
  }

  /**
   * @covers Concept::getDate
   */
  public function testGetDateWithCreatedAndModified() {
    $vocab = $this->model->getVocabulary('dates');
    $concepts = $vocab->getConceptInfo("http://www.skosmos.skos/date/d1", "en");
    $concept = $concepts[0];
    $date = $concept->getDate();
    $this->assertContains('1/3/00', $date);
    $this->assertContains('6/6/12', $date);
  }

  /**
   * @covers Concept::getDate
   * @covers ConceptProperty::getValues
   * @covers ConceptPropertyValueLiteral::getLabel
   * @expectedException \Exception
   * @expectedExceptionMessage DateTime::__construct(): Failed to parse time string (1986-21-00) at position 6 (1): Unexpected character
   */

  public function testGetTimestampInvalidWarning() {
    $vocab = $this->model->getVocabulary('test');
    $concepts = $vocab->getConceptInfo("http://www.skosmos.skos/test/ta114", "en");
    $concept = $concepts[0];
    $props = $concept->getDate(); # this should throw a E_USER_WARNING exception
  }

  /**
   * @covers Concept::getProperties
   * @covers ConceptProperty::getValues
   * @covers ConceptPropertyValueLiteral::getLabel
   */

  public function testGetTimestampInvalidResult() {
    $vocab = $this->model->getVocabulary('test');
    $concepts = $vocab->getConceptInfo("http://www.skosmos.skos/test/ta114", "en");
    $concept = $concepts[0];
    # we use @ to suppress the exceptions in order to be able to check the result
    $date = @$concept->getDate();
    $this->assertContains('1986-21-00', $date);
  }

  /**
   * @covers Concept::getProperties
   */
  public function testGetPropertiesTypes()
  {
    $props = $this->concept->getProperties();
    $propvals = $props['rdf:type']->getValues();
    $this->assertCount(1, $propvals); // should only have type meta:TestClass, not skos:Concept (see #200)
    $this->assertEquals('Test class', $propvals['Test classhttp://www.skosmos.skos/test-meta/TestClass']->getLabel());
    $this->assertEquals('http://www.skosmos.skos/test-meta/TestClass', $propvals['Test classhttp://www.skosmos.skos/test-meta/TestClass']->getUri());
  }
  
  /**
   * @covers Concept::getNotation
   */
  public function testGetNotationWhenNull()
  {
    $vocab = $this->model->getVocabulary('test');
    $concepts = $vocab->getConceptInfo("http://www.skosmos.skos/test/ta114", "en");
    $concept = $concepts[0];
    $this->assertEquals(null, $concept->getNotation());
  }
  
  /**
   * @covers Concept::getNotation
   */
  public function testGetNotation()
  {
    $this->assertEquals('665', $this->concept->getNotation());
  }
  
  /**
   * @covers Concept::getLabel
   */
  public function testGetLabelCurrentLanguage()
  {
    $this->assertEquals('Carp', $this->concept->getLabel()->getValue());
  }
  
  /**
   * @covers Concept::getLabel
   */
  public function testGetLabelWhenNull()
  {
    $model = new Model();
    $vocab = $model->getVocabulary('test');
    $concept = $vocab->getConceptInfo("http://www.skosmos.skos/test/ta120", "en");
    $this->assertEquals(null, $concept[0]->getLabel());
  }
  
  /**
   * @covers Concept::getLabel
   * @covers Concept::setContentLang
   * @covers Concept::getContentLang
   */
  public function testGetLabelResortingToVocabDefault()
  {
    $this->concept->setContentLang('pl');
    $this->assertEquals('pl', $this->concept->getContentLang());
    $this->assertEquals('Carp', $this->concept->getLabel()->getValue());
  }
  
  /**
   * @covers Concept::getGroupProperties
   */
  public function testGetGroupProperties()
  {
    $model = new Model();
    $vocab = $model->getVocabulary('groups');
    $concept = $vocab->getConceptInfo("http://www.skosmos.skos/groups/ta111", "en");
    $groups = $concept[0]->getGroupProperties();
    $this->assertArrayHasKey("Saltwater fish", $groups);
    $this->assertArrayHasKey("Submarine-like fish", $groups);
  }

  /**
   * @covers Concept::getProperties
   * @covers ConceptProperty::getValues
   * @covers ConceptPropertyValue::getLabel
   * @covers ConceptPropertyValue::getSubMembers
   */
  public function testGetPropertiesWithNarrowersPartOfACollection()
  {
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
   * @covers ConceptProperty::getValues
   */
  
  public function testGetPropertiesDefinitionResource() {
    $vocab = $this->model->getVocabulary('test');
    $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta122', 'en');
    $concept = $concepts[0];
    $props = $concept->getProperties();
    $propvals = $props['skos:definition']->getValues();
    $this->assertEquals('The black sea bass (Centropristis striata) is an exclusively marine fish.', $propvals['The black sea bass (Centropristis striata) is an exclusively marine fish.http://www.skosmos.skos/test/black_sea_bass_def']->getLabel());
  }
}
