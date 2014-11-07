<?php

class ModelTest extends PHPUnit_Framework_TestCase
{
  /**
   * @covers Model::__construct
   * @expectedException \Exception
   * @expectedExceptionMessage Use of undefined constant VOCABULARIES_FILE - assumed 'VOCABULARIES_FILE'
   */
  public function testConstructorNoVocabulariesConfigFile()
  {
    new Model(); 
  }
  
  /**
   * @covers Model::__construct
   * @depends testConstructorNoVocabulariesConfigFile
   */
  public function testConstructorWithConfig()
  {
    require_once 'testconfig.inc';
    new Model(); 
  }
  
  /**
   * @covers Model::getVocabularyList
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyList() {
    $model = new Model(); 
    $categories = $model->getVocabularyList();
    foreach($categories as $category)
      foreach($category as $vocab)
        $this->assertInstanceOf('Vocabulary', $vocab);
  }
  
  /**
   * @covers Model::getVocabularyCategories
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyCategories() {
    $model = new Model(); 
    $categories = $model->getVocabularyCategories();
    foreach($categories as $category)
      $this->assertInstanceOf('VocabularyCategory', $category);
  }
  
  /**
   * @covers Model::getVocabulariesInCategory
   * @depends testConstructorWithConfig
   */
  public function testGetVocabulariesInCategory() {
    $model = new Model(); 
    $category = $model->getVocabulariesInCategory('cat_science');
    foreach($category as $vocab)
      $this->assertInstanceOf('Vocabulary', $vocab);
  }
  
  /**
   * @covers Model::getVocabulary
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyById() {
    $model = new Model(); 
    $vocab = $model->getVocabulary('test');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }
  
  /**
   * @covers Model::getVocabulary
   * @depends testConstructorWithConfig
   * @expectedException \Exception
   * @expectedExceptionMessage Vocabulary id 'thisshouldnotbefound' not found in configuration 
   */
  public function testGetVocabularyByFalseId() {
    $model = new Model(); 
    $vocab = $model->getVocabulary('thisshouldnotbefound');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }

  /**
   * @covers Model::getVocabularyByGraph
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyByGraphUri() {
    $model = new Model(); 
    $vocab = $model->getVocabularyByGraph('http://www.skosmos.skos/test/');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }
  
  /**
   * @covers Model::getVocabularyByGraph
   * @depends testConstructorWithConfig
   * @expectedException \Exception
   * @expectedExceptionMessage no vocabulary found for graph http://no/address and endpoint http://localhost:3030/ds/sparql
   */
  public function testGetVocabularyByInvalidGraphUri() {
    $model = new Model(); 
    $vocab = $model->getVocabularyByGraph('http://no/address');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }
  
  /**
   * @covers Model::guessVocabularyFromURI
   * @depends testConstructorWithConfig
   */
  public function testGuessVocabularyFromURI() {
    $model = new Model();
    $vocab = $model->guessVocabularyFromURI('http://www.skosmos.skos/test/T21329');
    $this->assertInstanceOf('Vocabulary', $vocab);
    $this->assertEquals('test', $vocab->getId());
  }
  
  /**
   * @depends testConstructorWithConfig
   */
  public function testGuessVocabularyFromURIThatIsNotFound() {
    $model = new Model();
    $vocab = $model->guessVocabularyFromURI('http://doesnot/exist');
    $this->assertEquals(null, $vocab);
  }

  /**
   * @covers Model::getDefaultSparql
   * @depends testConstructorWithConfig
   */
  public function testGetDefaultSparql() {
    $model = new Model();
    $sparql = $model->getDefaultSparql();
    $this->assertInstanceOf('GenericSparql', $sparql);
  }
  
  /**
   * @covers Model::getSparqlImplementation
   * @depends testConstructorWithConfig
   */
  public function testGetSparqlImplementation() {
    $model = new Model();
    $sparql = $model->getSparqlImplementation('JenaText', 'http://api.dev.finto.fi/sparql', 'http://www.yso.fi/onto/test/');
    $this->assertInstanceOf('JenaTextSparql', $sparql);
  }
  
