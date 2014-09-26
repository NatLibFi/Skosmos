<?php

require_once('model/Model.php');

class VocabularyTest extends PHPUnit_Framework_TestCase
{
  
  private $model; 

  protected function setUp() {
    require_once 'testconfig.inc';
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
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
    $this->assertEquals('http://localhost:3030/ds/sparql', $endpoint);
  }

  /**
   * @covers Vocabulary::getGraph
   */
  public function testGetGraph() {
    $vocab = $this->model->getVocabulary('testdiff');
    $graph = $vocab->getGraph();
    $this->assertEquals('http://www.skosmos.skos/testdiff/', $graph);
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
  
  /**
   * @covers Vocabulary::getDefaultConceptScheme
   */
  public function testGetDefaultConceptScheme() {
    $vocab = $this->model->getVocabulary('testdiff');
    $cs = $vocab->getDefaultConceptScheme();
    $this->assertEquals('http://www.skosmos.skos/testdiff#conceptscheme', $cs);
  }
  
  /**
   * @covers Vocabulary::getDefaultConceptScheme
   */
  public function testGetDefaultConceptSchemeNotSet() {
    $vocab = $this->model->getVocabulary('test');
    $cs = $vocab->getDefaultConceptScheme();
    $this->assertEquals('http://www.skosmos.skos/test/conceptscheme', $cs);
  }
  
  /**
   * @covers Vocabulary::getConceptSchemes
   */
  public function testGetConceptSchemesFromFuseki() {
    $vocab = $this->model->getVocabulary('test');
    $cs = $vocab->getConceptSchemes();
    foreach($cs as $scheme=>$label) {
      $this->assertEquals('http://www.skosmos.skos/test/conceptscheme', $scheme);
      $this->assertEquals('Test conceptscheme', $label['label']);
    }
  }
  
  /**
   * @covers Vocabulary::getStatistics
   */
  public function testGetStatistics() {
    $vocab = $this->model->getVocabulary('test');
    $stats = $vocab->getStatistics();
    $this->assertEquals(10, $stats['concepts']);
    foreach($stats['terms'] as $lang=>$labels) {
      $this->assertEquals(8, $labels['skos:prefLabel']);
    }
  }

  /**
   * @covers Vocabulary::getAlphabet
   */
  public function testGetAlphabet() {
    $vocab = $this->model->getVocabulary('test');
    $alpha = $vocab->getAlphabet();
    $this->assertEquals(array("B","C","E","F","T", "!*", "0-9"), $alpha);
  }

  /**
   * @covers Vocabulary::getInfo
   */
  public function testGetInfo() {
    $vocab = $this->model->getVocabulary('test');
    $info = $vocab->getInfo();
    $this->assertEquals(array("dc:title" => array('Test ontology'), "dc:subject" => array('Science and medicine'), "rdf:type" => array('http://www.w3.org/2004/02/skos/core#ConceptScheme')), $info);
  }
  
  /**
   * @covers Vocabulary::getInfo
   */
  public function testGetInfoWithDC11Label() {
    $vocab = $this->model->getVocabulary('testdiff');
    $info = $vocab->getInfo();
    $this->assertEquals(array("dc11:title" => array('Test ontology 2')), $info);
  }

}
