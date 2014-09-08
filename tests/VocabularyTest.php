<?php

require_once('model/Model.php');

class VocabularyTest extends PHPUnit_Framework_TestCase
{
  
  private $model; 

  protected function setUp() {
    require_once 'testconfig.inc';
    $this->model = new Model();
  }

  /**
   * @covers Vocabulary::getId
   */
  public function testGetId() {
    $vocab = $this->model->getVocabulary('test');
    $id = $vocab->getId();
    $this->assertEquals('test', $id);
  }
  
  /**
   * @covers Vocabulary::getTitle
   */
  public function testGetTitle() {
    $vocab = $this->model->getVocabulary('test');
    $title = $vocab->getTitle();
    $this->assertEquals('Test ontology', $title);
  }
  
  /**
   * @covers Vocabulary::getLanguages
   */
  public function testGetLanguages() {
    $vocab = $this->model->getVocabulary('testdiff');
    $langs = $vocab->getLanguages();
    $this->assertEquals(2, sizeof($langs));
  }
  
  /**
   * @covers Vocabulary::getDefaultLanguage
   */
  public function testGetDefaultLanguage() {
    $vocab = $this->model->getVocabulary('test');
    $lang = $vocab->getDefaultLanguage();
    $this->assertEquals('en', $lang);
  }
  
  /**
   * @covers Vocabulary::getDefaultLanguage
   * @expectedException \Exception
   * @expectedExceptionMessage Default language for vocabulary 'testdiff' unknown, choosing
   */
  public function testGetDefaultLanguageWhenNotSet() {
    $vocab = $this->model->getVocabulary('testdiff');
    $lang = $vocab->getDefaultLanguage();
  }

  /**
   * @covers Vocabulary::getEndpoint
   */
  public function testGetEndpoint() {
    $vocab = $this->model->getVocabulary('testdiff');
    $endpoint = $vocab->getEndpoint();
    $this->assertEquals('http://api.dev.finto.fi/sparql', $endpoint);
  }

  /**
   * @covers Vocabulary::getGraph
   */
  public function testGetGraph() {
    $vocab = $this->model->getVocabulary('testdiff');
    $graph = $vocab->getGraph();
    $this->assertEquals('http://www.yso.fi/onto/testdiff/', $graph);
  }
  
  /**
   * @covers Vocabulary::getSparql
   */
  public function testGetSparql() {
    $vocab = $this->model->getVocabulary('test');
    $sparql = $vocab->getSparql();
    $this->assertInstanceOf('GenericSparql', $sparql);
  }
  
  /**
   * @covers Vocabulary::getSparql
   */
  public function testGetSparqlWithDialect() {
    $vocab = $this->model->getVocabulary('testdiff');
    $sparql = $vocab->getSparql();
    $this->assertInstanceOf('JenaTextSparql', $sparql);
  }
  
  /**
   * @covers Vocabulary::getUriSpace
   */
  public function testGetUriSpace() {
    $vocab = $this->model->getVocabulary('testdiff');
    $sparql = $vocab->getUriSpace();
    $this->assertEquals('http://www.skosmos.skos/onto/testdiff#', $sparql);
  }
  
  /**
   * @covers Vocabulary::getLocalName
   */
  public function testGetLocalName() {
    $vocab = $this->model->getVocabulary('testdiff');
    $name = $vocab->getLocalName('http://www.skosmos.skos/onto/testdiff#concept23');
    $this->assertEquals('concept23', $name);
  }
  
  /**
   * @covers Vocabulary::getConceptURI
   */
  public function testGetConceptURI() {
    $vocab = $this->model->getVocabulary('testdiff');
    $name = $vocab->getConceptURI('concept23');
    $this->assertEquals('http://www.skosmos.skos/onto/testdiff#concept23', $name);
  }
  
