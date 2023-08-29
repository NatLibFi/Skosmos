<?php

class ConceptPropertyValueLiteralTest extends PHPUnit\Framework\TestCase
{
    private $model;
    private $concept;
    private $vocab;

    protected function setUp(): void
    {
        $this->model = new Model(new GlobalConfig('/../../tests/testconfig.ttl'));
        $this->vocab = $this->model->getVocabulary('test');
        $results = $this->vocab->getConceptInfo('http://www.skosmos.skos/test/ta112', 'en');
        $this->concept = reset($results);
    }

    /**
     * @covers ConceptPropertyValueLiteral::__construct
     */
    public function testConstructor()
    {
        $litmock = $this->getMockBuilder('EasyRdf\Literal')->disableOriginalConstructor()->getMock();
        $resmock = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $prop = new ConceptPropertyValueLiteral($this->model, $this->vocab, $resmock, $litmock, 'skosmos:someProperty');
        $this->assertEquals(null, $prop->__toString());
    }

    /**
     * @covers ConceptPropertyValueLiteral::getLabel
     */
    public function testGetLabel()
    {
        $props = $this->concept->getProperties();
        $propvals = $props['skos:scopeNote']->getValues();
        $this->assertEquals('Carp are oily freshwater fish', $propvals['Carp are oily freshwater fish']->getLabel());
    }

    /**
     * @covers ConceptPropertyValueLiteral::getLabel
     */
    public function testGetLabelThatIsADate()
    {
        $vocab = $this->model->getVocabulary('dates');
        $concepts = $vocab->getConceptInfo("http://www.skosmos.skos/date/d1", "en");
        $props = $concepts[0]->getProperties();
        $propvals = $props['http://www.skosmos.skos/date/ownDate']->getValues();
        $this->assertStringContainsString('8/8/15', $propvals['8/8/15']->getLabel());
    }

    /**
     * @covers ConceptPropertyValueLiteral::getLabel
     */
    public function testGetLabelThatIsABrokenDate()
    {
        $this->expectWarning();
        $vocab = $this->model->getVocabulary('dates');
        $concepts = $vocab->getConceptInfo("http://www.skosmos.skos/date/d2", "en");
        $props = $concepts[0]->getProperties();
        $propvals = $props['http://www.skosmos.skos/date/ownDate']->getValues();
    }

    /**
    * @covers ConceptPropertyValueLiteral::getDatatype
    */
    public function testGetLabelForDatatype()
    {
        $this->markTestSkipped('disabled since the functionality needs to be reimplemented after the new translation component is in use');
        $vocab = $this->model->getVocabulary('test');
        $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta112', 'en');
        $props = $concepts[0]->getProperties();
        $propvals = $props['skos:notation']->getValues();
        $this->assertEquals('NameOfTheDatatype', $propvals['665']->getDatatype());
    }

    /**
    * @covers ConceptPropertyValueLiteral::getDatatype
    */
    public function testGetNotationDatatypeWithoutLabel()
    {
        $this->markTestSkipped('disabled since the functionality needs to be reimplemented after the new translation component is in use');
        $vocab = $this->model->getVocabulary('test');
        $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta128', 'en');
        $props = $concepts[0]->getProperties();
        $propvals = $props['skos:notation']->getValues();
        $this->assertNull($propvals['testnotation']->getDatatype());
    }

    /**
     * @covers ConceptPropertyValueLiteral::getDatatype
     */
    public function testGetLabelForDatatypeIfNull()
    {
        $this->markTestSkipped('disabled since the functionality needs to be reimplemented after the new translation component is in use');
        $vocab = $this->model->getVocabulary('test');
        $concepts = $vocab->getConceptInfo('http://www.skosmos.skos/test/ta126', 'en');
        $props = $concepts[0]->getProperties();
        $propvals = $props['skos:notation']->getValues();
        $this->assertNull($propvals['12.34']->getDatatype());
    }

    /**
   * @covers ConceptPropertyValueLiteral::getLang
   */
    public function testGetLang()
    {
        $props = $this->concept->getProperties();
        $propvals = $props['skos:scopeNote']->getValues();
        $this->assertEquals('en', $propvals['Carp are oily freshwater fish']->getLang());
    }

    /**
     * @covers ConceptPropertyValueLiteral::getType
     */
    public function testGetType()
    {
        $props = $this->concept->getProperties();
        $propvals = $props['skos:scopeNote']->getValues();
        $this->assertEquals('skos:scopeNote', $propvals['Carp are oily freshwater fish']->getType());
    }

