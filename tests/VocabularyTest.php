<?php

require_once('model/Model.php');

class VocabularyTest extends PHPUnit_Framework_TestCase
{
  
  private $model; 

  protected function setUp() {
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    $this->model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
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
   * @covers Vocabulary::getLabelStatistics
   */
  public function testGetLabelStatistics() {
    $vocab = $this->model->getVocabulary('test');
    $stats = $vocab->getLabelStatistics();
    foreach($stats['terms'] as $lang=>$labels) {
      $this->assertEquals(11, $labels['skos:prefLabel']);
    }
  }
  
  /**
   * @covers Vocabulary::getStatistics
   */
  public function testGetStatistics() {
    $vocab = $this->model->getVocabulary('test');
    $stats = $vocab->getStatistics();
    $this->assertEquals(16, $stats['http://www.w3.org/2004/02/skos/core#Concept']['count']);
  }
  
  /**
   * @covers Vocabulary::listConceptGroups
   */
  public function testListConceptGroups() {
    $vocab = $this->model->getVocabulary('groups');
    $cgroups = $vocab->listConceptGroups(false, 'en');
    $expected = array (0 => array ('uri' => 'http://www.skosmos.skos/groups/fish', 'hasMembers' => true, 'childGroups' => array('http://www.skosmos.skos/groups/sub'), 'prefLabel' => 'Fish'), 1 => array ('uri' => 'http://www.skosmos.skos/groups/fresh', 'hasMembers' => true, 'prefLabel' => 'Freshwater fish'), 2 => array ('uri' => 'http://www.skosmos.skos/groups/salt', 'hasMembers' => true, 'prefLabel' => 'Saltwater fish'),3 => array ('uri' => 'http://www.skosmos.skos/groups/sub', 'hasMembers' => true, 'prefLabel' => 'Submarine-like fish'));
    $this->assertEquals($expected, $cgroups);
  }
  
  /**
   * @covers Vocabulary::listConceptGroupContents
   */
  public function testListConceptGroupContents() {
    $vocab = $this->model->getVocabulary('groups');
    $cgroups = $vocab->listConceptGroupContents('http://www.skosmos.skos/groups/salt', 'en');
    $expected = array (0 => array ('uri' => 'http://www.skosmos.skos/groups/ta113','isSuper' => false,'hasMembers' => false,'type' => array (0 => 'skos:Concept'),'prefLabel' => 'Flatfish'));
    $this->assertEquals($expected, $cgroups);
  }
  
  /**
   * @covers Vocabulary::getAlphabet
   */
  public function testGetAlphabet() {
    $vocab = $this->model->getVocabulary('test');
    $alpha = $vocab->getAlphabet('en');
    $this->assertEquals(array("B","C","E","F","M","T","!*", "0-9"), $alpha);
  }

  /**
   * @covers Vocabulary::getAlphabet
   */
  public function testGetAlphabetIssue107() {
    $vocab = $this->model->getVocabulary('groups');
    $alpha = $vocab->getAlphabet('en');
    $this->assertEquals(array("G", "!*", "0-9"), $alpha);
  }

  /**
   * @covers Vocabulary::getInfo
   * @covers Vocabulary::parseVersionInfo
   */
  public function testGetInfo() {
    $vocab = $this->model->getVocabulary('test');
    $info = $vocab->getInfo();
    $this->assertEquals(array("dc:title" => array('Test ontology'), 'dc:modified' => array ('Wednesday, October 1, 2014 16:29:03'), "rdf:type" => array('http://www.w3.org/2004/02/skos/core#ConceptScheme'), "owl:versionInfo" => array('The latest and greatest version')), $info);
  }
  
  /**
   * @covers Vocabulary::getInfo
   */
  public function testGetInfoWithDC11Label() {
    $vocab = $this->model->getVocabulary('testdiff');
    $info = $vocab->getInfo();
    $this->assertEquals(array("dc11:title" => array('Test ontology 2')), $info);
  }

  /**
   * @covers Vocabulary::searchConceptsAlphabetical
   */
  public function testSearchConceptsAlphabetical() {
    $vocab = $this->model->getVocabulary('groups');
    $concepts = $vocab->searchConceptsAlphabetical('G', null, null, 'en');
    $this->assertCount(2, $concepts);
    $this->assertEquals('Grouped fish', $concepts[0]['prefLabel']);
    $this->assertEquals('Guppy', $concepts[1]['prefLabel']);
  }

  /**
   * @covers Vocabulary::searchConceptsAlphabetical
   */
  public function testSearchConceptsAlphabeticalDigits() {
    $vocab = $this->model->getVocabulary('groups');
    $concepts = $vocab->searchConceptsAlphabetical('0-9', null, null, 'en');
    $this->assertCount(1, $concepts);
    $this->assertEquals('3-eyed fish', $concepts[0]['prefLabel']);
  }

  /**
   * @covers Vocabulary::searchConceptsAlphabetical
   */
  public function testSearchConceptsAlphabeticalSpecial() {
    $vocab = $this->model->getVocabulary('groups');
    $concepts = $vocab->searchConceptsAlphabetical('!*', null, null, 'en');
    $this->assertCount(1, $concepts);
    $this->assertEquals("'fish and chips'", $concepts[0]['prefLabel']);
  }

  /**
   * @covers Vocabulary::searchConceptsAlphabetical
   */
  public function testSearchConceptsAlphabeticalEverything() {
    $vocab = $this->model->getVocabulary('groups');
    $concepts = $vocab->searchConceptsAlphabetical('*', null, null, 'en');
    $this->assertCount(4, $concepts);
  }

  /**
   * @covers Vocabulary::getBreadCrumbs
   * @covers Vocabulary::combineCrumbs
   * @covers Vocabulary::getCrumbs
   */
  public function testGetBreadCrumbs() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $resource = new EasyRdf_Resource('http://www.yso.fi/onto/yso/p14606');
    $vocabstub = $this->getMock('Vocabulary', array('getConceptTransitiveBroaders'), array($model, $resource));
    $vocabstub->method('getConceptTransitiveBroaders')->willReturn(array ( 'http://www.yso.fi/onto/yso/p4762' => array ( 'label' => 'objects', ), 'http://www.yso.fi/onto/yso/p1674' => array ( 'label' => 'physical whole', 'direct' => array ( 0 => 'http://www.yso.fi/onto/yso/p4762', ), ), 'http://www.yso.fi/onto/yso/p14606' => array ( 'label' => 'layers', 'direct' => array ( 0 => 'http://www.yso.fi/onto/yso/p1674', ), ), ));
    $result = $vocabstub->getBreadCrumbs('en', 'http://www.yso.fi/onto/yso/p14606');
    foreach($result['breadcrumbs'][0] as $crumb)    
      $this->assertInstanceOf('Breadcrumb', $crumb);
  }
  
