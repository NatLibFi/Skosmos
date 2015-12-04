<?php

class ModelTest extends PHPUnit_Framework_TestCase
{
  
  /**
   * @covers Model::__construct
   */
  public function testConstructorWithConfig()
  {
    new Model(new GlobalConfig('/../tests/testconfig.inc'));
  }
  
  /**
   * @covers Model::getVersion
   * @depends testConstructorWithConfig
   */

  public function testGetVersion() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $version = $model->getVersion();
    $this->assertNotEmpty($version);
  }
  
  /**
   * @covers Model::getVocabularyList
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyList() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
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
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc')); 
    $categories = $model->getVocabularyCategories();
    foreach($categories as $category)
      $this->assertInstanceOf('VocabularyCategory', $category);
  }
  
  /**
   * @covers Model::getVocabulariesInCategory
   * @depends testConstructorWithConfig
   */
  public function testGetVocabulariesInCategory() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc')); 
    $category = $model->getVocabulariesInCategory('cat_science');
    foreach($category as $vocab)
      $this->assertInstanceOf('Vocabulary', $vocab);
  }
  
  /**
   * @covers Model::getVocabulary
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyById() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc')); 
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
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $vocab = $model->getVocabulary('thisshouldnotbefound');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }

  /**
   * @covers Model::getVocabularyByGraph
   * @depends testConstructorWithConfig
   */
  public function testGetVocabularyByGraphUri() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
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
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $vocab = $model->getVocabularyByGraph('http://no/address');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }
  
  /**
   * @covers Model::guessVocabularyFromURI
   * @depends testConstructorWithConfig
   */
  public function testGuessVocabularyFromURI() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $vocab = $model->guessVocabularyFromURI('http://www.skosmos.skos/test/T21329');
    $this->assertInstanceOf('Vocabulary', $vocab);
    $this->assertEquals('test', $vocab->getId());
  }
  
  /**
   * @depends testConstructorWithConfig
   */
  public function testGuessVocabularyFromURIThatIsNotFound() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $vocab = $model->guessVocabularyFromURI('http://doesnot/exist');
    $this->assertEquals(null, $vocab);
  }

  /**
   * @covers Model::getDefaultSparql
   * @depends testConstructorWithConfig
   */
  public function testGetDefaultSparql() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $sparql = $model->getDefaultSparql();
    $this->assertInstanceOf('GenericSparql', $sparql);
  }
  
  /**
   * @covers Model::getSparqlImplementation
   * @depends testConstructorWithConfig
   */
  public function testGetSparqlImplementation() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $sparql = $model->getSparqlImplementation('JenaText', 'http://api.dev.finto.fi/sparql', 'http://www.yso.fi/onto/test/');
    $this->assertInstanceOf('JenaTextSparql', $sparql);
  }
  
  /**
   * @covers Model::searchConcepts
   * @depends testConstructorWithConfig
   */
  public function testSearchWithEmptyTerm() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $result = $model->searchConcepts('', '', '', '');
    $this->assertEquals(array(), $result);
  }
  
  /**
   * @covers Model::searchConcepts
   * @depends testConstructorWithConfig
   */
  public function testSearchWithOnlyWildcard() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $result = $model->searchConcepts('*','test','en','en');
    $this->assertEquals(array(), $result);
  }
  
  /**
   * @covers Model::searchConcepts
   * @depends testConstructorWithConfig
   */
  public function testSearchWithOnlyMultipleWildcards() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
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
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $result = $model->searchConcepts();
  }

  /**
   * @covers Model::getTypes
   * @depends testConstructorWithConfig
   */
  public function testGetTypesWithoutParams() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
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
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $result = $model->searchConcepts('bass', 'test', 'en', 'en');
    $this->assertEquals('http://www.skosmos.skos/test/ta116', $result[0]['uri']);
    $this->assertEquals('Bass', $result[0]['prefLabel']);
  }
  
  /**
   * @covers Model::searchConcepts
   * @depends testConstructorWithConfig
   */
  public function testSearchConceptsWithOneVocabSearchLangOtherThanLabellang() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $result = $model->searchConcepts('karppi', 'test', 'en', 'fi');
    $this->assertEquals('http://www.skosmos.skos/test/ta112', $result[0]['uri']);
    $this->assertEquals('Carp', $result[0]['prefLabel']);
  }

  
  /**
   * @covers Model::searchConcepts
   * @depends testConstructorWithConfig
   */
  public function testSearchConceptsWithAllVocabsCaseInsensitivity() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $result = $model->searchConcepts('bass', null, 'en', 'en');
    $this->assertEquals('http://www.skosmos.skos/test/ta116', $result[0]['uri']);
    $this->assertEquals('Bass', $result[0]['prefLabel']);
  }
  
  /**
   * @covers Model::searchConcepts
   * @depends testConstructorWithConfig
   */
  public function testSearchConceptsWithMultipleVocabsCaseInsensitivity() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
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
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $result = $model->searchConcepts('bass', array('doesnotexist', 'thisdoesnteither'), 'en', 'en');
  }


  /**
   * @covers Model::searchConcepts
   * @depends testConstructorWithConfig
   */
  public function testSearchConceptsWithMultipleBroaders() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
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
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $result = $model->searchConceptsAndInfo('bass', array('doesnotexist', 'thisdoesnteither'), 'en', 'en');
  }
  
  /**
   * @covers Model::searchConceptsAndInfo
   * @depends testConstructorWithConfig
   */
  public function testSearchConceptsAndInfoWithOneVocabCaseInsensitivity() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $result = $model->searchConceptsAndInfo('bass', 'test', 'en', 'en');
    $this->assertEquals('http://www.skosmos.skos/test/ta116', $result['results'][0]->getUri());
    $this->assertEquals(1, $result['count']);
  }

  /**
   * @covers Model::getRDF
   * @depends testConstructorWithConfig
   */
  public function testGetRDFWithVocidAndURIasTurtle() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $result = $model->getRDF('test', 'http://www.skosmos.skos/test/ta116', 'text/turtle');
    $resultGraph = new EasyRdf_Graph();
    $resultGraph->parse($result, "turtle");

    $expected = '@prefix skos: <http://www.w3.org/2004/02/skos/core#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix test: <http://www.skosmos.skos/test/> .