    /**
     * @covers ConceptPropertyValueLiteral::getUri
     */
    public function testGetUri()
    {
        $props = $this->concept->getProperties();
        $propvals = $props['skos:scopeNote']->getValues();
        $this->assertEquals(null, $propvals['Carp are oily freshwater fish']->getUri());
    }

    /**
     * @covers ConceptPropertyValueLiteral::__toString
     */
    public function testToString()
    {
        $props = $this->concept->getProperties();
        $propvals = $props['skos:scopeNote']->getValues();
        $this->assertEquals('Carp are oily freshwater fish', $propvals['Carp are oily freshwater fish']);
    }

    /**
     * @covers ConceptPropertyValueLiteral::__toString
     */
    public function testToStringEmpty()
    {
        $litmock = $this->getMockBuilder('EasyRdf\Literal')->disableOriginalConstructor()->getMock();
        $resmock = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $lit = new ConceptPropertyValueLiteral($this->model, $this->vocab, $resmock, $litmock, 'skosmos:testType');
        $this->assertEquals('', $lit);
    }

    /**
     * @covers ConceptPropertyValueLiteral::getNotation
     */
    public function testGetNotation()
    {
        $litmock = $this->getMockBuilder('EasyRdf\Literal')->disableOriginalConstructor()->getMock();
        $resmock = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $lit = new ConceptPropertyValueLiteral($this->model, $this->vocab, $resmock, $litmock, 'skosmos:testType');
        $this->assertEquals(null, $lit->getNotation());
    }

    /**
     * @covers ConceptPropertyValueLiteral::getContainsHtml
     */
    public function testGetContainsHtmlWhenThereIsNone()
    {
        $litmock = $this->getMockBuilder('EasyRdf\Literal')->disableOriginalConstructor()->getMock();
        $litmock->method('getValue')->will($this->returnValue('a regular literal'));
        $resmock = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $lit = new ConceptPropertyValueLiteral($this->model, $this->vocab, $resmock, $litmock, 'skosmos:testType');
        $this->assertFalse($lit->getContainsHtml());
    }

    /**
     * @covers ConceptPropertyValueLiteral::getContainsHtml
     */
    public function testGetContainsHtmlWhenThereIsOnlyAOpeningTag()
    {
        $litmock = $this->getMockBuilder('EasyRdf\Literal')->disableOriginalConstructor()->getMock();
        $litmock->method('getValue')->will($this->returnValue('a <a href=\"http://skosmos.org\"> literal with broken html'));
        $resmock = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $lit = new ConceptPropertyValueLiteral($this->model, $this->vocab, $resmock, $litmock, 'skosmos:testType');
        $this->assertFalse($lit->getContainsHtml());
    }

    /**
     * @covers ConceptPropertyValueLiteral::getContainsHtml
     */
    public function testGetContainsHtmlWhenValueIsDate()
    {
        $litmock = $this->getMockBuilder('EasyRdf\Literal\Date')->disableOriginalConstructor()->getMock();
        $litmock->method('getValue')->will($this->returnValue(DateTime::createFromFormat('Y-m-d', '2013-10-14')));
        $resmock = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $lit = new ConceptPropertyValueLiteral($this->model, $this->vocab, $resmock, $litmock, 'skosmos:testType');
        $this->assertFalse($lit->getContainsHtml());
    }

    /**
     * @covers ConceptPropertyValueLiteral::getContainsHtml
     */
    public function testGetContainsHtml()
    {
        $litmock = $this->getMockBuilder('EasyRdf\Literal')->disableOriginalConstructor()->getMock();
        $litmock->method('getValue')->will($this->returnValue('a <a href=\"http://skosmos.org\">literal</a> with valid html'));
        $resmock = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $lit = new ConceptPropertyValueLiteral($this->model, $this->vocab, $resmock, $litmock, 'skosmos:testType');
        $this->assertTrue($lit->getContainsHtml());
    }

    /**
     * @covers ConceptPropertyValueLiteral::getXlLabel
     * @covers ConceptPropertyValueLiteral::hasXlProperties
     */
    public function testGetXlLabel()
    {
        $this->markTestSkipped('disabled since the functionality needs to be reimplemented after the new translation component is in use');
        $voc = $this->model->getVocabulary('xl');
        $conc = $voc->getConceptInfo('http://www.skosmos.skos/xl/c1', 'en')[0];
        $props = $conc->getProperties();
        $vals = $props['skos:altLabel']->getValues();
        $val = reset($vals);

        $reified_vals = array();
        if ($val->hasXlProperties()) {
            $reified_vals = $val->getXlLabel()->getProperties();
        }
        $this->assertArrayNotHasKey('skosxl:literalForm', $reified_vals);
        $this->assertArrayHasKey('skosxl:labelRelation', $reified_vals);
    }
}
