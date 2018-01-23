<?php

class GenericSparqlTest extends PHPUnit\Framework\TestCase
{
  private $model;
  private $graph;
  private $sparql;
  private $vocab;
  private $params;

  protected function setUp() {
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    $this->model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $this->vocab = $this->model->getVocabulary('test');
    $this->graph = $this->vocab->getGraph();
    $this->params = $this->getMockBuilder('ConceptSearchParameters')->disableOriginalConstructor()->getMock();
    $this->sparql = new GenericSparql('http://localhost:3030/ds/sparql', $this->graph, $this->model);
  }

  /**
   * @covers GenericSparql::__construct
   */
  public function testConstructor() {
    $gs = new GenericSparql('http://localhost:3030/ds/sparql', $this->graph, $this->model);
    $this->assertInstanceOf('GenericSparql', $gs);
  }

  /**
   * @covers GenericSparql::getGraph
   */
  public function testGetGraph() {
    $gs = new GenericSparql('http://localhost:3030/ds/sparql', $this->graph, $this->model);
    $this->assertEquals($this->graph, $gs->getGraph());
  }

  /**
   * @covers GenericSparql::countConcepts
   * @covers GenericSparql::generateCountConceptsQuery
   * @covers GenericSparql::transformCountConceptsResults
   */
  public function testCountConcepts() {
    $actual = $this->sparql->countConcepts();
    $this->assertEquals(16, $actual['http://www.w3.org/2004/02/skos/core#Concept']['count']);
  }

  /**
   * @covers GenericSparql::countLangConcepts
   * @covers GenericSparql::generateCountLangConceptsQuery
   * @covers GenericSparql::transformCountLangConceptsResults
   */
  public function testCountLangConceptsOneLang() {
    $actual = $this->sparql->countLangConcepts(array('en'));
    $this->assertEquals(11, $actual['en']['skos:prefLabel']);
    $this->assertEquals(1, $actual['en']['skos:altLabel']);
  }

  /**
   * @covers GenericSparql::countLangConcepts
   * @covers GenericSparql::generateCountLangConceptsQuery
   * @covers GenericSparql::transformCountLangConceptsResults
   */
  public function testCountLangConceptsMultipleLangs() {
    $actual = $this->sparql->countLangConcepts(array('en','fi'));
    $this->assertEquals(11, $actual['en']['skos:prefLabel']);
    $this->assertEquals(1, $actual['en']['skos:altLabel']);
    $this->assertEquals(2, $actual['fi']['skos:prefLabel']);
  }

  /**
   * @covers GenericSparql::queryFirstCharacters
   * @covers GenericSparql::generateFirstCharactersQuery
   * @covers GenericSparql::transformFirstCharactersResults
   */
  public function testQueryFirstCharacters() {
    $actual = $this->sparql->queryFirstCharacters('en');
    sort($actual);
    $this->assertEquals(array("-","3","B","C","E","F","M","T"), $actual);
  }

  /**
   * @covers GenericSparql::queryLabel
   */
  public function testQueryLabelWhenConceptNotFound() {
    $actual = $this->sparql->queryLabel('http://notfound', null);
    $this->assertEquals(null, $actual);
  }

  /**
   * @covers GenericSparql::queryLabel
   */
  public function testQueryLabelWhenLabelNotFound() {
    $actual = $this->sparql->queryLabel('http://www.skosmos.skos/test/ta120', null);
    $this->assertEquals(array(), $actual);
  }

  /**
   * @covers GenericSparql::queryConceptsAlphabetical
   * @covers GenericSparql::generateAlphabeticalListQuery
   * @covers GenericSparql::formatFilterConditions
   * @covers GenericSparql::transformAlphabeticalListResults
   */
  public function testQueryConceptsAlphabetical() {
    $actual = $this->sparql->queryConceptsAlphabetical('b', 'en');
    $expected = array (
      0 => array (
        'uri' => 'http://www.skosmos.skos/test/ta116',
        'localname' => 'ta116',
        'prefLabel' => 'Bass',
        'lang' => 'en',
      ),
      1 => array (
        'uri' => 'http://www.skosmos.skos/test/ta122',
        'localname' => 'ta122',
        'prefLabel' => 'Black sea bass',
        'lang' => 'en',
      ),
      2 => array (
        'uri' => 'http://www.skosmos.skos/test/ta114',
        'localname' => 'ta114',
        'prefLabel' => 'Buri',
        'lang' => 'en',
      ),
    );
    $this->assertEquals($expected, $actual);
  }

  /**
   * @covers GenericSparql::queryConceptsAlphabetical
   * @covers GenericSparql::generateAlphabeticalListQuery
   * @covers GenericSparql::formatFilterConditions
   * @covers GenericSparql::formatLimitAndOffset
   * @covers GenericSparql::transformAlphabeticalListResults
   */
  public function testQueryConceptsAlphabeticalLimit() {
    $actual = $this->sparql->queryConceptsAlphabetical('b', 'en', 2);
    $expected = array (
      0 => array (
        'uri' => 'http://www.skosmos.skos/test/ta116',
        'localname' => 'ta116',
        'prefLabel' => 'Bass',
        'lang' => 'en',
      ),
      1 => array (
        'uri' => 'http://www.skosmos.skos/test/ta122',
        'localname' => 'ta122',
        'prefLabel' => 'Black sea bass',
        'lang' => 'en',
      ),
    );
    $this->assertEquals($expected, $actual);
  }

