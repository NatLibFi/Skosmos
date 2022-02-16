<?php

class ConceptSearchParametersTest extends PHPUnit\Framework\TestCase
{
    private $model;
    private $request;

    protected function setUp() : void
    {
        putenv("LANGUAGE=en_GB.utf8");
        putenv("LC_ALL=en_GB.utf8");
        setlocale(LC_ALL, 'en_GB.utf8');
        $this->request = $this->getMockBuilder('Request')->disableOriginalConstructor()->getMock();
        $this->model = new Model(new GlobalConfig('/../tests/testconfig.ttl'));
    }

    protected function tearDown() : void
    {
        $this->params = null;
    }

    /**
     * @covers ConceptSearchParameters::__construct
     * @covers ConceptSearchParameters::getSearchLimit
     */
    public function testConstructorAndSearchLimit() {
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'), true);
        $this->assertEquals(0, $params->getSearchLimit());
    }

    /**
     * @covers ConceptSearchParameters::getLang
     */
    public function testGetLang() {
        $this->request->method('getLang')->will($this->returnValue('en'));
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'), true);
        $this->assertEquals('en', $params->getLang());
        $this->request->method('getQueryParam')->will($this->returnValue('sv'));
        $this->assertEquals('sv', $params->getLang());
    }

    /**
     * @covers ConceptSearchParameters::getVocabs
     * @covers ConceptSearchParameters::setVocabularies
     */
    public function testGetVocabs() {
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'));
        $this->assertEquals(array(), $params->getVocabs());
        $this->request->method('getVocab')->will($this->returnValue('vocfromrequest'));
        $this->assertEquals(array('vocfromrequest'), $params->getVocabs());
        $params->setVocabularies(array('something'));
        $this->assertEquals(array('something'), $params->getVocabs());
    }

    /**
     * @covers ConceptSearchParameters::getVocabids
     */
    public function testGetVocabids() {
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'));
        $this->assertEquals(null, $params->getVocabids());
        $params->setVocabularies(array($this->model->getVocabulary('test')));
        $this->assertEquals(array('test'), $params->getVocabids());
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'), true);
        $this->assertEquals(null, $params->getVocabids());
        $mockreq = $this->getMockBuilder('Request')->disableOriginalConstructor()->getMock();
        $qparams = array(
            array('vocab', 'test'),
            array('vocabs', 'test dates')
        );
        $mockreq->method('getQueryParam')->will($this->returnValueMap($qparams));
        $params = new ConceptSearchParameters($mockreq, new GlobalConfig('/../tests/testconfig.ttl'));
        $this->assertEquals(array('test', 'dates'), $params->getVocabids());
        $params = new ConceptSearchParameters($mockreq, new GlobalConfig('/../tests/testconfig.ttl'), true);
        $this->assertEquals(array('test'), $params->getVocabids());
    }

    /**
     * @covers ConceptSearchParameters::getSearchTerm
     */
    public function testGetSearchTerm() {
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'));
        $this->assertEquals('*', $params->getSearchTerm());
        $this->request->method('getQueryParamRaw')->will($this->returnValue('test'));
        $this->assertEquals('test*', $params->getSearchTerm());
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'), true);
        $this->assertEquals('test', $params->getSearchTerm());
    }

    /**
     * @covers ConceptSearchParameters::getSearchTerm
     * Is created particularly for searches not using REST
     */
    public function testSearchTermWithoutRest() {
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'), false);
        $this->request->method('getQueryParamRaw')->will($this->returnValue('0'));
        $this->assertEquals('0*', $params->getSearchTerm());
    }

    /**
     * @covers ConceptSearchParameters::getTypeLimit
     * @covers ConceptSearchParameters::getDefaultTypeLimit
     */
    public function testGetTypeLimitNoQueryParam() {
        $mockreq = $this->getMockBuilder('Request')->disableOriginalConstructor()->getMock();
        $mockreq->method('getVocab')->will($this->returnValue($this->model->getVocabulary('test')));
        $params = new ConceptSearchParameters($mockreq, new GlobalConfig('/../tests/testconfig.ttl'));
        $this->assertEquals(array('skos:Concept', 'http://purl.org/iso25964/skos-thes#ThesaurusArray', 'http://www.w3.org/2004/02/skos/core#Collection'), $params->getTypeLimit());
    }

