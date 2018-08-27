<?php

class ConceptPropertyTest extends PHPUnit\Framework\TestCase
{
  private $model;

  protected function setUp() {
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    bindtextdomain('skosmos', 'resource/translations');
    bind_textdomain_codeset('skosmos', 'UTF-8');
    textdomain('skosmos');

    $this->model = new Model(new GlobalConfig('/../tests/testconfig.ttl'));
  }

  /**
   * @covers ConceptProperty::__construct
   * @covers ConceptProperty::getLabel
   */
  public function testGetConstructAndLabel() {
    $prop = new ConceptProperty('skosmos:testLabel', 'Test label');
    $this->assertEquals('Test label', $prop->getLabel());
  }

  /**
   * @covers Concept::getProperties
   * @covers ConceptProperty::getValues
   * @covers ConceptProperty::getLabel
   * @covers ConceptProperty::getDescription
   */
  public function testGetDescriptionAndLabel() {
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
  public function testGetLabel() {
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
  public function testGetLabelReturnsNullWhenThereIsNoLabel() {
    $prop = new ConceptProperty('skosmos:type', null);
    $this->assertEquals(null, $prop->getLabel());
  }

  /**
   * @covers Concept::getProperties
   * @covers ConceptProperty::getType
   */
  public function testGetType() {
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
  public function testAddValue() {
    $vocab = $this->model->getVocabulary('test');
    $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta1', 'en');
    $concept = $concepts[0];
    $props = $concept->getProperties();
    $prevlabel = null;
    foreach($props['skos:narrower'] as $val) {
      $label = is_string($val->getLabel()) ? $val->getLabel() : $val->getLabel()->getValue();
      if ($prevlabel)
        $this->assertEquals(1, strnatcmp($prevlabel, $label));
      $prevlabel = $label;
    }
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
}
