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
  
}