  /**
   * @covers Vocabulary::getConceptURI
   */
  public function testGetConceptURIWhenGivenAReadyURI() {
    $vocab = $this->model->getVocabulary('testdiff');
    $name = $vocab->getConceptURI('http://www.skosmos.skos/onto/testdiff#concept23');
    $this->assertEquals('http://www.skosmos.skos/onto/testdiff#concept23', $name);
  }
  
  /**
   * @covers Vocabulary::getAlphabeticalFull
   */
  public function testGetFullAlphabeticalIndex() {
    $vocab = $this->model->getVocabulary('testdiff');
    $boolean = $vocab->getAlphabeticalFull();
    $this->assertEquals(true, $boolean);
  }
  
  /**
   * @covers Vocabulary::getAlphabeticalFull
   */
  public function testGetFullAlphabeticalIndexWhenNotSet() {
    $vocab = $this->model->getVocabulary('test');
    $boolean = $vocab->getAlphabeticalFull();
    $this->assertEquals(false, $boolean);
  }
  
  /**
   * @covers Vocabulary::getShortName
   */
  public function testGetShortName() {
    $vocab = $this->model->getVocabulary('test');
    $name = $vocab->getShortName();
    $this->assertEquals('Test short', $name);
  }
  
  /**
   * @covers Vocabulary::getShortName
   */
  public function testGetShortNameNotDefined() {
    $vocab = $this->model->getVocabulary('testdiff');
    $name = $vocab->getShortName();
    $this->assertEquals('testdiff', $name);
  }
  
  /**
   * @covers Vocabulary::getDefaultConceptScheme
   */
  public function testGetDefaultConceptScheme() {
    $vocab = $this->model->getVocabulary('test');
    $cs = $vocab->getDefaultConceptScheme();
    $this->assertEquals('http://www.yso.fi/onto/test/', $cs);
  }
  
  /**
   * @covers Vocabulary::getDataURL
   */
  public function testGetDataURL() {
    $vocab = $this->model->getVocabulary('test');
    $url = $vocab->getDataURL();
    $this->assertEquals('http://skosmos.skos/dump/test/', $url);
  }
  
  /**
   * @covers Vocabulary::getDataURL
   */
  public function testGetDataURLNotSet() {
    $vocab = $this->model->getVocabulary('testdiff');
    $url = $vocab->getDataURL();
    $this->assertEquals(false, $url);
  }
  
  /**
   * @covers Vocabulary::getGroupClassURI
   */
  public function testGetGroupClassURI() {
    $vocab = $this->model->getVocabulary('test');
    $uri = $vocab->getGroupClassURI();
    $this->assertEquals('http://www.w3.org/2004/02/skos/core#Collection', $uri);
  }
  
  /**
   * @covers Vocabulary::getGroupClassURI
   */
  public function testGetGroupClassURINotSet() {
    $vocab = $this->model->getVocabulary('testdiff');
    $uri = $vocab->getGroupClassURI();
    $this->assertEquals(null, $uri);
  }
  
  /**
   * @covers Vocabulary::getArrayClassURI
   */
  public function testGetArrayClassURI() {
    $vocab = $this->model->getVocabulary('test');
    $uri = $vocab->getArrayClassURI();
    $this->assertEquals('http://purl.org/iso25964/skos-thes#ThesaurusArray', $uri);
  }
  
  /**
   * @covers Vocabulary::getArrayClassURI
   */
  public function testGetArrayClassURINotSet() {
    $vocab = $this->model->getVocabulary('testdiff');
    $uri = $vocab->getArrayClassURI();
    $this->assertEquals(null, $uri);
  }
  
  /**
   * @covers Vocabulary::getShowHierarchy
   */
  public function testGetShowHierarchy() {
    $vocab = $this->model->getVocabulary('test');
    $uri = $vocab->getShowHierarchy();
    $this->assertEquals(true, $uri);
  }

 
  /**
   * @covers Vocabulary::getShowHierarchy
   */
  public function testGetShowHierarchyNotSet() {
    $vocab = $this->model->getVocabulary('testdiff');
    $uri = $vocab->getShowHierarchy();
    $this->assertEquals(false, $uri);
  }

}
