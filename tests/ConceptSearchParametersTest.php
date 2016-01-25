<?php

class ConceptSearchParametersTest extends PHPUnit_Framework_TestCase
{
    private $model; 
    private $request;

    protected function setUp() {
        putenv("LC_ALL=en_GB.utf8");
        setlocale(LC_ALL, 'en_GB.utf8');
        $this->request = $this->getMockBuilder('Request')->disableOriginalConstructor()->getMock();
    }
    
    protected function tearDown() {
        $this->params = null;
    }
  
    /**
     * @covers ConceptSearchParameters::getLang
     */
    public function testGetLang() {
        $this->request->method('getLang')->will($this->returnValue('en'));
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.inc'), true);
        $this->assertEquals('en', $params->getLang());
        $this->request->method('getQueryParam')->will($this->returnValue('sv'));
        $this->assertEquals('sv', $params->getLang());
    }

    /**
     * @covers ConceptSearchParameters::getVocabs
     * @covers ConceptSearchParameters::setVocabularies
     */
    public function testGetVocabs() {
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.inc'));
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
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.inc'));
        $this->assertEquals(null, $params->getVocabids());
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.inc'), true);
        $this->assertEquals(null, $params->getVocabids());
        $this->request->method('getQueryParam')->will($this->onConsecutiveCalls('test vocab', 'test')); //$this->returnValue('test vocab'));
        $this->assertEquals(array('test', 'vocab'), $params->getVocabids());
        $this->assertEquals(array('test'), $params->getVocabids());
    }
  
    /**
     * @covers ConceptSearchParameters::getSearchTerm
     */
    public function testGetSearchTerm() {
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.inc'));
        $this->assertEquals('*', $params->getSearchTerm());
        $this->request->method('getQueryParam')->will($this->returnValue('test'));
        $this->assertEquals('test*', $params->getSearchTerm());
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.inc'), true);
        $this->assertEquals('test', $params->getSearchTerm());
    }
  
    /**
     * @covers ConceptSearchParameters::getTypeLimit
     */
    public function testGetTypeLimit() {
        $params = new ConceptSearchParameters($this->request, new GlobalConfig('/../tests/testconfig.inc'));
        $this->assertEquals(array('skos:Concept'), $params->getTypeLimit());
        $this->request->method('getQueryParam')->will($this->returnValue('isothes:ThesaurusArray+skos:Collection'));
        $this->assertEquals(array('isothes:ThesaurusArray', 'skos:Collection'), $params->getTypeLimit());
    }
}