  /**
   * @covers Model::getBreadCrumbs
   * @covers Model::getCrumbs
   * @depends testConstructorWithConfig
   */
  public function testGetBreadCrumbs() {
    $model = new Model();
    $resource = new EasyRdf_Resource('http://www.yso.fi/onto/yso/p14606');
    $vocabstub = $this->getMock('Vocabulary', array('getConceptTransitiveBroaders'), array($model, $resource));
    $vocabstub->method('getConceptTransitiveBroaders')->willReturn(array ( 'http://www.yso.fi/onto/yso/p4762' => array ( 'label' => 'objects', ), 'http://www.yso.fi/onto/yso/p1674' => array ( 'label' => 'physical whole', 'direct' => array ( 0 => 'http://www.yso.fi/onto/yso/p4762', ), ), 'http://www.yso.fi/onto/yso/p14606' => array ( 'label' => 'layers', 'direct' => array ( 0 => 'http://www.yso.fi/onto/yso/p1674', ), ), ));
    $result = $model->getBreadCrumbs($vocabstub, 'en', 'http://www.yso.fi/onto/yso/p14606');
    foreach($result['breadcrumbs'][0] as $crumb)    
      $this->assertInstanceOf('Breadcrumb', $crumb);
  }
  
  /**
   * @covers Model::getBreadCrumbs
   * @covers Model::combineCrumbs
   * @covers Model::getCrumbs
   * @depends testConstructorWithConfig
   */
  public function testGetBreadCrumbsShortening() {
    $model = new Model();
    $resource = new EasyRdf_Resource('http://www.yso.fi/onto/yso/p14606');
    $vocabstub = $this->getMock('Vocabulary', array('getConceptTransitiveBroaders'), array($model, $resource));
    $vocabstub->method('getConceptTransitiveBroaders')->willReturn(array ( 'http://www.yso.fi/onto/yso/p4762' => array ( 'label' => 'objects', ), 'http://www.yso.fi/onto/yso/p13871' => array ( 'label' => 'thai language', 'direct' => array ( 0 => 'http://www.yso.fi/onto/yso/p10834', ), ), 'http://www.yso.fi/onto/yso/p556' => array ( 'label' => 'languages', 'direct' => array ( 0 => 'http://www.yso.fi/onto/yso/p2881', ), ), 'http://www.yso.fi/onto/yso/p8965' => array ( 'label' => 'Sino-Tibetan languages', 'direct' => array ( 0 => 'http://www.yso.fi/onto/yso/p556', ), ), 'http://www.yso.fi/onto/yso/p3358' => array ( 'label' => 'systems', 'direct' => array ( 0 => 'http://www.yso.fi/onto/yso/p4762', ), ), 'http://www.yso.fi/onto/yso/p10834' => array ( 'label' => 'Tai languages', 'direct' => array ( 0 => 'http://www.yso.fi/onto/yso/p8965', ), ), 'http://www.yso.fi/onto/yso/p2881' => array ( 'label' => 'cultural systems', 'direct' => array ( 0 => 'http://www.yso.fi/onto/yso/p3358', ), ), ) );
    $result = $model->getBreadCrumbs($vocabstub, 'en', 'http://www.yso.fi/onto/yso/p13871');
    $this->assertEquals(6, sizeof($result['breadcrumbs'][0]));
  }
  
  /**
   * @covers Model::searchConcepts
   * @depends testConstructorWithConfig
   */
  public function testSearchWithEmptyTerm() {
    $model = new Model();
    $result = $model->searchConcepts('', '', '', '');
    $this->assertEquals(array(), $result);
  }
  
  /**
   * @covers Model::searchConcepts
   * @depends testConstructorWithConfig
   */
  public function testSearchWithOnlyWildcard() {
    $model = new Model();
    $result = $model->searchConcepts('*','test','en','en');
    $this->assertEquals(array(), $result);
  }
  
  /**
   * @covers Model::searchConcepts
   * @depends testConstructorWithConfig
   */
  public function testSearchWithOnlyMultipleWildcards() {
    $model = new Model();
    $result = $model->searchConcepts('**','test','en','en');
    $this->assertEquals(array(), $result);
    $result = $model->searchConcepts('******','test','en','en');
    $this->assertEquals(array(), $result);
  }
  
  /**
   * @covers Model::searchConcepts
   * @depends testConstructorWithConfig
   * @expectedException \Exception
   * @expectedExceptionMessage Missing argument
   */
  public function testSearchWithNoParams() {
    $model = new Model();
    $result = $model->searchConcepts();
  }

