<?php

class ModelTest extends PHPUnit\Framework\TestCase
{
  private $params;
  private $model;

  protected function setUp() : void
  {
    putenv("LANGUAGE=en_GB.utf8");
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    $this->model = new Model(new GlobalConfig('/../tests/testconfig.ttl'));
    $this->params = $this->getMockBuilder('ConceptSearchParameters')->disableOriginalConstructor()->getMock();
    $this->params->method('getVocabIds')->will($this->returnValue(array('test')));
    $this->params->method('getVocabs')->will($this->returnValue(array($this->model->getVocabulary('test'))));
  }

  protected function tearDown() : void
  {
    $this->params = null;
  }

  /**
   * @covers Model::__construct
   */
  public function testConstructorWithConfig()
  {
    $model = new Model(new GlobalConfig('/../tests/testconfig.ttl'));
    $this->assertNotNull($model);
  }

  /**
   * @covers Model::getVersion
   */
  public function testGetVersion() {
    $version = $this->model->getVersion();
    // make sure that the returned version (which comes from composer.json)
    // matches the version from git tags
    $git_tag = rtrim(shell_exec('git describe --tags --always'));
    $this->assertStringStartsWith("v" . $version, $git_tag,
      "Composer version '$version' doesn't match git tag '$git_tag'.\n" .
      "Please run 'composer update' to update the Composer version.");
  }

  /**
   * @covers Model::getVocabularyList
   */
  public function testGetVocabularyList() {
    $categories = $this->model->getVocabularyList();
    foreach($categories as $category)
      foreach($category as $vocab)
        $this->assertInstanceOf('Vocabulary', $vocab);
  }

  /**
   * @covers Model::getVocabularyCategories
   */
  public function testGetVocabularyCategories() {
    $categories = $this->model->getVocabularyCategories();
    foreach($categories as $category)
      $this->assertInstanceOf('VocabularyCategory', $category);
  }

  /**
   * @covers Model::getVocabulariesInCategory
   */
  public function testGetVocabulariesInCategory() {
    $category = $this->model->getVocabulariesInCategory(new EasyRdf\Resource('http://base/#cat_science'));
    foreach($category as $vocab)
      $this->assertInstanceOf('Vocabulary', $vocab);
  }

  /**
   * @covers Model::getVocabulary
   */
  public function testGetVocabularyById() {
    $vocab = $this->model->getVocabulary('test');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }

  /**
   * @covers Model::getVocabulary
   */
  public function testGetVocabularyByFalseId() {
    $this->expectException(ValueError::class);
    $this->expectExceptionMessage("Vocabulary id 'thisshouldnotbefound' not found in configuration");
    $vocab = $this->model->getVocabulary('thisshouldnotbefound');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }

  /**
   * @covers Model::getVocabularyByGraph
   */
  public function testGetVocabularyByGraphUri() {
    $vocab = $this->model->getVocabularyByGraph('http://www.skosmos.skos/test/');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }

  /**
   * @covers Model::getVocabularyByGraph
   */
  public function testGetVocabularyByInvalidGraphUri() {
    $this->expectException(ValueError::class);
    $this->expectExceptionMessage("no vocabulary found for graph http://no/address and endpoint http://localhost:13030/skosmos-test/sparql");
    $vocab = $this->model->getVocabularyByGraph('http://no/address');
    $this->assertInstanceOf('Vocabulary', $vocab);
  }

  /**
   * @covers Model::guessVocabularyFromURI
   */
  public function testGuessVocabularyFromURI() {
    $vocab = $this->model->guessVocabularyFromURI('http://www.skosmos.skos/test/T21329');
    $this->assertInstanceOf('Vocabulary', $vocab);
    $this->assertEquals('test', $vocab->getId());
  }

  /**
   */
  public function testGuessVocabularyFromURIThatIsNotFound() {
    $vocab = $this->model->guessVocabularyFromURI('http://doesnot/exist');
    $this->assertEquals(null, $vocab);
  }

  /**
   * @covers Model::getDefaultSparql
   */
  public function testGetDefaultSparql() {
    $sparql = $this->model->getDefaultSparql();
    $this->assertInstanceOf('GenericSparql', $sparql);
  }

