<?php

class ConceptMappingPropertyValueTest extends PHPUnit\Framework\TestCase
{
    private $model;
    private $concept;
    private $vocab;
    private $props;

    protected function setUp(): void
    {
        $this->model = new Model();
        $this->vocab = $this->model->getVocabulary('mapping');
        $this->concept = $this->vocab->getConceptInfo('http://www.skosmos.skos/mapping/m1', 'en');
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
     * @covers ConceptMappingPropertyValue::getSortKey
     * @covers ConceptMappingPropertyValue::getLabel
     * @covers ConceptMappingPropertyValue::queryLabel
     * @covers DataObject::getExternalLabel
     */
    public function testGetSortKey()
    {
        $propvals = $this->props['skos:exactMatch']->getValues();
        $this->assertEquals('test ontology: eel', $propvals['test:ta115 http://www.skosmos.skos/test/ta115']->getSortKey());
    }

    /**
     * @covers ConceptMappingPropertyValue::getLabel
     * @covers ConceptMappingPropertyValue::queryLabel
     * @covers DataObject::getExternalLabel
     */
    public function testGetLabelFromExternalVocabulary()
    {
        $propvals = $this->props['skos:exactMatch']->getValues();
        $this->assertEquals('Eel', $propvals['test:ta115 http://www.skosmos.skos/test/ta115']->getLabel()->getValue());
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
          array('en', [], 'english'),
          array(null, [], 'default')
        );
        $mockres->method('label')->will($this->returnValueMap($labelmap));
        $mockres->method('getUri')->will($this->returnValue('http://thisdoesntexistatalland.sefsf/2j2h4/'));
        $mapping = new ConceptMappingPropertyValue($this->model, $this->vocab, $mockres, $mocksource, 'skos:exactMatch');
        $this->assertEquals('english', $mapping->getLabel('en'));
        // no language requested: the vocabulary's default language (en) is tried first
        $this->assertEquals('english', $mapping->getLabel());
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
        // no language requested: the vocabulary's default language (en) is tried first
        $this->assertEquals('english lit', $mapping->getLabel());
        $this->assertEquals('english lit', $mapping->getLabel()); // from labelcache
    }

    /**
     * @covers ConceptMappingPropertyValue::getLabel
     * @covers ConceptMappingPropertyValue::queryLabel
     * @covers ConceptMappingPropertyValue::getResourceLabel
     * @covers ConceptMappingPropertyValue::getLabelLanguages
     */
    public function testGetLabelPrefersConfiguredLanguageOverAnyOtherLanguage()
    {
        // Simulates a remote resource (e.g. from Wikidata) that has labels in many
        // languages: the label in a configured language must be preferred over
        // a label in an unrelated language.
        $mocksource = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $mockres = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $labelmap = array(
          array('ky', [], 'Аял'),
          array('nds', [], 'weiblich Geschlecht'),
          array('en', [], 'female'),
          array(null, [], 'Аял')
        );
        $mockres->method('label')->will($this->returnValueMap($labelmap));
        $mockres->method('getUri')->will($this->returnValue('http://www.wikidata.org/entity/Q6581072'));
        $mapping = new ConceptMappingPropertyValue($this->model, $this->vocab, $mockres, $mocksource, 'skos:exactMatch');
        $this->assertEquals('female', $mapping->getLabel('en'));
        // no language requested: falls back to the vocabulary's default language (en)
        $this->assertEquals('female', $mapping->getLabel());
    }

    /**
     * @covers ConceptMappingPropertyValue::getLabel
     * @covers ConceptMappingPropertyValue::queryLabel
     * @covers ConceptMappingPropertyValue::getResourceLabel
     * @covers ConceptMappingPropertyValue::getLabelLanguages
     */
    public function testGetLabelFallsBackToUiLanguage()
    {
        // The resource has no label in any of the vocabulary's configured
        // languages (en) but has a label in a UI language (fi).
        $mocksource = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $mockres = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $labelmap = array(
          array('fi', [], 'nainen'),
          array(null, [], 'nainen')
        );
        $mockres->method('label')->will($this->returnValueMap($labelmap));
        $mockres->method('getUri')->will($this->returnValue('http://www.wikidata.org/entity/Q1243'));
        $mapping = new ConceptMappingPropertyValue($this->model, $this->vocab, $mockres, $mocksource, 'skos:exactMatch');
        $this->assertEquals('nainen', $mapping->getLabel('en'));
    }

    /**
     * @covers ConceptMappingPropertyValue::getLabel
     * @covers ConceptMappingPropertyValue::queryLabel
     * @covers ConceptMappingPropertyValue::getResourceLabel
     * @covers ConceptMappingPropertyValue::getLabelLanguages
     */
    public function testGetLabelPrefersUnlabeledLiteralOverAnyLanguageLabel()
    {
        // The resource has no label in any configured language; a language-neutral
        // literal must be preferred over a label in an unrelated language.
        $mocksource = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $mockres = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $labelmap = array(
          array('ky', [], 'Аял'),
          array(null, [], 'Аял')
        );
        $mockres->method('label')->will($this->returnValueMap($labelmap));
        $literal = $this->getMockBuilder('EasyRdf\Literal')->disableOriginalConstructor()->getMock();
        $literal->method('getLang')->will($this->returnValue(null));
        $mockres->method('allLiterals')->willReturnCallback(function ($property) use ($literal) {
            if ($property === 'rdfs:label') {
                return array($literal);
            }
            return array();
        });
        $mockres->method('getUri')->will($this->returnValue('http://www.wikidata.org/entity/Q6581072'));
        $mapping = new ConceptMappingPropertyValue($this->model, $this->vocab, $mockres, $mocksource, 'skos:exactMatch');
        $this->assertEquals($literal, $mapping->getLabel('en'));
    }