  /**
   * @covers Model::getTypes
   * @depends testConstructorWithConfig
   */
  public function testGetTypesWithoutParams() {
    $model = new Model();
    $result = $model->getTypes();
    $this->assertEquals(array(
      'http://www.w3.org/2004/02/skos/core#Concept' => array('label' => "skos:Concept"),
      'http://www.skosmos.skos/test-meta/TestClass' => array(
        'superclass' => 'http://www.w3.org/2004/02/skos/core#Concept'
      ),
      'http://www.w3.org/2004/02/skos/core#Collection' => array('label' => "skos:Collection")
    ), $result);
  }

  /**
   * @covers Model::searchConcepts
   * @depends testConstructorWithConfig
   */
  public function testSearchConceptsWithOneVocabCaseInsensitivity() {
    $model = new Model();
    $result = $model->searchConcepts('bass', 'test', 'en', 'en');
    $this->assertEquals('http://www.skosmos.skos/test/ta116', $result[0]['uri']);
    $this->assertEquals('Bass', $result[0]['prefLabel']);
  }
  
  /**
   * @covers Model::searchConcepts
   * @depends testConstructorWithConfig
   */
  public function testSearchConceptsWithAllVocabsCaseInsensitivity() {
    $model = new Model();
    $result = $model->searchConcepts('bass', null, 'en', 'en');
    $this->assertEquals('http://www.skosmos.skos/test/ta116', $result[0]['uri']);
    $this->assertEquals('Bass', $result[0]['prefLabel']);
  }
  
  /**
   * @covers Model::searchConcepts
   * @depends testConstructorWithConfig
   */
  public function testSearchConceptsWithMultipleVocabsCaseInsensitivity() {
    $model = new Model();
    $result = $model->searchConcepts('bass', array('test', 'testdiff'), 'en', 'en');
    $this->assertEquals('http://www.skosmos.skos/test/ta116', $result[0]['uri']);
    $this->assertEquals('Bass', $result[0]['prefLabel']);
  }
  
  /**
   * @covers Model::searchConcepts
   * @depends testConstructorWithConfig
   * @expectedException \Exception
   * @expectedExceptionMessage Vocabulary id 'doesnotexist' not found in configuration.
   */
  public function testSearchConceptsWithNotExistingVocabID() {
    $model = new Model();
    $result = $model->searchConcepts('bass', array('doesnotexist', 'thisdoesnteither'), 'en', 'en');
  }


  /**
   * @covers Model::searchConcepts
   * @depends testConstructorWithConfig
   */
  public function testSearchConceptsWithMultipleBroaders() {
    $model = new Model();
    $result = $model->searchConcepts('multiple broaders', 'test', 'en', 'en', null, null, null, 0, 10, true, array('broader'));
    $this->assertEquals('http://www.skosmos.skos/test/ta123', $result[0]['uri']);
    $this->assertEquals('multiple broaders', $result[0]['prefLabel']);
    $this->assertCount(2, $result[0]['broader']); // two broader concepts
    $this->assertEquals('http://www.skosmos.skos/test/ta118', $result[0]['broader'][0]['uri']);
    $this->assertEquals('-"special" character \\example\\', $result[0]['broader'][0]['prefLabel']);
    $this->assertEquals('http://www.skosmos.skos/test/ta119', $result[0]['broader'][1]['uri']);
    $this->assertCount(2, $result[0]['type']); // two concept types
  }
  
  /**
   * @covers Model::searchConceptsAndInfo
   * @depends testConstructorWithConfig
   * @expectedException \Exception
   * @expectedExceptionMessage Vocabulary id 'doesnotexist' not found in configuration.
   */
  public function testSearchConceptsAndInfoWithNotExistingVocabID() {
    $model = new Model();
    $result = $model->searchConceptsAndInfo('bass', array('doesnotexist', 'thisdoesnteither'), 'en', 'en');
  }
  
  /**
   * @covers Model::searchConceptsAndInfo
   * @depends testConstructorWithConfig
   */
  public function testSearchConceptsAndInfoWithOneVocabCaseInsensitivity() {
    $model = new Model();
    $result = $model->searchConceptsAndInfo('bass', 'test', 'en', 'en');
    $this->assertEquals('http://www.skosmos.skos/test/ta116', $result['results'][0]->getUri());
    $this->assertEquals(1, $result['count']);
  }

