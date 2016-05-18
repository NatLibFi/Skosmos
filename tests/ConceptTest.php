<?php

class ConceptTest extends PHPUnit_Framework_TestCase
{
  private $model; 
  private $concept;

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
   * @covers Concept::__construct
   */
  public function testConstructor()
  {
    $mockres = $this->getMockBuilder('EasyRdf_Resource')->disableOriginalConstructor()->getMock();
    $concept = new Concept($this->model, $this->vocab, $mockres, 'http://skosmos.skos/test', 'en');
    $this->assertInstanceOf('Concept', $concept);
    $this->assertEquals('Test ontology', $concept->getVocabTitle());
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
   * @covers Concept::literalLanguageToString
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
    $results = $this->vocab->getConceptInfo('http://www.skosmos.skos/test/ta115', 'en');
    $concept = reset($results);
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
    $results = $this->vocab->getConceptInfo('http://www.skosmos.skos/test/ta1', 'en'); 
    $concept = reset($results);
    $props = $concept->getProperties();
    $prevlabel;
    foreach($props['skos:narrower'] as $val) {
      $label = is_string($val->getLabel()) ? $val->getLabel() : $val->getLabel()-getValue();
      if ($prevlabel)
        $this->assertEquals(1, strnatcmp($prevlabel, $label));
      $prevlabel = $label;
    }
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
   * @expectedException PHPUnit_Framework_Error
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
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
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
   * @covers Concept::getArrayProperties
   * @covers Concept::getGroupProperties
   * @covers Concept::getReverseResources
   */
  public function testGetGroupProperties()
  {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $vocab = $model->getVocabulary('groups');
    $concept = $vocab->getConceptInfo("http://www.skosmos.skos/groups/ta111", "en");
    $arrays = $concept[0]->getArrayProperties();
    $this->assertArrayHasKey("Saltwater fish", $arrays);
    $this->assertArrayHasKey("Submarine-like fish", $arrays);
    $groups = $concept[0]->getGroupProperties();
    $this->assertEmpty($groups);
  }

  /**
   * @covers Concept::getProperties
   * @covers Concept::getCollectionMembers
   * @covers ConceptProperty::getValues
   * @covers ConceptPropertyValue::getLabel
   * @covers ConceptPropertyValue::getSubMembers
   */
  public function testGetPropertiesWithNarrowersPartOfACollection()
  {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
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