  /**
   * @covers Vocabulary::getBreadCrumbs
   * @covers Vocabulary::combineCrumbs
   * @covers Vocabulary::getCrumbs
   */
  public function testGetBreadCrumbsShortening() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $resource = new EasyRdf_Resource('http://www.yso.fi/onto/yso/p14606');
    $vocabstub = $this->getMock('Vocabulary', array('getConceptTransitiveBroaders'), array($model, $resource));
    $vocabstub->method('getConceptTransitiveBroaders')->willReturn(array ( 'http://www.yso.fi/onto/yso/p4762' => array ( 'label' => 'objects', ), 'http://www.yso.fi/onto/yso/p13871' => array ( 'label' => 'thai language', 'direct' => array ( 0 => 'http://www.yso.fi/onto/yso/p10834', ), ), 'http://www.yso.fi/onto/yso/p556' => array ( 'label' => 'languages', 'direct' => array ( 0 => 'http://www.yso.fi/onto/yso/p2881', ), ), 'http://www.yso.fi/onto/yso/p8965' => array ( 'label' => 'Sino-Tibetan languages', 'direct' => array ( 0 => 'http://www.yso.fi/onto/yso/p556', ), ), 'http://www.yso.fi/onto/yso/p3358' => array ( 'label' => 'systems', 'direct' => array ( 0 => 'http://www.yso.fi/onto/yso/p4762', ), ), 'http://www.yso.fi/onto/yso/p10834' => array ( 'label' => 'Tai languages', 'direct' => array ( 0 => 'http://www.yso.fi/onto/yso/p8965', ), ), 'http://www.yso.fi/onto/yso/p2881' => array ( 'label' => 'cultural systems', 'direct' => array ( 0 => 'http://www.yso.fi/onto/yso/p3358', ), ), ) );
    $result = $vocabstub->getBreadCrumbs('en', 'http://www.yso.fi/onto/yso/p13871');
    $this->assertEquals(6, sizeof($result['breadcrumbs'][0]));
  }

  /**
   * @covers Vocabulary::getBreadCrumbs
   * @covers Vocabulary::combineCrumbs
   * @covers Vocabulary::getCrumbs
   */
  public function testGetBreadCrumbsCycle() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $vocab = $model->getVocabulary('cycle');
    $result = $vocab->getBreadCrumbs('en', 'http://www.skosmos.skos/cycle/ta4');
    foreach ($result['breadcrumbs'][0] as $crumb)    
      $this->assertInstanceOf('Breadcrumb', $crumb);
  }

  /**
   * @covers Vocabulary::getTopConcepts
   */
  public function testGetTopConcepts() {
    $vocab = $this->model->getVocabulary('test');
    $prop = $vocab->getTopConcepts();
    $this->assertEquals(array (0 => array ('uri' => 'http://www.skosmos.skos/test/ta1','label' => 'Fish','hasChildren' => true, 'topConceptOf' => 'http://www.skosmos.skos/test/conceptscheme')), $prop);
  }
  
  /**
   * @covers Vocabulary::getChangeList
   */
  public function testGetChangeList() {
    $vocab = $this->model->getVocabulary('changes');
    $months = $vocab->getChangeList('dc11:created','en', 'en', 0);
    $expected = array ('hurr durr' => array ('uri' => 'http://www.skosmos.skos/changes/d3', 'prefLabel' => 'Hurr Durr', 'date' => DateTime::__set_state(array('date' => '2010-02-12 10:26:39.000000', 'timezone_type' => 3, 'timezone' => 'UTC')), 'datestring' => 'Feb 12, 2010'), 'second date' => array ('uri' => 'http://www.skosmos.skos/changes/d2', 'prefLabel' => 'Second date', 'date' => DateTime::__set_state(array('date' => '2010-02-12 15:26:39.000000', 'timezone_type' => 3, 'timezone' => 'UTC')), 'datestring' => 'Feb 12, 2010'));
    $this->assertEquals(array('December 2011', 'February 2010', 'January 2000'), array_keys($months));
    $this->assertEquals($expected, $months['February 2010']);
  }
  
  /**
   * @covers Vocabulary::verifyVocabularyLanguage
   */
  public function testVerifyVocabularyLanguage() {
    $vocab = $this->model->getVocabulary('test');
    $this->assertEquals('en', $vocab->verifyVocabularyLanguage('en'));
    $this->assertEquals('en', $vocab->verifyVocabularyLanguage('de'));
  }
}