  /**
   * @covers Model::getSparqlImplementation
   */
  public function testGetSparqlImplementation() {
    $sparql = $this->model->getSparqlImplementation('JenaText', 'http://api.dev.finto.fi/sparql', 'http://www.yso.fi/onto/test/');
    $this->assertInstanceOf('JenaTextSparql', $sparql);
  }

  /**
   * @covers Model::searchConcepts
   */
  public function testSearchWithEmptyTerm() {
    $this->params->method('getSearchTerm')->will($this->returnValue(''));
    $result = $this->model->searchConcepts($this->params);
    $this->assertEquals(array(), $result);
  }

  /**
   * @covers Model::searchConcepts
   */
  public function testSearchWithOnlyWildcard() {
    $this->params->method('getSearchTerm')->will($this->returnValue('*'));
    $result = $this->model->searchConcepts($this->params);
    $this->assertEquals(array(), $result);
  }

  /**
   * @covers Model::searchConcepts
   */
  public function testSearchWithOnlyMultipleWildcards() {
    $this->params->method('getSearchTerm')->will($this->returnValue('**'));
    $result = $this->model->searchConcepts($this->params);
    $this->assertEquals(array(), $result);
    $this->params->method('getSearchTerm')->will($this->returnValue('******'));
    $this->assertEquals(array(), $result);
  }

  /**
   * @covers Model::searchConcepts
   */
  public function testSearchWithNoParams() {
    if (PHP_VERSION_ID >= 70100) {
      $this->expectException(ArgumentCountError::class);
    } else {
      $this->expectException(PHPUnit\Framework\Error\Error::class);
    }
    $result = $this->model->searchConcepts();
  }

  /**
   * @covers Model::getTypes
   */
  public function testGetTypesWithoutParams() {
    $result = $this->model->getTypes();
    $this->assertEquals(array('http://www.w3.org/2004/02/skos/core#Concept', 'http://www.w3.org/2004/02/skos/core#Collection', 'http://purl.org/iso25964/skos-thes#ConceptGroup', 'http://purl.org/iso25964/skos-thes#ThesaurusArray', 'http://www.skosmos.skos/test-meta/TestClass'), array_keys($result));
  }

  /**
   * @covers Model::searchConcepts
   */
  public function testSearchConceptsWithOneVocabCaseInsensitivity() {
    $this->params->method('getSearchTerm')->will($this->returnValue('bass'));
    $this->params->method('getSearchLang')->will($this->returnValue('en'));
    $this->params->method('getLang')->will($this->returnValue('en'));
    $result = $this->model->searchConcepts($this->params);
    $this->assertEquals('http://www.skosmos.skos/test/ta116', $result[0]['uri']);
    $this->assertEquals('Bass', $result[0]['prefLabel']);
  }

  /**
   * @covers Model::searchConcepts
   */
  public function testSearchConceptsWithOneVocabSearchLangOtherThanLabellang() {
    $this->params->method('getSearchTerm')->will($this->returnValue('karppi'));
    $this->params->method('getSearchLang')->will($this->returnValue('fi'));
    $this->params->method('getLang')->will($this->returnValue('en'));
    $result = $this->model->searchConcepts($this->params);
    $this->assertEquals('http://www.skosmos.skos/test/ta112', $result[0]['uri']);
    $this->assertEquals('Carp', $result[0]['prefLabel']);
  }

  /**
   * @covers Model::searchConcepts
   */
  public function testSearchConceptsWithAllVocabsCaseInsensitivity() {
    $this->params->method('getSearchTerm')->will($this->returnValue('bass'));
    $result = $this->model->searchConcepts($this->params);
    $this->assertEquals('http://www.skosmos.skos/test/ta116', $result[0]['uri']);
    $this->assertEquals('Bass', $result[0]['prefLabel']);
  }

  /**
   * @covers Model::searchConcepts
   */
  public function testSearchConceptsWithMultipleVocabsCaseInsensitivity() {
    $this->params->method('getSearchTerm')->will($this->returnValue('bass'));
    $this->params->method('getVocabIds')->will($this->returnValue(array('test', 'testdiff')));
    $result = $this->model->searchConcepts($this->params);
    $this->assertEquals('http://www.skosmos.skos/test/ta116', $result[0]['uri']);
    $this->assertEquals('Bass', $result[0]['prefLabel']);
  }