  /**
   * @covers Model::getRDF
   * @depends testConstructorWithConfig
   */
  public function testGetRDFWithVocidAndURIasTurtle() {
    $model = new Model();
    $result = $model->getRDF('test', 'http://www.skosmos.skos/test/ta116', 'text/turtle');
    $resultGraph = new EasyRdf_Graph();
    $resultGraph->parse($result, "turtle");

    $expected = '@prefix test: <http://www.skosmos.skos/test/> .
@prefix skos: <http://www.w3.org/2004/02/skos/core#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix owl: <http://www.w3.org/2002/07/owl#> .

test:ta1
  skos:prefLabel "Fish"@en ;
  a skos:Concept, <http://www.skosmos.skos/test-meta/TestClass> ;
  skos:narrower test:ta116 .

test:conceptscheme
  rdfs:label "Test conceptscheme"@en ;
  a skos:ConceptScheme .

test:ta122 skos:broader test:ta116 .
test:ta116
  skos:prefLabel "Bass"@en ;
  skos:inScheme test:conceptscheme ;
  skos:broader test:ta1 ;
  a skos:Concept, <http://www.skosmos.skos/test-meta/TestClass> .

<http://www.skosmos.skos/test-meta/TestClass>
  rdfs:label "Test class"@en ;
  a owl:Class .

';
    $expectedGraph = new EasyRdf_Graph();
    $expectedGraph->parse($expected, "turtle");
    $this->assertTrue(EasyRdf_Isomorphic::isomorphic($resultGraph, $expectedGraph));
  }
  
  /**
   * @covers Model::getRDF
   * @depends testConstructorWithConfig
   */
  public function testGetRDFWithURIasTurtle() {
    $model = new Model();
    $result = $model->getRDF(null, 'http://www.skosmos.skos/test/ta116', 'text/turtle');
    $resultGraph = new EasyRdf_Graph();
    $resultGraph->parse($result, "turtle");
    $expected = '@prefix test: <http://www.skosmos.skos/test/> .
@prefix skos: <http://www.w3.org/2004/02/skos/core#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix owl: <http://www.w3.org/2002/07/owl#> .

test:ta1
  skos:prefLabel "Fish"@en ;
  a skos:Concept, <http://www.skosmos.skos/test-meta/TestClass> ;
  skos:narrower test:ta116 .

test:conceptscheme
  rdfs:label "Test conceptscheme"@en ;
  a skos:ConceptScheme .

test:ta122 skos:broader test:ta116 .
test:ta116
  skos:prefLabel "Bass"@en ;
  skos:inScheme test:conceptscheme ;
  skos:broader test:ta1 ;
  a skos:Concept, <http://www.skosmos.skos/test-meta/TestClass> .

<http://www.skosmos.skos/test-meta/TestClass>
  rdfs:label "Test class"@en ;
  a owl:Class .

';
    $expectedGraph = new EasyRdf_Graph();
    $expectedGraph->parse($expected, "turtle");
    $this->assertTrue(EasyRdf_Isomorphic::isomorphic($resultGraph, $expectedGraph));
  }

