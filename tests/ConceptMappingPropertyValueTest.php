<?php

class ConceptMappingPropertyValueTest extends PHPUnit\Framework\TestCase
{
    private $model;
    private $concept;
    private $vocab;
    private $props;

    protected function setUp(): void
    {
        $this->model = new Model(new GlobalConfig('/../../tests/testconfig.ttl'));
        $this->vocab = $this->model->getVocabulary('mapping');
        $concepts = $this->vocab->getConceptInfo('http://www.skosmos.skos/mapping/m1', 'en');
        $this->concept = $concepts[0];
        $this->props = $this->concept->getMappingProperties();
    }

    /**
     * @covers ConceptMappingPropertyValue::__construct
     */
    public function testConstructor()
    {
        $resourcestub = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $sourcestub = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $mapping = new ConceptMappingPropertyValue($this->model, $this->vocab, $resourcestub, $sourcestub, 'skos:exactMatch');
        $this->assertEquals('skos:exactMatch', $mapping->getType());
    }

    /**
     * @covers ConceptMappingPropertyValue::getLabel
     * @covers ConceptMappingPropertyValue::queryLabel
     * @covers DataObject::getExternalLabel
     */
    public function testGetLabelFromExternalVocabulary()
    {
        $this->markTestSkipped('disabled since the functionality needs to be reimplemented after the new translation component is in use');
        $propvals = $this->props['skos:exactMatch']->getValues();
        $this->assertEquals('Eel', $propvals['Eel http://www.skosmos.skos/test/ta115']->getLabel()->getValue());
    }

    /**
     * @covers ConceptMappingPropertyValue::getLabel
     * @covers ConceptMappingPropertyValue::queryLabel
     */
    public function testGetLabelResortsToUri()
    {
        $mocksource = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $mockres = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $labelmap = array(
          array('en', null),
          array(null, null)
        );
        $mockres->method('label')->will($this->returnValueMap($labelmap));
        $litmap = array(
          array('rdf:value', 'en', null),
          array('rdf:value', null)
        );
        $mockres->method('getLiteral')->will($this->returnValueMap($litmap));
        $mockres->method('getUri')->will($this->returnValue('http://thisdoesntexistatalland.sefsf/2j2h4/'));
        $mapping = new ConceptMappingPropertyValue($this->model, $this->vocab, $mockres, $mocksource, 'skos:exactMatch');
        $this->assertEquals('http://thisdoesntexistatalland.sefsf/2j2h4/', $mapping->getLabel());
    }

    /**
     * @covers ConceptMappingPropertyValue::getLabel
     * @covers ConceptMappingPropertyValue::queryLabel
     */
    public function testGetLabelWithAndWithoutLang()
    {
        $mocksource = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $mockres = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $labelmap = array(
          array('en', 'english'),
          array(null, 'default')
        );
        $mockres->method('label')->will($this->returnValueMap($labelmap));
        $mockres->method('getUri')->will($this->returnValue('http://thisdoesntexistatalland.sefsf/2j2h4/'));
        $mapping = new ConceptMappingPropertyValue($this->model, $this->vocab, $mockres, $mocksource, 'skos:exactMatch');
        $this->assertEquals('english', $mapping->getLabel('en'));
        $this->assertEquals('default', $mapping->getLabel());
    }

    /**
     * @covers ConceptMappingPropertyValue::getLabel
     * @covers ConceptMappingPropertyValue::queryLabel
     */
    public function testGetLabelWithLiteralAndLang()
    {
        $mocksource = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $mockres = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $labelmap = array(
          array('en', null),
          array(null, null)
        );
        $mockres->method('label')->will($this->returnValueMap($labelmap));
        $litmap = array(
          array('rdf:value', 'en', 'english lit'),
          array('rdf:value', null, 'default lit')
        );
        $mockres->method('getLiteral')->will($this->returnValueMap($litmap));
        $mockres->method('getUri')->will($this->returnValue('http://thisdoesntexistatalland.sefsf/2j2h4/'));
        $mapping = new ConceptMappingPropertyValue($this->model, $this->vocab, $mockres, $mocksource, 'skos:exactMatch');
        $this->assertEquals('english lit', $mapping->getLabel('en'));
        $this->assertEquals('default lit', $mapping->getLabel());
    }