  /**
   * @covers Model::searchConcepts
   */
  public function testSearchConceptsWithMultipleBroaders() {
    $this->params->method('getSearchTerm')->will($this->returnValue('multiple broaders'));
    $this->params->method('getSearchLang')->will($this->returnValue('en'));
    $this->params->method('getLang')->will($this->returnValue('en'));
    $this->params->method('getAdditionalFields')->will($this->returnValue(array('broader')));
    $result = $this->model->searchConcepts($this->params);
    $this->assertEquals('http://www.skosmos.skos/test/ta123', $result[0]['uri']);
    $this->assertEquals('multiple broaders', $result[0]['prefLabel']);

    // sort by URI to ensure their relative order
    usort($result[0]['skos:broader'], function($a, $b) {
        return strnatcasecmp($a['uri'], $b['uri']);
    });

    $this->assertCount(2, $result[0]['skos:broader']); // two broader concepts
    $this->assertEquals('http://www.skosmos.skos/test/ta118', $result[0]['skos:broader'][0]['uri']);
    $this->assertEquals('-"special" character \\example\\', $result[0]['skos:broader'][0]['prefLabel']);
    $this->assertEquals('http://www.skosmos.skos/test/ta119', $result[0]['skos:broader'][1]['uri']);
    $this->assertCount(2, $result[0]['type']); // two concept types
  }

  /**
   * @covers Model::searchConcepts
   */
  public function testSearchConceptsUnique() {
    $params = $this->getMockBuilder('ConceptSearchParameters')->disableOriginalConstructor()->getMock();
    $params->method('getSearchTerm')->will($this->returnValue('*identical*'));
    $params->method('getVocabIds')->will($this->returnValue('duplicates'));
    $params->method('getVocabs')->will($this->returnValue(array($this->model->getVocabulary('duplicates'))));
    $result = $this->model->searchConcepts($params);
    $this->assertCount(4, $result);
    $params->method('getUnique')->will($this->returnValue(true));
    $result = $this->model->searchConcepts($params);
    $this->assertCount(3, $result);
  }

  /**
   * @covers Model::searchConcepts
   */
  public function testSearchConceptsIncludingDeprecated() {
      $params = $this->getMockBuilder('ConceptSearchParameters')->disableOriginalConstructor()->getMock();
      $params->method('getSearchTerm')->will($this->returnValue('Tuna'));
      $params->method('getVocabIds')->will($this->returnValue('showDeprecated'));
      $params->method('getVocabs')->will($this->returnValue(array($this->model->getVocabulary('showDeprecated'))));
      $result = $this->model->searchConcepts($params);
      $this->assertCount(1, $result);
      $this->assertEquals('http://www.skosmos.skos/groups/ta111', $result[0]['uri']);
  }

  /**
   * @covers Model::searchConcepts
   */
  public function testSearchConceptsWithOneVocabLanguageSubtag() {
    $params = $this->getMockBuilder('ConceptSearchParameters')->disableOriginalConstructor()->getMock();
    $params->method('getSearchTerm')->will($this->returnValue('neighbour'));
    $params->method('getSearchLang')->will($this->returnValue('en'));
    $params->method('getLang')->will($this->returnValue('en'));
    $params->method('getVocabs')->will($this->returnValue(array($this->model->getVocabulary('subtag'))));
    $result = $this->model->searchConcepts($params);
    $this->assertCount(1, $result);
    $this->assertEquals('http://www.skosmos.skos/subtag/p1', $result[0]['uri']);
    $this->assertEquals('Neighbour', $result[0]['prefLabel']);
  }

  /**
   * @covers Model::searchConceptsAndInfo
   */
  public function testSearchConceptsAndInfoWithOneVocabCaseInsensitivity() {
    $this->params->method('getSearchTerm')->will($this->returnValue('bass'));
    $result = $this->model->searchConceptsAndInfo($this->params);
    $this->assertEquals('http://www.skosmos.skos/test/ta116', $result['results'][0]->getUri());
    $this->assertEquals(1, $result['count']);
  }

  /**
   * Test for issue #387: make sure namespaces defined in config.ttl are used for RDF export
   * @covers Model::getRDF
   */