    /**
     * @covers ConceptMappingPropertyValue::getLabel
     * @covers ConceptMappingPropertyValue::queryLabel
     * @covers ConceptMappingPropertyValue::getResourceLabel
     * @covers ConceptMappingPropertyValue::getLabelLanguages
     */
    public function testGetLabelAnyLanguageIsOnlyLastResort()
    {
        // The resource has no label in any configured language, only in an
        // unrelated one: the any-language label is used as a last resort.
        $mocksource = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $mockres = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
        $labelmap = array(
          array('ky', [], 'Аял'),
          array(null, [], 'Аял')
        );
        $mockres->method('label')->will($this->returnValueMap($labelmap));
        $mockres->method('getUri')->will($this->returnValue('http://www.wikidata.org/entity/Q6581072'));
        $mapping = new ConceptMappingPropertyValue($this->model, $this->vocab, $mockres, $mocksource, 'skos:exactMatch');
        $this->assertEquals('Аял', $mapping->getLabel('en'));
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
        $propvals = $this->props['skos:exactMatch']->getValues();
        $this->assertInstanceOf('Vocabulary', $propvals['test:ta115 http://www.skosmos.skos/test/ta115']->getExVocab());
        $this->assertEquals('test', $propvals['test:ta115 http://www.skosmos.skos/test/ta115']->getExVocab()->getId());
    }

    /**
     * @covers ConceptMappingPropertyValue::getVocabName
     */
    public function testGetVocabNameWithExternalVocabulary()
    {
        $propvals = $this->props['skos:exactMatch']->getValues();
        $this->assertEquals('Test ontology', $propvals['test:ta115 http://www.skosmos.skos/test/ta115']->getVocabName());
    }

    /**
     * @covers ConceptMappingPropertyValue::getUri
     */
    public function testGetUri()
    {
        $propvals = $this->props['skos:exactMatch']->getValues();
        $this->assertEquals('http://www.skosmos.skos/test/ta115', $propvals['test:ta115 http://www.skosmos.skos/test/ta115']->getUri());
    }

    /**
     * @covers ConceptMappingPropertyValue::getVocab
     */
    public function testGetVocab()
    {
        $propvals = $this->props['skos:exactMatch']->getValues();
        $this->assertEquals($this->vocab, $propvals['test:ta115 http://www.skosmos.skos/test/ta115']->getVocab());
    }

    /**
     * @covers ConceptMappingPropertyValue::getType
     */
    public function testGetType()
    {
        $propvals = $this->props['skos:exactMatch']->getValues();
        $this->assertEquals('skos:exactMatch', $propvals['test:ta115 http://www.skosmos.skos/test/ta115']->getType());
    }

    /**
     * @covers ConceptMappingPropertyValue::__toString
     */
    public function testToString()
    {
        $propvals = $this->props['skos:exactMatch']->getValues();
        $this->assertEquals('Eel', $propvals['test:ta115 http://www.skosmos.skos/test/ta115']->__toString());
    }

    /**
     * @covers ConceptMappingPropertyValue::asJskos
     */
    public function testAsJskos()
    {
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
        ], $propvals['test:ta115 http://www.skosmos.skos/test/ta115']->asJskos());
   }

   /**
    * Mapping labels must be returned in the concept's content language,
    * even when the mapping target comes from an external resource.
    *
    * @covers Concept::getMappingProperties
    * @covers ConceptMappingPropertyValue::getLabel
    */
   public function testMappingPropertiesUseContentLanguageForExternalTarget()
   {
       $uri = 'http://www.wikidata.org/entity/Q6581072'; // not in any configured uri space

       // the external resource has labels in multiple languages
       $extGraph = new EasyRdf\Graph();
       $ext = $extGraph->resource($uri);
       $ext->addLiteral('skos:prefLabel', 'female', 'en');
       $ext->addLiteral('skos:prefLabel', 'nainen', 'fi');
       $ext->addLiteral('skos:prefLabel', 'kvinnlig', 'sv');

       $model = $this->getMockBuilder('Model')->onlyMethods(['getResourceFromUri'])->getMock();
       $model->method('getResourceFromUri')->with($uri)->willReturn($ext);

       $graph = new EasyRdf\Graph();
       $source = $graph->resource('http://www.skosmos.skos/mapping/m1');
       $source->addResource('skos:exactMatch', $uri);

       // content language is Finnish, UI language is English
       $concept = new Concept($model, $this->vocab, $source, $graph, 'fi');
       $props = $concept->getMappingProperties();
       $values = $props['skos:exactMatch']->getValues();
       $this->assertCount(1, $values);
       $value = reset($values);

       // the label must be in the content language (fi), not the requested/UI language (en)
       $this->assertEquals('nainen', $value->getLabel('en'));
       $this->assertEquals('nainen', $value->getLabel());
   }

}