  /**
   * @covers GenericSparql::queryConceptsAlphabetical
   * @covers GenericSparql::generateAlphabeticalListQuery
   * @covers GenericSparql::formatFilterConditions
   * @covers GenericSparql::formatLimitAndOffset
   * @covers GenericSparql::transformAlphabeticalListResults
   */
  public function testQueryConceptsAlphabeticalLimitAndOffset() {
    $actual = $this->sparql->queryConceptsAlphabetical('b', 'en', 2, 1);
    $expected = array (
      0 => array (
        'uri' => 'http://www.skosmos.skos/test/ta122',
        'localname' => 'ta122',
        'prefLabel' => 'Black sea bass',
        'lang' => 'en',
      ),
      1 => array (
        'uri' => 'http://www.skosmos.skos/test/ta114',
        'localname' => 'ta114',
        'prefLabel' => 'Buri',
        'lang' => 'en',
      ),
    );
    $this->assertEquals($expected, $actual);
  }

  /**
   * @covers GenericSparql::queryConceptsAlphabetical
   * @covers GenericSparql::generateAlphabeticalListQuery
   * @covers GenericSparql::formatFilterConditions
   * @covers GenericSparql::transformAlphabeticalListResults
   */
  public function testQueryConceptsAlphabeticalNoResults() {
    $actual = $this->sparql->queryConceptsAlphabetical('x', 'en');
    $expected = array();
    $this->assertEquals($expected, $actual);
  }

  /**
   * @covers GenericSparql::queryConceptsAlphabetical
   * @covers GenericSparql::generateAlphabeticalListQuery
   * @covers GenericSparql::formatFilterConditions
   * @covers GenericSparql::transformAlphabeticalListResults
   */
  public function testQueryConceptsAlphabeticalSpecialChars() {
    $actual = $this->sparql->queryConceptsAlphabetical('!*', 'en');
    $this->assertEquals(1, sizeof($actual));
    $this->assertEquals('-"special" character \\example\\', $actual[0]['prefLabel']);
  }

  /**
   * @covers GenericSparql::queryConceptsAlphabetical
   * @covers GenericSparql::generateAlphabeticalListQuery
   * @covers GenericSparql::formatFilterConditions
   * @covers GenericSparql::transformAlphabeticalListResults
   */
  public function testQueryConceptsAlphabeticalNumbers() {
    $actual = $this->sparql->queryConceptsAlphabetical('0-9', 'en');
    $this->assertEquals(1, sizeof($actual));
    $this->assertContains("3D", $actual[0]['prefLabel']);
  }

  /**
   * @covers GenericSparql::queryConceptsAlphabetical
   * @covers GenericSparql::generateAlphabeticalListQuery
   * @covers GenericSparql::formatFilterConditions
   * @covers GenericSparql::transformAlphabeticalListResults
   */
  public function testQueryConceptsAlphabeticalFull() {
    $actual = $this->sparql->queryConceptsAlphabetical('*', 'en');
    $this->assertEquals(11, sizeof($actual));
  }

  /**
   * @covers GenericSparql::queryConceptInfo
   * @covers GenericSparql::queryConceptInfoGraph
   * @covers GenericSparql::generateConceptInfoQuery
   * @covers GenericSparql::transformConceptInfoResults
   * @covers GenericSparql::filterDuplicateVocabs
   * @covers GenericSparql::getVocabGraphs
   * @covers GenericSparql::formatValuesGraph
   */
  public function testQueryConceptInfoWithMultipleVocabs()
  {
    $this->sparql = new GenericSparql('http://localhost:3030/ds/sparql', '?graph', $this->model);
    $voc2 = $this->model->getVocabulary('test');
    $voc3 = $this->model->getVocabulary('dates');
    $voc4 = $this->model->getVocabulary('groups');
    $actual = $this->sparql->queryConceptInfo(array('http://www.skosmos.skos/test/ta121', 'http://www.skosmos.skos/groups/ta111'), null, array($voc2, $voc3, $voc4), 'en');
    $this->assertInstanceOf('Concept', $actual[0]);
    $this->assertEquals('http://www.skosmos.skos/test/ta121', $actual[0]->getUri());
    $this->assertEquals('http://www.skosmos.skos/groups/ta111', $actual[1]->getUri());
    $this->assertEquals(2, sizeof($actual));
  }

  /**
   * @covers GenericSparql::queryConceptInfo
   * @covers GenericSparql::queryConceptInfoGraph
   * @covers GenericSparql::generateConceptInfoQuery
   * @covers GenericSparql::transformConceptInfoResults
   * @covers GenericSparql::filterDuplicateVocabs
   * @covers GenericSparql::getVocabGraphs
   * @covers GenericSparql::formatValuesGraph
   */
  public function testQueryConceptInfoWithAllVocabs()
  {
    $this->sparql = new GenericSparql('http://localhost:3030/ds/sparql', '?graph', $this->model);
    $actual = $this->sparql->queryConceptInfo(array('http://www.skosmos.skos/test/ta121', 'http://www.skosmos.skos/groups/ta111'), null, null, 'en');
    $this->assertInstanceOf('Concept', $actual[0]);
    $this->assertEquals('http://www.skosmos.skos/test/ta121', $actual[0]->getUri());
    $this->assertEquals('http://www.skosmos.skos/groups/ta111', $actual[1]->getUri());
    $this->assertEquals(2, sizeof($actual));
  }

