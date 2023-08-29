<?php

class ConceptPropertyTest extends PHPUnit\Framework\TestCase
{
    private $model;

    protected function setUp(): void
    {
        $this->model = new Model(new GlobalConfig('/../../tests/testconfig.ttl'));
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
        $this->markTestSkipped('disabled since the functionality needs to be reimplemented after the new translation component is in use');
        $vocab = $this->model->getVocabulary('test');
        $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta122', 'en');
        $concept = $concepts[0];
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
        $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/date/d1', 'en');
        $concept = $concepts[0];
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
        $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta112', 'en');
        $concept = $concepts[0];
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
        $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta0112', 'en');
        $concept = $concepts[0];
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
        $this->markTestSkipped('disabled since the functionality needs to be reimplemented after the new translation component is in use');
        $vocab = $this->model->getVocabulary('test');
        $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta122', 'en');
        $concept = $concepts[0];
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
        $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta1', 'en');
        $concept = $concepts[0];
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
        $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta01', 'en');
        $concept = $concepts[0];
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
        $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta01', 'en');
        $concept = $concepts[0];
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
        $results = $vocab->getConceptInfo('http://www.skosmos.skos/sub/d1', 'en');
        $concept = reset($results);
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

}