  public function testGetRdfCustomPrefix() {
    $result = $this->model->getRDF('prefix', 'http://www.skosmos.skos/prefix/p1', 'text/turtle');
    $this->assertStringContainsString("@prefix my: <http://example.com/myns#> .", $result);
  }

  /**
   * @covers Model::getRDF
   */
  public function testGetRDFWithVocidAndURIasTurtle() {
    $result = $this->model->getRDF('test', 'http://www.skosmos.skos/test/ta116', 'text/turtle');
    $resultGraph = new EasyRdf\Graph();
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
    $expectedGraph = new EasyRdf\Graph();
    $expectedGraph->parse($expected, "turtle");
    $this->assertTrue(EasyRdf\Isomorphic::isomorphic($resultGraph, $expectedGraph));
  }

  /**
   * @covers Model::getRDF
   */
  public function testGetRDFWithURIasTurtle() {
    $result = $this->model->getRDF(null, 'http://www.skosmos.skos/test/ta116', 'text/turtle');
    $resultGraph = new EasyRdf\Graph();
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

    $expectedGraph = new EasyRdf\Graph();
    $expectedGraph->parse($expected, "turtle");
    $this->assertTrue(EasyRdf\Isomorphic::isomorphic($resultGraph, $expectedGraph));
  }

  /**
   * @covers Model::getRDF
   */
  public function testGetRDFWithVocidAndURIasJSON() {
    $result = $this->model->getRDF('test', 'http://www.skosmos.skos/test/ta116', 'application/json');

    $resultGraph = new EasyRdf\Graph();
    $resultGraph->parse($result, "jsonld");
    $expected = '[{"@id":"http://www.skosmos.skos/test-meta/TestClass","http://www.w3.org/2000/01/rdf-schema#label":[{"@value":"Test class","@language":"en"}],"@type":["http://www.w3.org/2002/07/owl#Class"]},{"@id":"http://www.skosmos.skos/test/conceptscheme","http://www.w3.org/2000/01/rdf-schema#label":[{"@value":"Test conceptscheme","@language":"en"}],"@type":["http://www.w3.org/2004/02/skos/core#ConceptScheme"]},{"@id":"http://www.skosmos.skos/test/ta1","http://www.w3.org/2004/02/skos/core#prefLabel":[{"@value":"Fish","@language":"en"}],"@type":["http://www.skosmos.skos/test-meta/TestClass","http://www.w3.org/2004/02/skos/core#Concept"],"http://www.w3.org/2004/02/skos/core#narrower":[{"@id":"http://www.skosmos.skos/test/ta116"}]},{"@id":"http://www.skosmos.skos/test/ta116","http://www.w3.org/2004/02/skos/core#inScheme":[{"@id":"http://www.skosmos.skos/test/conceptscheme"}],"http://www.w3.org/2004/02/skos/core#broader":[{"@id":"http://www.skosmos.skos/test/ta1"}],"http://www.w3.org/2004/02/skos/core#prefLabel":[{"@value":"Bass","@language":"en"}],"@type":["http://www.skosmos.skos/test-meta/TestClass","http://www.w3.org/2004/02/skos/core#Concept"]},{"@id":"http://www.skosmos.skos/test/ta122","http://www.w3.org/2004/02/skos/core#broader":[{"@id":"http://www.skosmos.skos/test/ta116"}]},{"@id":"http://www.w3.org/2002/07/owl#Class"},{"@id":"http://www.w3.org/2004/02/skos/core#Concept"},{"@id":"http://www.w3.org/2004/02/skos/core#ConceptScheme"},{"@id":"http://www.w3.org/2004/02/skos/core#broader","http://www.w3.org/2000/01/rdf-schema#label":[{"@value":"has broader","@language":"en"}]},{"@id":"http://www.w3.org/2004/02/skos/core#prefLabel","http://www.w3.org/2000/01/rdf-schema#label":[{"@value":"preferred label","@language":"en"}]}]';
    $expectedGraph = new EasyRdf\Graph();
    $expectedGraph->parse($expected, "jsonld");
    $this->assertTrue(EasyRdf\Isomorphic::isomorphic($resultGraph, $expectedGraph));
  }