  /**
   * @covers GenericSparql::queryConceptInfo
   * @covers GenericSparql::queryConceptInfoGraph
   * @covers GenericSparql::generateConceptInfoQuery
   * @covers GenericSparql::transformConceptInfoResults
   * @covers GenericSparql::filterDuplicateVocabs
   */
  public function testQueryConceptInfoWithMultipleSameVocabs()
  {
    $actual = $this->sparql->queryConceptInfo(array('http://www.skosmos.skos/test/ta123'), null, array($this->vocab, $this->vocab, $this->vocab, $this->vocab, $this->vocab), 'en');
    $this->assertInstanceOf('Concept', $actual[0]);
    $this->assertEquals('http://www.skosmos.skos/test/ta123', $actual[0]->getUri());
    $this->assertEquals(1, sizeof($actual));
  }

  /**
   * @covers GenericSparql::queryConceptInfo
   * @covers GenericSparql::queryConceptInfoGraph
   * @covers GenericSparql::generateConceptInfoQuery
   * @covers GenericSparql::transformConceptInfoResults
   * @covers GenericSparql::formatValues
   */
  public function testQueryConceptInfoWithOneURI()
  {
    $actual = $this->sparql->queryConceptInfo(array('http://www.skosmos.skos/test/ta123'), null, array($this->vocab), 'en');
    $this->assertInstanceOf('Concept', $actual[0]);
    $this->assertEquals('http://www.skosmos.skos/test/ta123', $actual[0]->getUri());
  }

  /**
   * @covers GenericSparql::queryConceptInfo
   * @covers GenericSparql::queryConceptInfoGraph
   * @covers GenericSparql::generateConceptInfoQuery
   * @covers GenericSparql::transformConceptInfoResults
   * @covers GenericSparql::formatValues
   */
  public function testQueryConceptInfoWithOneURINotInArray()
  {
    $actual = $this->sparql->queryConceptInfo('http://www.skosmos.skos/test/ta123', null, array($this->vocab), 'en');
    $this->assertInstanceOf('Concept', $actual[0]);
    $this->assertEquals('http://www.skosmos.skos/test/ta123', $actual[0]->getUri());
  }

  /**
   * @covers GenericSparql::queryTypes
   * @covers GenericSparql::generateQueryTypesQuery
   * @covers GenericSparql::transformQueryTypesResults
   */
  public function testQueryTypes()
  {
    $actual = $this->sparql->queryTypes('en');
    $expected = array(
      'http://www.w3.org/2004/02/skos/core#Concept' => array(),
      'http://www.skosmos.skos/test-meta/TestClass' => array(
        'superclass' => 'http://www.w3.org/2004/02/skos/core#Concept',
        'label' => 'Test class'
      )
    );
    $this->assertEquals($expected, $actual);
  }

  /**
   * @covers GenericSparql::queryConceptScheme
   * @covers GenericSparql::generateQueryConceptSchemeQuery
   */
  public function testQueryConceptScheme()
  {
    $actual = $this->sparql->queryConceptScheme('http://www.skosmos.skos/test/conceptscheme');
    $this->assertInstanceOf('EasyRdf\Graph', $actual);
    $this->assertEquals('http://localhost:3030/ds/sparql', $actual->getUri());
  }

  /**
   * @covers GenericSparql::queryConceptSchemes
   * @covers GenericSparql::generateQueryConceptSchemesQuery
   * @covers GenericSparql::transformQueryConceptSchemesResults
   */
  public function testQueryConceptSchemes()
  {
    $actual = $this->sparql->queryConceptSchemes('en');
    foreach($actual as $scheme=>$label) {
      $this->assertEquals('http://www.skosmos.skos/test/conceptscheme', $scheme);
      $this->assertEquals('Test conceptscheme', $label['label']);
    }
  }

  /**
   * @covers GenericSparql::queryConceptSchemes
   * @covers GenericSparql::generateQueryConceptSchemesQuery
   * @covers GenericSparql::transformQueryConceptSchemesResults
   */
  public function testQueryConceptSchemesSubject()
  {
      $sparql = new GenericSparql('http://localhost:3030/ds/sparql', 'http://www.skosmos.skos/test-concept-schemes/', $this->model);

      $actual = $sparql->queryConceptSchemes('en');
      $expected = array(
          'http://exemple.fr/domains' => array(
              'prefLabel' => 'Special Domains Concept Scheme'
          ),
          'http://exemple.fr/mt1' => array(
              'prefLabel' => 'Micro-Thesaurus 1',
              'subject' => array(
                  'uri' => 'http://exemple.fr/d1',
                  'prefLabel' => 'Domain 1'
              )
          ),
          'http://exemple.fr/mt2' => array(
              'prefLabel' => 'Micro-Thesaurus 2',
              'subject' => array(
                  'uri' => 'http://exemple.fr/d1',
                  'prefLabel' => 'Domain 1'
              )
          ),
          'http://exemple.fr/mt3' => array(
              'prefLabel' => 'Micro-Thesaurus 3',
              'subject' => array(
                  'uri' => 'http://exemple.fr/d2',
                  'prefLabel' => 'Domain 2'
              )
          ),
          'http://exemple.fr/thesaurus' => array(
              'prefLabel' => 'The Thesaurus'
          ),
      );

      $this->assertEquals($expected, $actual);
  }

