<?php

class ConceptPropertyTest extends PHPUnit\Framework\TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new Model('/../../tests/testconfig.ttl');
    }

    /**
     * @covers ConceptProperty::__construct
     * @covers ConceptProperty::getLabel
     */
    public function testGetConstructAndLabel()
    {
        $prop = new ConceptProperty($this->model, 'skosmos:testLabel', 'Test label');
        $this->assertEquals('Test label', $prop->getLabel());
    }

    /**
     * @covers Concept::getProperties
     * @covers ConceptProperty::getValues
     * @covers ConceptProperty::getLabel
     * @covers ConceptProperty::getDescription
     */
    public function testGetDescriptionAndLabel()
    {
        $vocab = $this->model->getVocabulary('test');
        $concept = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta122', 'en');
        $props = $concept->getProperties();
        $propvals = $props['skos:definition']->getValues();
        $this->assertEquals('Definition', $props['skos:definition']->getLabel());
        $this->assertEquals('A complete explanation of the intended meaning of a concept', $props['skos:definition']->getDescription());
    }

    /**
     * @covers ConceptProperty::getLabel
     */
    public function testGetLabel()
    {
        $vocab = $this->model->getVocabulary('dates');
        $concept = $vocab->getConceptInfo('http://www.skosmos.skos/date/d1', 'en');
        $props = $concept->getProperties();
        $proplabel = $props['http://www.skosmos.skos/date/ownDate']->getLabel();
        $this->assertEquals('This is also a dateTime', $proplabel->getValue());
    }

    /**
     * @covers ConceptProperty::getLabel
     */
    public function testGetLabelReturnsNullWhenThereIsNoLabel()
    {
        $prop = new ConceptProperty($this->model, 'skosmos:type', null);
        $this->assertEquals(null, $prop->getLabel());
    }

    /**
     * @covers ConceptProperty::getLabel
     * @covers ConceptProperty::getDescription
     */
    public function testGetDescriptionAndLabelForCustomProperty()
    {
        $vocab = $this->model->getVocabulary('test');
        $concept = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta112', 'en');
        $props = $concept->getProperties();
        $prop = $props["http://www.skosmos.skos/testprop"];
        $this->assertEquals('Skosmos test property', $prop->getLabel());
        $this->assertEquals('description for Skosmos test property', $prop->getDescription());
    }

    /**
     * @covers ConceptProperty::getLabel
     * @covers ConceptProperty::getDescription
     */
    public function testGetDescriptionAndLabelForCustomPropertyMissingDesc()
    {
        $vocab = $this->model->getVocabulary('test-notation-sort');
        $concept = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta0112', 'en');
        $props = $concept->getProperties();
        $prop = $props["http://www.skosmos.skos/testprop"];
        $this->assertEquals('Skosmos test property', $prop->getLabel());
        $this->assertEquals(null, $prop->getDescription());
    }

    /**
     * @covers Concept::getProperties
     * @covers ConceptProperty::getType
     */
    public function testGetType()
    {
        $vocab = $this->model->getVocabulary('test');
        $concept = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta122', 'en');
        $props = $concept->getProperties();
        $this->assertEquals('skos:definition', $props['skos:definition']->getType());
    }

    /**
     * @covers ConceptProperty::addValue
     * @covers ConceptProperty::sortValues
     */
    public function testAddValue()
    {
        $vocab = $this->model->getVocabulary('test');
        $concept = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta1', 'en');
        $props = $concept->getProperties();
        $prevlabel = null;
        foreach($props['skos:narrower']->getValues() as $val) {
            $label = $val->getLabel();
            if ($prevlabel) {
                $this->assertEquals(-1, strnatcasecmp($prevlabel, $label));
            }
            $prevlabel = $label;
        }
    }

    /**
     * @covers ConceptProperty::addValue
     * @covers ConceptProperty::sortValues
     */
    public function testSortNotatedValuesLexical()
    {
        # the vocabulary is configured to use lexical sorting
        $vocab = $this->model->getVocabulary('test-notation-sort');
        $concept = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta01', 'en');
        $props = $concept->getProperties();
        $expected = array(
          "test:ta0111", # 33.01
          "test:ta0116", # 33.02
          "test:ta0112", # 33.1
          "test:ta0114", # 33.10
          "test:ta0115", # 33.2
          "test:ta0117", # 33.9
          "test:ta0119", # 33.90
          "test:ta0120", # K2
          "test:ta0113"  # concept not defined, no notation code
        );
        $ret = array();

        foreach($props['skos:narrower']->getValues() as $val) {
            $ret[] = EasyRdf\RdfNamespace::shorten($val->getUri());
        }
        $this->assertEquals($expected, $ret);
    }

    /**
     * @covers ConceptProperty::addValue
     * @covers ConceptProperty::sortValues
     */
    public function testSortNotatedValuesNatural()
    {
        # the vocabulary is configured to use natural sorting
        $vocab = $this->model->getVocabulary('testNotation');
        $concept = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta01', 'en');
        $props = $concept->getProperties();
        $expected = array(
          "test:ta0111", # 33.01
          "test:ta0116", # 33.02
          "test:ta0112", # 33.1
          "test:ta0115", # 33.2
          "test:ta0117", # 33.9
          "test:ta0114", # 33.10
          "test:ta0119", # 33.90
          "test:ta0120", # K2
          "test:ta0113"  # concept not defined, no notation code
        );
        $ret = array();

        foreach($props['skos:narrower']->getValues() as $val) {
            $ret[] = EasyRdf\RdfNamespace::shorten($val->getUri());
        }
        $this->assertEquals($expected, $ret);
    }

    /**
     * @covers ConceptProperty::getSubPropertyOf
     */
    public function testGetPropertiesSubClassOfHiddenLabel()
    {
        $vocab = $this->model->getVocabulary('subclass');
        $concept = $vocab->getConceptInfo('http://www.skosmos.skos/sub/d1', 'en');
        $props = $concept->getProperties();
        $this->assertEquals('skos:hiddenLabel', $props['subclass:prop1']->getSubPropertyOf());
    }

    /**
     * @covers ConceptProperty::getID
     */
    public function testGetIDShortenedURI()
    {
        $prop = new ConceptProperty($this->model, 'skosmos:testLabel', 'Test label');
        $this->assertEquals('skosmos_testLabel', $prop->getID());
    }

    /**
     * @covers ConceptProperty::getID
     */
    public function testGetIDFullURI()
    {
        $prop = new ConceptProperty($this->model, 'http://rdaregistry.info/Elements/a/P50008', 'has hierarchical superior');
        $this->assertEquals('http___rdaregistry_info_Elements_a_P50008', $prop->getID());
    }

    /**
     * @covers ConceptPropertyValue::isRdfList
     * @covers ConceptPropertyValue::getRdfListItems
     */
    public function testRdfListOrdered()
    {
        $vocab = $this->model->getVocabulary('test-rdf-list');
        $concept = $vocab->getConceptInfo('http://www.skosmos.skos/test-rdf-list/sdlc-ordered', 'en');
        $props = $concept->getProperties();

        $propKey = 'http://www.skosmos.skos/hasRelatedConcept';
        $this->assertArrayHasKey($propKey, $props);
        $values = $props[$propKey]->getValues();

        // Should have exactly one value (the RDF list)
        $this->assertCount(1, $values);

        // getValues() returns associative array, get first value
        $listValue = reset($values);
        $this->assertNotNull($listValue, 'List value should not be null');
        $this->assertTrue($listValue->isRdfList());

        $listItems = $listValue->getRdfListItems();
        $this->assertCount(6, $listItems);

        // Check the order is preserved (SDLC phases)
        $expectedOrder = ['Requirements Gathering', 'System Design', 'Implementation', 
                         'Testing', 'Deployment', 'Maintenance'];
        foreach ($listItems as $index => $item) {
            $this->assertEquals($expectedOrder[$index], $item->getLabel()->getValue());
        }
    }

    /**
     * @covers ConceptPropertyValue::isRdfList
     */
    public function testRdfListUnordered()
    {
        $vocab = $this->model->getVocabulary('test-rdf-list');
        $concept = $vocab->getConceptInfo('http://www.skosmos.skos/test-rdf-list/languages-unordered', 'en');
        $props = $concept->getProperties();
        
        $propKey = 'http://www.skosmos.skos/hasRelatedConcept';
        $this->assertArrayHasKey($propKey, $props);
        $values = $props[$propKey]->getValues();
        
        // Should have 12 individual values (not a list)
        $this->assertCount(12, $values);
        
        // None of the values should be RDF lists
        foreach ($values as $value) {
            $this->assertFalse($value->isRdfList());
        }
    }

    /**
     * @covers ConceptPropertyValue::isRdfList
     * @covers ConceptPropertyValue::getRdfListItems
     */
    public function testRdfListMixed()
    {
        $vocab = $this->model->getVocabulary('test-rdf-list');
        $concept = $vocab->getConceptInfo('http://www.skosmos.skos/test-rdf-list/mixed', 'en');
        $props = $concept->getProperties();
        
        $propKey = 'http://www.skosmos.skos/hasRelatedConcept';
        $this->assertArrayHasKey($propKey, $props);
        $values = $props[$propKey]->getValues();
        
        // Should have 4 values: 3 individual items + 1 RDF list (containing 6 items)
        $this->assertCount(4, $values);
        
        $listCount = 0;
        $regularCount = 0;
        
        foreach ($values as $value) {
            if ($value->isRdfList()) {
                $listCount++;
                $listItems = $value->getRdfListItems();
                $this->assertCount(6, $listItems);
                // Verify the list contains SDLC phases
                $this->assertEquals('Requirements Gathering', $listItems[0]->getLabel()->getValue());
                $this->assertEquals('System Design', $listItems[1]->getLabel()->getValue());
                $this->assertEquals('Maintenance', $listItems[5]->getLabel()->getValue());
            } else {
                $regularCount++;
            }
        }
        
        $this->assertEquals(1, $listCount);
        $this->assertEquals(3, $regularCount);
    }

    /**
     * @covers ConceptPropertyValue::isRdfListTruncated
     */
    public function testRdfListNotTruncated()
    {
        $vocab = $this->model->getVocabulary('test-rdf-list');
        $concept = $vocab->getConceptInfo('http://www.skosmos.skos/test-rdf-list/sdlc-ordered', 'en');
        $props = $concept->getProperties();
        
        $propKey = 'http://www.skosmos.skos/hasRelatedConcept';
        $values = $props[$propKey]->getValues();
        $listValue = reset($values);
        
        $this->assertNotNull($listValue, 'List value should not be null');
        $this->assertTrue($listValue->isRdfList());
        $this->assertFalse($listValue->isRdfListTruncated());
    }

    /**
     * @covers ConceptPropertyValue::isRdfListTruncated
     * @covers ConceptPropertyValue::getRdfListItems
     */
    public function testRdfListTruncated()
    {
        $vocab = $this->model->getVocabulary('test-rdf-list');
        $concept = $vocab->getConceptInfo('http://www.skosmos.skos/test-rdf-list/languages-ordered', 'en');
        $props = $concept->getProperties();
        
        $propKey = 'http://www.skosmos.skos/hasRelatedConcept';
        $this->assertArrayHasKey($propKey, $props);
        $values = $props[$propKey]->getValues();
        
        $this->assertCount(1, $values);
        $listValue = reset($values);
        
        $this->assertNotNull($listValue, 'List value should not be null');
        $this->assertTrue($listValue->isRdfList());
        
        // List should be truncated at 10 items (config limit)
        $listItems = $listValue->getRdfListItems();
        $this->assertCount(10, $listItems);
        
        // Should be marked as truncated since original has 12 items
        $this->assertTrue($listValue->isRdfListTruncated());
        
        // Verify first and last items of truncated list
        // Order: Python, Java, JavaScript, C#, Ruby, PHP, Go, Rust, TypeScript, Swift (truncated at 10)
        $this->assertEquals('Python', $listItems[0]->getLabel()->getValue());
        $this->assertEquals('Swift', $listItems[9]->getLabel()->getValue());
    }

}