@prefix owl: <http://www.w3.org/2002/07/owl#> .

skos:broader rdfs:label "has broader"@en .
test:ta1
  skos:prefLabel "Fish"@en ;
  a <http://www.skosmos.skos/test-meta/TestClass>, skos:Concept ;
  skos:narrower test:ta116 .

test:conceptscheme
  rdfs:label "Test conceptscheme"@en ;
  a skos:ConceptScheme .

skos:prefLabel rdfs:label "preferred label"@en .
test:ta122 skos:broader test:ta116 .
test:ta116
  skos:inScheme test:conceptscheme ;
  skos:broader test:ta1 ;
  skos:prefLabel "Bass"@en ;
  a <http://www.skosmos.skos/test-meta/TestClass>, skos:Concept .

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
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $result = $model->getRDF(null, 'http://www.skosmos.skos/test/ta116', 'text/turtle');
    $resultGraph = new EasyRdf_Graph();
    $resultGraph->parse($result, "turtle");
    $expected = '@prefix skos: <http://www.w3.org/2004/02/skos/core#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix test: <http://www.skosmos.skos/test/> .
@prefix owl: <http://www.w3.org/2002/07/owl#> .

skos:broader rdfs:label "has broader"@en .
test:ta1
  skos:prefLabel "Fish"@en ;
  a <http://www.skosmos.skos/test-meta/TestClass>, skos:Concept ;
  skos:narrower test:ta116 .

test:conceptscheme
  rdfs:label "Test conceptscheme"@en ;
  a skos:ConceptScheme .