  /**
   * @covers GenericSparql::queryConcepts
   * @covers GenericSparql::generateConceptSearchQueryCondition
   * @covers GenericSparql::generateConceptSearchQueryInner
   * @covers GenericSparql::generateConceptSearchQuery
   * @covers GenericSparql::formatFilterGraph
   * @covers GenericSparql::transformConceptSearchResults
   * @covers GenericSparql::transformConceptSearchResult
   * @covers GenericSparql::shortenUri
   */
  public function testQueryConceptsMultipleVocabs()
  {
    $voc = $this->model->getVocabulary('test');
    $voc2 = $this->model->getVocabulary('groups');
    $this->params->method('getSearchTerm')->will($this->returnValue('Carp'));
    $this->params->method('getVocabs')->will($this->returnValue(array($voc, $voc2)));
    $sparql = new GenericSparql('http://localhost:3030/ds/sparql', '?graph', $this->model);
    $actual = $sparql->queryConcepts(array($voc, $voc2), null, null, $this->params);
    $this->assertEquals(2, sizeof($actual));
    $this->assertEquals('http://www.skosmos.skos/groups/ta112', $actual[0]['uri']);
    $this->assertEquals('http://www.skosmos.skos/test/ta112', $actual[1]['uri']);
  }

  /**
   * @covers GenericSparql::queryConcepts
   * @covers GenericSparql::generateConceptSearchQueryCondition
   * @covers GenericSparql::generateConceptSearchQueryInner
   * @covers GenericSparql::generateConceptSearchQuery
   * @covers GenericSparql::formatFilterGraph
   * @covers GenericSparql::transformConceptSearchResults
   * @covers GenericSparql::transformConceptSearchResult
   * @covers GenericSparql::shortenUri
   */
  public function testQueryConceptsMultipleSchemes()
  {
      $voc = $this->model->getVocabulary('multiple-schemes');
      // returns 3 concepts without the scheme limit, and only 2 with the scheme limit below
      $this->params->method('getSearchTerm')->will($this->returnValue('concept*'));
      $this->params->method('getSchemeLimit')->will($this->returnValue(array('http://www.skosmos.skos/multiple-schemes/cs1', 'http://www.skosmos.skos/multiple-schemes/cs2')));
      $sparql = new GenericSparql('http://localhost:3030/ds/sparql', 'http://www.skosmos.skos/multiple-schemes/', $this->model);
      $actual = $sparql->queryConcepts(array($voc), null, null, $this->params);
      $this->assertEquals(2, sizeof($actual));
      $this->assertEquals('http://www.skosmos.skos/multiple-schemes/c1-in-cs1', $actual[0]['uri']);
      $this->assertEquals('http://www.skosmos.skos/multiple-schemes/c2-in-cs2', $actual[1]['uri']);
  }


  /**
   * @covers GenericSparql::queryConcepts
   * @covers GenericSparql::generateConceptSearchQueryCondition
   * @covers GenericSparql::generateConceptSearchQueryInner
   * @covers GenericSparql::generateConceptSearchQuery
   * @covers GenericSparql::formatFilterGraph
   * @covers GenericSparql::transformConceptSearchResults
   * @covers GenericSparql::transformConceptSearchResult
   * @covers GenericSparql::shortenUri
   */
  public function testQueryConcepts()
  {
    $voc = $this->model->getVocabulary('test');
    $this->params->method('getSearchTerm')->will($this->returnValue('bass*'));
    $this->params->method('getVocabIds')->will($this->returnValue(array('test')));
    $actual = $this->sparql->queryConcepts(array($voc), null, null, $this->params);
    $this->assertEquals(1, sizeof($actual));
    $this->assertEquals('Bass', $actual[0]['prefLabel']);
  }

  /**
   * @covers GenericSparql::queryConcepts
   * @covers GenericSparql::generateConceptSearchQueryCondition
   * @covers GenericSparql::generateConceptSearchQueryInner
   * @covers GenericSparql::generateConceptSearchQuery
   * @covers GenericSparql::formatFilterGraph
   * @covers GenericSparql::transformConceptSearchResults
   * @covers GenericSparql::transformConceptSearchResult
   * @covers GenericSparql::shortenUri
   */
  public function testQueryConceptsMatchAltLabel()
  {
    $voc = $this->model->getVocabulary('test');
    $this->params->method('getSearchTerm')->will($this->returnValue('Golden*'));
    $this->params->method('getVocabIds')->will($this->returnValue(array('test')));
    $actual = $this->sparql->queryConcepts(array($voc), null, null, $this->params);
    $this->assertEquals(1, sizeof($actual));
    $this->assertEquals('Carp', $actual[0]['prefLabel']);
  }

  /**
   * @covers GenericSparql::queryConcepts
   * @covers GenericSparql::generateConceptSearchQueryCondition
   * @covers GenericSparql::generateConceptSearchQueryInner
   * @covers GenericSparql::generateConceptSearchQuery
   * @covers GenericSparql::formatFilterGraph
   * @covers GenericSparql::transformConceptSearchResults
   * @covers GenericSparql::transformConceptSearchResult
   * @covers GenericSparql::shortenUri
   */
  public function testQueryConceptsMatchHiddenLabel()
  {
    $voc = $this->model->getVocabulary('test');
    $this->params->method('getSearchTerm')->will($this->returnValue('Karpit*'));
    $this->params->method('getVocabIds')->will($this->returnValue(array('test')));
    $this->params->method('getHidden')->will($this->returnValue(true));
    $actual = $this->sparql->queryConcepts(array($voc), null, null, $this->params);
    $this->assertEquals(1, sizeof($actual));
    $this->assertEquals('Karppi', $actual[0]['prefLabel']);
  }