  /**
   * @covers Model::getRDF
   * @depends testConstructorWithConfig
   */
  public function testGetRDFWithVocidAndURIasJSON() {
    $model = new Model();
    $result = $model->getRDF('test', 'http://www.skosmos.skos/test/ta116', 'application/json');
    
    # check that we at least get something
    $this->assertNotEmpty($result);
    $this->markTestIncomplete('Further checking of the result would require a JSON-LD parser');

 /* This requires the JSON-LD parser, which is not available in EasyRdf 0.8.0
 
    $resultGraph = new EasyRdf_Graph();
    $resultGraph->parse($result, "jsonld");
    $expected = '[{"@id":"http://www.skosmos.skos/test-meta/TestClass","http://www.w3.org/2000/01/rdf-schema#label":[{"@value":"Test class","@language":"en"}],"@type":["http://www.w3.org/2002/07/owl#Class"]},{"@id":"http://www.skosmos.skos/test/conceptscheme","http://www.w3.org/2000/01/rdf-schema#label":[{"@value":"Test conceptscheme","@language":"en"}],"@type":["http://www.w3.org/2004/02/skos/core#ConceptScheme"]},{"@id":"http://www.skosmos.skos/test/ta1","http://www.w3.org/2004/02/skos/core#prefLabel":[{"@value":"Fish","@language":"en"}],"@type":["http://www.w3.org/2004/02/skos/core#Concept","http://www.skosmos.skos/test-meta/TestClass"],"http://www.w3.org/2004/02/skos/core#narrower":[{"@id":"http://www.skosmos.skos/test/ta116"}]},{"@id":"http://www.skosmos.skos/test/ta116","http://www.w3.org/2004/02/skos/core#prefLabel":[{"@value":"Bass","@language":"en"}],"http://www.w3.org/2004/02/skos/core#inScheme":[{"@id":"http://www.skosmos.skos/test/conceptscheme"}],"http://www.w3.org/2004/02/skos/core#broader":[{"@id":"http://www.skosmos.skos/test/ta1"}],"@type":["http://www.w3.org/2004/02/skos/core#Concept","http://www.skosmos.skos/test-meta/TestClass"]},{"@id":"http://www.skosmos.skos/test/ta122","http://www.w3.org/2004/02/skos/core#broader":[{"@id":"http://www.skosmos.skos/test/ta116"}]},{"@id":"http://www.w3.org/2002/07/owl#Class"},{"@id":"http://www.w3.org/2004/02/skos/core#Concept"},{"@id":"http://www.w3.org/2004/02/skos/core#ConceptScheme"}]';
    $expectedGraph = new EasyRdf_Graph();
    $expectedGraph->parse($expected, "jsonld");
    $this->assertTrue(EasyRdf_Isomorphic::isomorphic($resultGraph, $expectedGraph));
 */
  }

  /**
   * @covers Model::getRDF
   * @depends testConstructorWithConfig
   */
  public function testGetRDFWithVocidAndURIasRDFXML() {
    $model = new Model();
    $result = $model->getRDF('test', 'http://www.skosmos.skos/test/ta116', 'application/rdf+xml');

    # check that we at least get something
    $resultGraph = new EasyRdf_Graph();
    $resultGraph->parse($result, "rdfxml");
    $this->assertTrue($resultGraph->countTriples() > 0);

    $this->markTestIncomplete('Result is not what we expect due to EasyRdf issue https://github.com/njh/easyrdf/issues/209');

/*
#    $result = $model->getRDF('test', 'http://www.skosmos.skos/test/ta116', 'text/turtle');
#    var_dump($result);
#    $resultGraph->parse($result, "turtle");
#    var_dump($resultGraph->countTriples());
    $expected = '<?xml version="1.0" encoding="utf-8" ?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:skos="http://www.w3.org/2004/02/skos/core#"
         xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
         xmlns:owl="http://www.w3.org/2002/07/owl#">

  <skos:Concept rdf:about="http://www.skosmos.skos/test/ta1">
    <skos:prefLabel xml:lang="en">Fish</skos:prefLabel>
    <rdf:type rdf:resource="http://www.skosmos.skos/test-meta/TestClass"/>
    <skos:narrower rdf:resource="http://www.skosmos.skos/test/ta116"/>
  </skos:Concept>

  <skos:ConceptScheme rdf:about="http://www.skosmos.skos/test/conceptscheme">
    <rdfs:label xml:lang="en">Test conceptscheme</rdfs:label>
  </skos:ConceptScheme>

  <rdf:Description rdf:about="http://www.skosmos.skos/test/ta122">
    <skos:broader rdf:resource="http://www.skosmos.skos/test/ta116"/>
  </rdf:Description>

  <skos:Concept rdf:about="http://www.skosmos.skos/test/ta116">
    <skos:prefLabel xml:lang="en">Bass</skos:prefLabel>
    <skos:inScheme rdf:resource="http://www.skosmos.skos/test/conceptscheme"/>
    <skos:broader rdf:resource="http://www.skosmos.skos/test/ta1"/>
    <rdf:type rdf:resource="http://www.skosmos.skos/test-meta/TestClass"/>
  </skos:Concept>

  <owl:Class rdf:about="http://www.skosmos.skos/test-meta/TestClass">
    <rdfs:label xml:lang="en">Test class</rdfs:label>
  </owl:Class>

</rdf:RDF>
';
    var_dump($expected);
    $expectedGraph = new EasyRdf_Graph();
    $expectedGraph->parse($expected, "rdfxml");
    var_dump($expectedGraph->countTriples());
    $this->assertTrue(EasyRdf_Isomorphic::isomorphic($resultGraph, $expectedGraph));
*/
  }

}