    /**
     * @covers ConceptSearchParameters::getTypeLimit
     * @covers ConceptSearchParameters::getDefaultTypeLimit
     */
    public function testGetTypeLimit() {
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'));
        $this->assertEquals(array('skos:Concept'), $params->getTypeLimit());
        $this->request->method('getQueryParam')->will($this->returnValue('isothes:ThesaurusArray+skos:Collection'));
        $this->assertEquals(array('isothes:ThesaurusArray', 'skos:Collection'), $params->getTypeLimit());
    }

    /**
     * @covers ConceptSearchParameters::getTypeLimit
     * @covers ConceptSearchParameters::getDefaultTypeLimit
     */
    public function testGetTypeLimitOnlyOne() {
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'));
        $this->request->method('getQueryParam')->will($this->returnValue('skos:Collection'));
        $this->assertEquals(array('skos:Collection'), $params->getTypeLimit());
    }

    /**
     * @covers ConceptSearchParameters::getUnique
     * @covers ConceptSearchParameters::setUnique
     */
    public function testGetAndSetUnique() {
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'));
        $this->assertEquals(false, $params->getUnique());
        $params->setUnique(true);
        $this->assertEquals(true, $params->getUnique());
    }

    /**
     * @covers ConceptSearchParameters::getHidden
     * @covers ConceptSearchParameters::setHidden
     */
    public function testGetAndSetHidden() {
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'));
        $this->assertEquals(true, $params->getHidden());
        $params->setHidden(false);
        $this->assertEquals(false, $params->getHidden());
    }

    /**
     * @covers ConceptSearchParameters::getArrayClass
     */
    public function testGetArrayClassWithoutVocabulary() {
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'));
        $this->assertEquals(null, $params->getArrayClass());
    }

    /**
     * @covers ConceptSearchParameters::getArrayClass
     */
    public function testGetArrayClass() {
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'));
        $params->setVocabularies(array($this->model->getVocabulary('test')));
        $this->assertEquals('http://purl.org/iso25964/skos-thes#ThesaurusArray', $params->getArrayClass());
    }

    /**
     * @covers ConceptSearchParameters::getSchemeLimit
     * @covers ConceptSearchParameters::getQueryParam
     */
    public function testGetSchemeLimit() {
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'));
        $this->assertEquals([], $params->getSchemeLimit());
        $this->request->method('getQueryParam')->will($this->returnValue('http://www.skosmos.skos/test/ http://www.skosmos.skos/date/'));
        $this->assertEquals(array(0 => 'http://www.skosmos.skos/test/', 1 => 'http://www.skosmos.skos/date/'), $params->getSchemeLimit());
    }

    /**
     * @covers ConceptSearchParameters::getContentLang
     */
    public function testGetContentLang() {
        $this->request->method('getContentLang')->will($this->returnValue('de'));
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'));
        $this->assertEquals('de', $params->getContentLang());
    }

    /**
     * @covers ConceptSearchParameters::getSearchLang
     */
    public function testGetSearchLang() {
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'));
        $this->assertEquals(null, $params->getSearchLang());
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'));
        $this->request->method('getContentLang')->will($this->returnValue('en'));
        $this->request->method('getQueryParam')->will($this->returnValue('on')); //anylang=on
        $this->assertEquals('', $params->getSearchLang());
    }

    /**
     * @covers ConceptSearchParameters::getOffset
     */
    public function testGetOffsetNonNumeric() {
        $this->request->method('getQueryParam')->will($this->returnValue('notvalid'));
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'));
        $this->assertEquals(0, $params->getOffset());
    }

    /**
     * @covers ConceptSearchParameters::getOffset
     */
    public function testGetOffsetValid() {
        $this->request->method('getQueryParam')->will($this->returnValue(25));
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'));
        $this->assertEquals(25, $params->getOffset());
    }

    /**
     * @covers ConceptSearchParameters::getAdditionalFields
     */
    public function testGetAdditionalField() {
        $map = array(
            array('fields', 'broader')
        );
        $this->request->method('getQueryParam')->will($this->returnValueMap($map));
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'));
        $this->assertEquals(array('broader'), $params->getAdditionalFields());
    }

    /**
     * @covers ConceptSearchParameters::getAdditionalFields
     */
    public function testGetAdditionalFields() {
        $map = array(
            array('fields', 'broader prefLabel')
        );
        $this->request->method('getQueryParam')->will($this->returnValueMap($map));
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.ttl'));
        $this->assertEquals(array('broader', 'prefLabel'), $params->getAdditionalFields());
    }

}