  /**
   * @covers GenericSparql::queryConcepts
   * @covers GenericSparql::generateConceptSearchQueryCondition
   * @covers GenericSparql::generateConceptSearchQueryInner
   * @covers GenericSparql::generateConceptSearchQuery
   * @covers GenericSparql::formatFilterGraph
   * @covers GenericSparql::transformConceptSearchResults
   * @covers GenericSparql::transformConceptSearchResult
   * @covers GenericSparql::shortenUri
   */
  public function testQueryConceptsMatchNotation()
  {
    $voc = $this->model->getVocabulary('test');
    $this->params->method('getSearchTerm')->will($this->returnValue('665*'));
    $this->params->method('getVocabIds')->will($this->returnValue(array('test')));
    $actual = $this->sparql->queryConcepts(array($voc), null, null, $this->params);
    $this->assertEquals(1, sizeof($actual));
    $this->assertEquals('http://www.skosmos.skos/test/ta112', $actual[0]['uri']);
  }

  /**
   * @covers GenericSparql::queryConcepts
   * @covers GenericSparql::generateConceptSearchQueryCondition
   * @covers GenericSparql::generateConceptSearchQueryInner
   * @covers GenericSparql::generateConceptSearchQuery
   * @covers GenericSparql::formatFilterGraph
   */
  public function testQueryConceptsAdditionalFields()
  {
    $voc = $this->model->getVocabulary('test');
    $this->params->method('getSearchTerm')->will($this->returnValue('bass*'));
    $this->params->method('getVocabIds')->will($this->returnValue(array('test')));
    $actual = $this->sparql->queryConcepts(array($voc), array('broader'), null, $this->params);
    $this->assertEquals(1, sizeof($actual));
    $this->assertEquals('Bass', $actual[0]['prefLabel']);
    $this->assertEquals('http://www.skosmos.skos/test/ta1', $actual[0]['skos:broader'][0]['uri']);
  }

  /**
   * @covers GenericSparql::queryConcepts
   * @covers GenericSparql::generateConceptSearchQueryCondition
   * @covers GenericSparql::generateConceptSearchQueryInner
   * @covers GenericSparql::generateConceptSearchQuery
   * @covers GenericSparql::transformConceptSearchResults
   * @covers GenericSparql::transformConceptSearchResult
   * @covers GenericSparql::shortenUri
   */
  public function testQueryConceptsExactTerm()
  {
    $voc = $this->model->getVocabulary('test');
    $this->params->method('getSearchTerm')->will($this->returnValue('bass'));
    $this->params->method('getVocabIds')->will($this->returnValue(array('test')));
    $actual = $this->sparql->queryConcepts(array($voc), null, null, $this->params);
    $this->assertEquals(1, sizeof($actual));
    $this->assertEquals('Bass', $actual[0]['prefLabel']);
  }

  /**
   * @covers GenericSparql::queryConcepts
   * @covers GenericSparql::generateConceptSearchQueryCondition
   * @covers GenericSparql::generateConceptSearchQueryInner
   * @covers GenericSparql::generateConceptSearchQuery
   * @covers GenericSparql::transformConceptSearchResults
   * @covers GenericSparql::transformConceptSearchResult
   * @covers GenericSparql::shortenUri
   */
  public function testQueryConceptsAsteriskBeforeTerm()
  {
    $voc = $this->model->getVocabulary('test');
    $this->params->method('getSearchTerm')->will($this->returnValue('*bass'));
    $actual = $this->sparql->queryConcepts(array($voc), null, null, $this->params);
    $this->assertEquals(3, sizeof($actual));
    foreach($actual as $match)
      $this->assertContains('bass', $match['prefLabel'], '',true);
  }

  /**
   * @covers GenericSparql::queryConcepts
   * @covers GenericSparql::generateConceptSearchQueryCondition
   * @covers GenericSparql::generateConceptSearchQueryInner
   * @covers GenericSparql::generateConceptSearchQuery
   * @covers GenericSparql::transformConceptSearchResults
   * @covers GenericSparql::transformConceptSearchResult
   * @covers GenericSparql::shortenUri
   */
  public function testQueryConceptsAsteriskBeforeAndAfterTerm()
  {
    $voc = $this->model->getVocabulary('test');
    $this->params->method('getSearchTerm')->will($this->returnValue('*bass*'));
    $actual = $this->sparql->queryConcepts(array($voc), null, null, $this->params);
    $this->assertEquals(3, sizeof($actual));
    foreach($actual as $match)
      $this->assertContains('bass', $match['prefLabel'], '',true);
  }

  /**
   * @covers GenericSparql::queryLabel
   * @covers GenericSparql::generateLabelQuery
   */
  public function testQueryLabelNotExistingConcept()
  {
    $actual = $this->sparql->queryLabel('http://notfound', 'en');
    $this->assertEquals(null, $actual);
  }

  /**
   * @covers GenericSparql::queryLabel
   * @covers GenericSparql::generateLabelQuery
   */
  public function testQueryLabel()
  {
    $actual = $this->sparql->queryLabel('http://www.skosmos.skos/test/ta112', 'en');
    $expected = array('en' => 'Carp');
    $this->assertEquals($expected, $actual);
  }