    /**
     * @covers ConceptMappingPropertyValue::getNotation
     */
    public function testGetNotation()
    {
        $mocksource = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $mockres = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $mocklit = $this->getMockBuilder('EasyRdf\Literal')->disableOriginalConstructor()->getMock();
        $mocklit->method('getValue')->will($this->returnValue('666'));
        $map = array(
            array('skos:notation', null, null, $mocklit),
            array(null,null,null,null),
        );
        $mockres->method('get')->will($this->returnValueMap($map));
        $mapping = new ConceptMappingPropertyValue($this->model, $this->vocab, $mockres, $mocksource, 'skos:exactMatch');
        $this->assertEquals(666, $mapping->getNotation());
    }

    /**
     * @covers ConceptMappingPropertyValue::getExVocab
     */
    public function testGetExVocab()
    {
        $this->markTestSkipped('disabled since the functionality needs to be reimplemented after the new translation component is in use');
        $propvals = $this->props['skos:exactMatch']->getValues();
        $this->assertInstanceOf('Vocabulary', $propvals['Eel http://www.skosmos.skos/test/ta115']->getExVocab());
        $this->assertEquals('test', $propvals['Eel http://www.skosmos.skos/test/ta115']->getExVocab()->getId());
    }

    /**
     * @covers ConceptMappingPropertyValue::getVocabName
     */
    public function testGetVocabNameWithExternalVocabulary()
    {
        $this->markTestSkipped('disabled since the functionality needs to be reimplemented after the new translation component is in use');
        $propvals = $this->props['skos:exactMatch']->getValues();
        $this->assertEquals('Test ontology', $propvals['Eel http://www.skosmos.skos/test/ta115']->getVocabName());
    }

    /**
     * @covers ConceptMappingPropertyValue::getUri
     */
    public function testGetUri()
    {
        $this->markTestSkipped('disabled since the functionality needs to be reimplemented after the new translation component is in use');
        $propvals = $this->props['skos:exactMatch']->getValues();
        $this->assertEquals('http://www.skosmos.skos/test/ta115', $propvals['Eel http://www.skosmos.skos/test/ta115']->getUri());
    }

    /**
     * @covers ConceptMappingPropertyValue::getVocab
     */
    public function testGetVocab()
    {
        $this->markTestSkipped('disabled since the functionality needs to be reimplemented after the new translation component is in use');
        $propvals = $this->props['skos:exactMatch']->getValues();
        $this->assertEquals($this->vocab, $propvals['Eel http://www.skosmos.skos/test/ta115']->getVocab());
    }

    /**
     * @covers ConceptMappingPropertyValue::getType
     */
    public function testGetType()
    {
        $this->markTestSkipped('disabled since the functionality needs to be reimplemented after the new translation component is in use');
        $propvals = $this->props['skos:exactMatch']->getValues();
        $this->assertEquals('skos:exactMatch', $propvals['Eel http://www.skosmos.skos/test/ta115']->getType());
    }

    /**
     * @covers ConceptMappingPropertyValue::__toString
     */
    public function testToString()
    {
        $this->markTestSkipped('disabled since the functionality needs to be reimplemented after the new translation component is in use');
        $propvals = $this->props['skos:exactMatch']->getValues();
        $this->assertEquals('Eel', $propvals['Eel http://www.skosmos.skos/test/ta115']->__toString());
    }

    /**
     * @covers ConceptMappingPropertyValue::asJskos
     */
    public function testAsJskos()
    {
        $this->markTestSkipped('disabled since the functionality needs to be reimplemented after the new translation component is in use');
        $propvals = $this->props['skos:exactMatch']->getValues();
        $this->assertEquals([
          'type' => [
            'skos:exactMatch',
          ],
          'toScheme' => [
            'uri' => 'http://www.skosmos.skos/test/conceptscheme',
          ],
          'from' => [
            'memberSet' => [
              [
                'uri' => 'http://www.skosmos.skos/mapping/m1',
              ]
            ]
          ],
          'to' => [
            'memberSet' => [
              [
                'uri' => 'http://www.skosmos.skos/test/ta115',
                'prefLabel' => [
                  'en' => 'Eel',
                ]
              ]
            ]
          ],
          'uri' => 'http://www.skosmos.skos/mapping/m1',
          'notation' => null,
          'prefLabel' => 'Eel',
          'description' => 'Exactly matching concepts in another vocabulary.',
          'hrefLink' => null,
          'lang' => 'en',
          'vocabName' => 'Test ontology',
          'typeLabel' => 'Exactly matching concepts',
        ], $propvals['Eel http://www.skosmos.skos/test/ta115']->asJskos());
    }

}