  /**
   * @covers Model::getRDF
   */
  public function testGetRDFWithVocidAndURIasRDFXML() {
    $result = $this->model->getRDF('test', 'http://www.skosmos.skos/test/ta116', 'application/rdf+xml');
    $resultGraph = new EasyRdf\Graph();
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
    $expectedGraph = new EasyRdf\Graph();
    $expectedGraph->parse($expected, "rdfxml");
    $this->assertTrue(EasyRdf\Isomorphic::isomorphic($resultGraph, $expectedGraph));
  }

  /**
   * @covers Model::getRDF
   * @depends testConstructorWithConfig
   */
  public function testGetRDFShouldIncludeLists() {
    $result = $this->model->getRDF('test', 'http://www.skosmos.skos/test/ta124', 'text/turtle');
    $resultGraph = new EasyRdf\Graph();
    $resultGraph->parse($result, "turtle");

    $expected = '@prefix test: <http://www.skosmos.skos/test/> .
@prefix skos: <http://www.w3.org/2004/02/skos/core#> .
@prefix mads: <http://www.loc.gov/mads/rdf/v1#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .

skos:prefLabel rdfs:label "preferred label"@en .

test:ta124
  a mads:ComplexSubject, skos:Concept ;
  skos:prefLabel "Vadefugler : Europa"@nb ;
  mads:componentList ( test:ta125 test:ta126 ) .

test:ta125
  a mads:Topic, skos:Concept ;
  skos:prefLabel "Vadefugler"@nb .

test:ta126
  a mads:Geographic, skos:Concept ;
  skos:prefLabel "Europa"@nb .

';

    $expectedGraph = new EasyRdf\Graph();
    $expectedGraph->parse($expected, "turtle");
    $this->assertTrue(EasyRdf\Isomorphic::isomorphic($resultGraph, $expectedGraph));
  }

  /**
   * @covers Model::getRDF
   * @depends testConstructorWithConfig
   * Issue: https://github.com/NatLibFi/Skosmos/pull/419
   */
  public function testGetRDFShouldNotIncludeExtraBlankNodesFromLists() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.ttl'));
    $result = $model->getRDF('test', 'http://www.skosmos.skos/test/ta125', 'text/turtle');
    $resultGraph = new EasyRdf\Graph();
    $resultGraph->parse($result, "turtle");

    $expected = '@prefix test: <http://www.skosmos.skos/test/> .
@prefix skos: <http://www.w3.org/2004/02/skos/core#> .
@prefix mads: <http://www.loc.gov/mads/rdf/v1#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .

skos:prefLabel rdfs:label "preferred label"@en .

test:ta125
  a mads:Topic, skos:Concept ;
  skos:prefLabel "Vadefugler"@nb .
';

    $expectedGraph = new EasyRdf\Graph();
    $expectedGraph->parse($expected, "turtle");
    $this->assertTrue(EasyRdf\Isomorphic::isomorphic($resultGraph, $expectedGraph));
  }

  /**
   * @covers Model::getLanguages
   */
  public function testGetLanguages() {
    $languages = $this->model->getLanguages('en');
    $expected = array('English' => 'en');
    $this->assertEquals($expected, $languages);
  }

  /**
   * @covers Model::getResourceFromUri
   */
  public function testGetResourceFromUri() {
    $resource = $this->model->getResourceFromUri('http://www.yso.fi/onto/yso/p19378');
    $this->assertInstanceOf('EasyRdf\Resource', $resource);
    $this->assertEquals('http://www.yso.fi/onto/yso/p19378', $resource->getURI());
  }

  /**
   * @covers Model::getResourceLabel
   */
  public function testGetResourceLabelAcceptAnyLanguageWhenDesiredNotFound() {
    $mockres = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
    $labelmap = array(
      array('en', null),
      array(null, 'test value')
    );
    $mockres->method('label')->will($this->returnValueMap($labelmap));
    $this->assertEquals('test value', $this->model->getResourceLabel($mockres, 'en'));
  }

  /**
   * @covers Model::getResourceLabel
   */
  public function testGetResourceLabelCorrectLanguage() {
    $mockres = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
    $labelmap = array(
      array('en', 'test value'),
      array('fi', 'testiarvo')
    );
    $mockres->method('label')->will($this->returnValueMap($labelmap));
    $this->assertEquals('test value', $this->model->getResourceLabel($mockres, 'en'));
    $this->assertEquals('testiarvo', $this->model->getResourceLabel($mockres, 'fi'));
  }

}