  /**
   * @covers GenericSparql::queryLabel
   * @covers GenericSparql::generateLabelQuery
   */
  public function testQueryLabelWithoutLangParamGiven()
  {
    $actual = $this->sparql->queryLabel('http://www.skosmos.skos/test/ta112', null);
    $expected = array('en' => 'Carp', 'fi' => 'Karppi');
    $this->assertEquals($expected, $actual);
  }

  /**
   * @covers GenericSparql::queryProperty
   * @covers GenericSparql::transformPropertyQueryResults
   * @covers GenericSparql::generatePropertyQuery
   */
  public function testQueryPropertyForBroaderThatExists()
  {
    $actual = $this->sparql->queryProperty('http://www.skosmos.skos/test/ta116', 'skos:broader', 'en');
    $expected = array('http://www.skosmos.skos/test/ta1' => array('label' => 'Fish'));
    $this->assertEquals($expected, $actual);
  }

  /**
   * @covers GenericSparql::queryProperty
   * @covers GenericSparql::transformPropertyQueryResults
   * @covers GenericSparql::generatePropertyQuery
   */
  public function testQueryPropertyForNarrowerThatDoesntExist()
  {
    $actual = $this->sparql->queryProperty('http://www.skosmos.skos/test/ta116', 'skos:narrower', 'en');
    $expected = array();
    $this->assertEquals($expected, $actual);
  }

  /**
   * @covers GenericSparql::queryProperty
   * @covers GenericSparql::transformPropertyQueryResults
   * @covers GenericSparql::generatePropertyQuery
   */
  public function testQueryPropertyForNonexistentConcept()
  {
    $actual = $this->sparql->queryProperty('http://notfound', 'skos:narrower', 'en');
    $this->assertEquals(null, $actual);
  }

  /**
   * @covers GenericSparql::queryTransitiveProperty
   * @covers GenericSparql::generateTransitivePropertyQuery
   * @covers GenericSparql::transformTransitivePropertyResults
   */
  public function testQueryTransitiveProperty()
  {
    $actual = $this->sparql->queryTransitiveProperty('http://www.skosmos.skos/test/ta111', array('skos:broader'), 'en', '10');
    $expected = array(
      'http://www.skosmos.skos/test/ta111' =>
        array(
          'label' => 'Tuna',
          'direct' =>
          array (
            0 => 'http://www.skosmos.skos/test/ta1',
          ),
        ),
        'http://www.skosmos.skos/test/ta1' =>
        array (
          'label' => 'Fish',
        )
    );
    $this->assertEquals($expected, $actual);
  }

  /**
   * @covers GenericSparql::queryTransitiveProperty
   * @covers GenericSparql::generateTransitivePropertyQuery
   * @covers GenericSparql::transformTransitivePropertyResults
   */
  public function testQueryTransitivePropertyLongerPath()
  {
    $actual = $this->sparql->queryTransitiveProperty('http://www.skosmos.skos/test/ta122', array('skos:broader'), 'en', '10');
    $expected = array(
      'http://www.skosmos.skos/test/ta122' =>
        array (
          'label' => 'Black sea bass',
          'direct' =>
          array (
            0 => 'http://www.skosmos.skos/test/ta116',
          ),
        ),
        'http://www.skosmos.skos/test/ta1' =>
        array (
          'label' => 'Fish',
        ),
        'http://www.skosmos.skos/test/ta116' =>
        array (
          'label' => 'Bass',
          'direct' =>
          array (
            0 => 'http://www.skosmos.skos/test/ta1',
          ),
        ),
    );
    $this->assertEquals($expected, $actual);
  }


  /**
   * @covers GenericSparql::queryChildren
   * @covers GenericSparql::generateChildQuery
   * @covers GenericSparql::transformNarrowerResults
   */
  public function testQueryChildren()
  {
    $actual = $this->sparql->queryChildren('http://www.skosmos.skos/test/ta1', 'en', 'en', array('skos:broader'));
    $actual_uris = array();
    foreach ($actual as $child)
      $actual_uris[$child['uri']] = $child['uri'];
    $expected = array ('http://www.skosmos.skos/test/ta111','http://www.skosmos.skos/test/ta112','http://www.skosmos.skos/test/ta114','http://www.skosmos.skos/test/ta115','http://www.skosmos.skos/test/ta116','http://www.skosmos.skos/test/ta117','http://www.skosmos.skos/test/ta119','http://www.skosmos.skos/test/ta120');
    foreach ($expected as $uri)
      $this->assertArrayHasKey($uri, $actual_uris);
  }

  /**
   * @covers GenericSparql::queryChildren
   * @covers GenericSparql::generateChildQuery
   * @covers GenericSparql::transformNarrowerResults
   */
  public function testQueryChildrenOfNonExistentConcept()
  {
    $actual = $this->sparql->queryChildren('http://notfound', 'en', 'en', array('skos:broader'));
    $this->assertEquals(null, $actual);
  }

  /**
   * @covers GenericSparql::queryTopConcepts
   */
  public function testQueryTopConcepts()
  {
    $actual = $this->sparql->queryTopConcepts('http://www.skosmos.skos/test/conceptscheme', 'en', 'en');
    $this->assertEquals(array (0 => array ('uri' => 'http://www.skosmos.skos/test/ta1','label' => 'Fish','hasChildren' => true, 'topConceptOf' => 'http://www.skosmos.skos/test/conceptscheme')), $actual);
  }