skos:prefLabel rdfs:label "preferred label"@en .
test:ta122 skos:broader test:ta116 .
test:ta116
  skos:inScheme test:conceptscheme ;
  skos:broader test:ta1 ;
  skos:prefLabel "Bass"@en ;
  a <http://www.skosmos.skos/test-meta/TestClass>, skos:Concept .

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
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $result = $model->getRDF('test', 'http://www.skosmos.skos/test/ta116', 'application/json');
    
    $resultGraph = new EasyRdf_Graph();
    $resultGraph->parse($result, "jsonld");
    $expected = '[{"@id":"http://www.skosmos.skos/test-meta/TestClass","http://www.w3.org/2000/01/rdf-schema#label":[{"@value":"Test class","@language":"en"}],"@type":["http://www.w3.org/2002/07/owl#Class"]},{"@id":"http://www.skosmos.skos/test/conceptscheme","http://www.w3.org/2000/01/rdf-schema#label":[{"@value":"Test conceptscheme","@language":"en"}],"@type":["http://www.w3.org/2004/02/skos/core#ConceptScheme"]},{"@id":"http://www.skosmos.skos/test/ta1","http://www.w3.org/2004/02/skos/core#prefLabel":[{"@value":"Fish","@language":"en"}],"@type":["http://www.skosmos.skos/test-meta/TestClass","http://www.w3.org/2004/02/skos/core#Concept"],"http://www.w3.org/2004/02/skos/core#narrower":[{"@id":"http://www.skosmos.skos/test/ta116"}]},{"@id":"http://www.skosmos.skos/test/ta116","http://www.w3.org/2004/02/skos/core#inScheme":[{"@id":"http://www.skosmos.skos/test/conceptscheme"}],"http://www.w3.org/2004/02/skos/core#broader":[{"@id":"http://www.skosmos.skos/test/ta1"}],"http://www.w3.org/2004/02/skos/core#prefLabel":[{"@value":"Bass","@language":"en"}],"@type":["http://www.skosmos.skos/test-meta/TestClass","http://www.w3.org/2004/02/skos/core#Concept"]},{"@id":"http://www.skosmos.skos/test/ta122","http://www.w3.org/2004/02/skos/core#broader":[{"@id":"http://www.skosmos.skos/test/ta116"}]},{"@id":"http://www.w3.org/2002/07/owl#Class"},{"@id":"http://www.w3.org/2004/02/skos/core#Concept"},{"@id":"http://www.w3.org/2004/02/skos/core#ConceptScheme"},{"@id":"http://www.w3.org/2004/02/skos/core#broader","http://www.w3.org/2000/01/rdf-schema#label":[{"@value":"has broader","@language":"en"}]},{"@id":"http://www.w3.org/2004/02/skos/core#prefLabel","http://www.w3.org/2000/01/rdf-schema#label":[{"@value":"preferred label","@language":"en"}]}]';
    $expectedGraph = new EasyRdf_Graph();
    $expectedGraph->parse($expected, "jsonld");
    $this->assertTrue(EasyRdf_Isomorphic::isomorphic($resultGraph, $expectedGraph));
  }

  /**
   * @covers Model::getRDF
   * @depends testConstructorWithConfig
   */
  public function testGetRDFWithVocidAndURIasRDFXML() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $result = $model->getRDF('test', 'http://www.skosmos.skos/test/ta116', 'application/rdf+xml');
    $resultGraph = new EasyRdf_Graph();
    $resultGraph->parse($result, "rdfxml");
    $expected = '<?xml version="1.0" encoding="utf-8" ?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
         xmlns:skos="http://www.w3.org/2004/02/skos/core#"
         xmlns:owl="http://www.w3.org/2002/07/owl#">

  <rdf:Description rdf:about="http://www.w3.org/2004/02/skos/core#broader">
    <rdfs:label xml:lang="en">has broader</rdfs:label>
  </rdf:Description>

  <rdf:Description rdf:about="http://www.skosmos.skos/test/ta1">
    <skos:prefLabel xml:lang="en">Fish</skos:prefLabel>
    <rdf:type rdf:resource="http://www.skosmos.skos/test-meta/TestClass"/>
    <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
    <skos:narrower rdf:resource="http://www.skosmos.skos/test/ta116"/>
  </rdf:Description>

  <skos:ConceptScheme rdf:about="http://www.skosmos.skos/test/conceptscheme">
    <rdfs:label xml:lang="en">Test conceptscheme</rdfs:label>
  </skos:ConceptScheme>

  <rdf:Description rdf:about="http://www.w3.org/2004/02/skos/core#prefLabel">
    <rdfs:label xml:lang="en">preferred label</rdfs:label>
  </rdf:Description>

  <rdf:Description rdf:about="http://www.skosmos.skos/test/ta122">
    <skos:broader rdf:resource="http://www.skosmos.skos/test/ta116"/>
  </rdf:Description>

  <rdf:Description rdf:about="http://www.skosmos.skos/test/ta116">
    <skos:inScheme rdf:resource="http://www.skosmos.skos/test/conceptscheme"/>
    <skos:broader rdf:resource="http://www.skosmos.skos/test/ta1"/>
    <skos:prefLabel xml:lang="en">Bass</skos:prefLabel>
    <rdf:type rdf:resource="http://www.skosmos.skos/test-meta/TestClass"/>
    <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
  </rdf:Description>

  <owl:Class rdf:about="http://www.skosmos.skos/test-meta/TestClass">
    <rdfs:label xml:lang="en">Test class</rdfs:label>
  </owl:Class>

</rdf:RDF>';
    $expectedGraph = new EasyRdf_Graph();
    $expectedGraph->parse($expected, "rdfxml");
    $this->assertTrue(EasyRdf_Isomorphic::isomorphic($resultGraph, $expectedGraph));
  }
  
  /**
   * @covers Model::getLanguages
   * @depends testConstructorWithConfig
   */
  public function testGetLanguages() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc')); 
    $languages = $model->getLanguages('en');
    $expected = array('English' => 'en');
    $this->assertEquals($expected, $languages);
  }
  
  /**
   * @covers Model::getResourceFromUri
   * @covers Model::getResourceLabel
   * @covers Model::fetchResourceFromUri
   * @depends testConstructorWithConfig
   */
  public function testGetResourceFromUri() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.inc')); 
    $resource = $model->getResourceFromUri('http://www.yso.fi/onto/yso/p19378');
    $this->assertInstanceOf('EasyRdf_Resource', $resource);
    $this->assertEquals('http://www.yso.fi/onto/yso/p19378', $resource->getURI());
  }

}