  /**
   * @covers GenericSparql::queryTopConcepts
   */
  public function testQueryTopConceptsFallbackLanguage()
  {
    $actual = $this->sparql->queryTopConcepts('http://www.skosmos.skos/test/conceptscheme', 'fi', 'en');
    $this->assertEquals(array (0 => array ('uri' => 'http://www.skosmos.skos/test/ta1','label' => 'Fish (en)','hasChildren' => true, 'topConceptOf' => 'http://www.skosmos.skos/test/conceptscheme')), $actual);
  }

  /**
   * @covers GenericSparql::queryTopConcepts
   */
  public function testQueryTopConceptsOtherLanguage()
  {
    $actual = $this->sparql->queryTopConcepts('http://www.skosmos.skos/test/conceptscheme', 'fi', 'fi');
    $this->assertEquals(array (0 => array ('uri' => 'http://www.skosmos.skos/test/ta1','label' => 'Fish (en)','hasChildren' => true, 'topConceptOf' => 'http://www.skosmos.skos/test/conceptscheme')), $actual);
  }

  /**
   * @covers GenericSparql::queryParentList
   * @covers GenericSparql::generateParentListQuery
   * @covers GenericSparql::transformParentListResults
   */
  public function testQueryParentList()
  {
    $actual = $this->sparql->queryParentList('http://www.skosmos.skos/test/ta122', 'en', 'en', array('skos:broader'));
    $expected = array(
      'http://www.skosmos.skos/test/ta116' => array (
        'uri' => 'http://www.skosmos.skos/test/ta116',
        'prefLabel' => 'Bass',
        'broader' =>
        array (
          0 => 'http://www.skosmos.skos/test/ta1',
        ),
      ),
      'http://www.skosmos.skos/test/ta122' => array (
        'uri' => 'http://www.skosmos.skos/test/ta122',
        'prefLabel' => 'Black sea bass',
        'broader' =>
        array (
          0 => 'http://www.skosmos.skos/test/ta116',
        ),
      ),
    );
    $props = array (
      'uri' => 'http://www.skosmos.skos/test/ta1',
      'tops' => array('http://www.skosmos.skos/test/conceptscheme'),
      'prefLabel' => 'Fish',
    );
    $narrowers = array (
      0 => array (
        'uri' => 'http://www.skosmos.skos/test/ta112',
        'label' => 'Carp',
        'hasChildren' => true,
        'notation' => '665'
      ),
      1 => array (
        'uri' => 'http://www.skosmos.skos/test/ta117',
        'label' => '3D Bass',
        'hasChildren' => false,
      ),
      2 => array (
        'uri' => 'http://www.skosmos.skos/test/ta119',
        'label' => 'Hauki (fi)',
        'hasChildren' => false,
      ),
      3 => array (
        'uri' => 'http://www.skosmos.skos/test/ta115',
        'label' => 'Eel',
        'hasChildren' => false,
      ),
      4 => array (
        'uri' => 'http://www.skosmos.skos/test/ta120',
        'label' => NULL,
        'hasChildren' => false,
      ),
      5 => array (
        'uri' => 'http://www.skosmos.skos/test/ta111',
        'label' => 'Tuna',
        'hasChildren' => false,
      ),
      6 => array (
        'uri' => 'http://www.skosmos.skos/test/ta116',
        'label' => 'Bass',
        'hasChildren' => false,
      ),
      7 => array (
        'uri' => 'http://www.skosmos.skos/test/ta113',
        'label' => NULL,
        'hasChildren' => false,
      ),
      8 => array (
        'uri' => 'http://www.skosmos.skos/test/ta114',
        'label' => 'Buri',
        'hasChildren' => false,
      ),
    );
    foreach ($narrowers as $narrower) {
      $this->assertContains($narrower, $actual['http://www.skosmos.skos/test/ta1']['narrower']);
    }
    foreach ($expected as $concept) {
      $this->assertContains($concept, $actual);
    }
    foreach ($props as $property) {
      $this->assertContains($property, $actual['http://www.skosmos.skos/test/ta1']);
    }
  }

  /**
   * @covers GenericSparql::listConceptGroups
   * @covers GenericSparql::generateConceptGroupsQuery
   * @covers GenericSparql::transformConceptGroupsResults
   */
  public function testListConceptGroups()
  {
    $voc = $this->model->getVocabulary('groups');
    $graph = $voc->getGraph();
    $sparql = new GenericSparql('http://localhost:3030/ds/sparql', $graph, $this->model);
    $actual = $sparql->ListConceptGroups('http://www.w3.org/2004/02/skos/core#Collection', 'en', false);
    $expected = array (0 => array ('prefLabel' => 'Fish', 'uri' => 'http://www.skosmos.skos/groups/fish', 'hasMembers' => true, 'childGroups' => array('http://www.skosmos.skos/groups/sub')), 1 => array ('prefLabel' => 'Freshwater fish', 'uri' => 'http://www.skosmos.skos/groups/fresh', 'hasMembers' => true), 2 => array ('prefLabel' => 'Saltwater fish', 'uri' => 'http://www.skosmos.skos/groups/salt', 'hasMembers' => true),3 => array ('prefLabel' => 'Submarine-like fish', 'uri' => 'http://www.skosmos.skos/groups/sub', 'hasMembers' => true));
    $this->assertEquals($expected, $actual);
  }

  /**
   * @covers GenericSparql::listConceptGroupContents
   * @covers GenericSparql::generateConceptGroupContentsQuery
   * @covers GenericSparql::transformConceptGroupContentsResults
   */
  public function testListConceptGroupContentsExcludingDeprecatedConcept()
  {
    $voc = $this->model->getVocabulary('groups');
    $graph = $voc->getGraph();
    $sparql = new GenericSparql('http://localhost:3030/ds/sparql', $graph, $this->model);
    $actual = $sparql->ListConceptGroupContents('http://www.w3.org/2004/02/skos/core#Collection', 'http://www.skosmos.skos/groups/salt', 'en');
    $this->assertEquals('http://www.skosmos.skos/groups/ta113', $actual[0]['uri']);
    $this->assertEquals(1, sizeof($actual));
  }

  /**
   * @covers GenericSparql::listConceptGroupContents
   * @covers GenericSparql::generateConceptGroupContentsQuery
   * @covers GenericSparql::transformConceptGroupContentsResults
   */
  public function testListConceptGroupContentsIncludingDeprecatedConcept()
  {
      $voc = $this->model->getVocabulary('showDeprecated');
      $graph = $voc->getGraph();
      $sparql = new GenericSparql('http://localhost:3030/ds/sparql', $graph, $this->model);
      $actual = $sparql->ListConceptGroupContents('http://www.w3.org/2004/02/skos/core#Collection', 'http://www.skosmos.skos/groups/salt', 'en', $voc->getConfig()->getShowDeprecated());
      $expected = array (
          0 => array (
              'uri' => 'http://www.skosmos.skos/groups/ta113',
              'isSuper' => false,
              'hasMembers' => false,
              'type' => array('skos:Concept'),
              'prefLabel' => 'Flatfish'
          ),
          1 => array (
              'uri' => 'http://www.skosmos.skos/groups/ta111',
              'isSuper' => false,
              'hasMembers' => false,
              'type' => array('skos:Concept'),
              'prefLabel' => 'Tuna'
          )
      );
      $this->assertEquals($expected, $actual);
      $this->assertEquals(2, sizeof($actual));
  }

  /**
   * @covers GenericSparql::queryChangeList
   * @covers GenericSparql::generateChangeListQuery
   * @covers GenericSparql::transFormChangeListResults
   */
  public function testQueryChangeList()
  {
    $voc = $this->model->getVocabulary('changes');
    $graph = $voc->getGraph();
    $sparql = new GenericSparql('http://localhost:3030/ds/sparql', $graph, $this->model);
    $actual = $sparql->queryChangeList('en', 0, 'dc11:created');
    $order = array();
    foreach($actual as $concept) {
        array_push($order, $concept['prefLabel']);
    }
    $this->assertEquals(4, sizeof($actual));
    $this->assertEquals(array('Fourth date', 'Hurr Durr', 'Second date', 'A date'), $order);
  }

  /**
   * @covers GenericSparql::formatTypes
   * @covers GenericSparql::queryConcepts
   */
  public function testLimitSearchToType()
  {
    $voc = $this->model->getVocabulary('test');
    $graph = $voc->getGraph();
    $sparql = new GenericSparql('http://localhost:3030/ds/sparql', $graph, $this->model);
    $this->params->method('getSearchTerm')->will($this->returnValue('*'));
    $this->params->method('getTypeLimit')->will($this->returnValue(array('mads:Topic')));
    $actual = $this->sparql->queryConcepts(array($voc), null, true, $this->params);
    $this->assertEquals(1, sizeof($actual));
  }

  /**
   * @covers GenericSparql::queryConcepts
   * @covers GenericSparql::generateConceptSearchQueryCondition
   * @covers GenericSparql::generateConceptSearchQueryInner
   * @covers GenericSparql::generateConceptSearchQuery
   * @covers GenericSparql::formatFilterGraph
   * @covers GenericSparql::transformConceptSearchResults
   * @covers GenericSparql::transformConceptSearchResult
   * @covers GenericSparql::shortenUri
   * @covers GenericSparql::formatExtraFields
   * @covers GenericSparql::formatPropertyCsvClause
   * @covers GenericSparql::formatPrefLabelCsvClause
   */
  public function testQueryConceptsWithExtraFields()
  {
    $voc = $this->model->getVocabulary('test');
    $this->params->method('getSearchTerm')->will($this->returnValue('bass*'));
    $this->params->method('getVocabIds')->will($this->returnValue(array('test')));
    $actual = $this->sparql->queryConcepts(array($voc), array('broader', 'prefLabel'), null, $this->params);
    $this->assertEquals(1, sizeof($actual));
    $expected = array('uri' => 'http://www.skosmos.skos/test/ta116', 'type' => array (0 => 'skos:Concept',
    1 => 'meta:TestClass',
  ),
);
    $this->assertEquals(array('en' => 'Bass'), $actual[0]['prefLabels']);
    $this->assertEquals(array(0 => array('uri' => 'http://www.skosmos.skos/test/ta1')), $actual[0]['skos:broader']);
  }

  /**
   * @covers GenericSparql::querySuperProperties
   */
  public function testQuerySuperProperties()
  {
      $this->sparql = new GenericSparql('http://localhost:3030/ds/sparql', '?graph', $this->model);
      $actual = $this->sparql->querySuperProperties('http://example.com/myns#property');
      $this->assertEquals(1, sizeof($actual));
      $expected = array('http://example.com/myns#superProperty');
      $this->assertEquals($actual, $expected);
  }
}
