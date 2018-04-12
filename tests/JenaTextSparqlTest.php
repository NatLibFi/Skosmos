<?php

class JenaTextSparqlTest extends PHPUnit\Framework\TestCase
{
  private $model;
  private $graph;
  private $sparql;
  private $vocab;
  private $params;

  protected function setUp() {
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    $this->model = new Model(new GlobalConfig('/../tests/jenatestconfig.inc'));
    $this->vocab = $this->model->getVocabulary('test');
    $this->graph = $this->vocab->getGraph();
    $this->params = $this->getMockBuilder('ConceptSearchParameters')->disableOriginalConstructor()->getMock();
    $this->sparql = new JenaTextSparql('http://localhost:13030/ds/sparql', $this->graph, $this->model);
  }

  /**
   * @covers JenaTextSparql::__construct
   */
  public function testConstructor() {
    $gs = new JenaTextSparql('http://localhost:13030/ds/sparql', $this->graph, $this->model);
    $this->assertInstanceOf('JenaTextSparql', $gs);
  }

  /**
   * @covers JenaTextSparql::generateAlphabeticalListQuery
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
   * @covers JenaTextSparql::generateAlphabeticalListQuery
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
   * @covers JenaTextSparql::generateAlphabeticalListQuery
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
   * @covers JenaTextSparql::generateAlphabeticalListQuery
   */
  public function testQueryConceptsAlphabeticalNoResults() {
    $actual = $this->sparql->queryConceptsAlphabetical('x', 'en');
    $expected = array();
    $this->assertEquals($expected, $actual);
  }

  /**
   * @covers JenaTextSparql::generateAlphabeticalListQuery
   */
  public function testQueryConceptsAlphabeticalSpecialChars() {
    $actual = $this->sparql->queryConceptsAlphabetical('!*', 'en');
    $this->assertEquals(1, sizeof($actual));
    $this->assertEquals('-"special" character \\example\\', $actual[0]['prefLabel']);
  }

  /**
   * @covers JenaTextSparql::generateAlphabeticalListQuery
   */
  public function testQueryConceptsAlphabeticalNumbers() {
    $actual = $this->sparql->queryConceptsAlphabetical('0-9', 'en');
    $this->assertEquals(1, sizeof($actual));
    $this->assertContains("3D", $actual[0]['prefLabel']);
  }

  /**
   * @covers JenaTextSparql::generateAlphabeticalListQuery
   */
  public function testQueryConceptsAlphabeticalFull() {
    $actual = $this->sparql->queryConceptsAlphabetical('*', 'en');
    $this->assertEquals(11, sizeof($actual));
  }

  /**
   * @covers JenaTextSparql::createTextQueryCondition
   * @covers JenaTextSparql::generateConceptSearchQueryCondition
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
   * @covers JenaTextSparql::createTextQueryCondition
   * @covers JenaTextSparql::generateConceptSearchQueryCondition
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
   * @covers JenaTextSparql::createTextQueryCondition
   * @covers JenaTextSparql::generateConceptSearchQueryCondition
   */
  public function testQueryConceptsDefaultGraph()
  {
    $this->params->method('getSearchTerm')->will($this->returnValue('bass*'));
    $this->params->method('getVocabIds')->will($this->returnValue(null));
    $actual = $this->sparql->queryConcepts(array(), null, null, $this->params);
    $this->assertEquals(1, sizeof($actual));
    $this->assertEquals('Bass', $actual[0]['prefLabel']);
  }

  /**
   * @covers JenaTextSparql::formatOrderBy
   */
  public function testQueryConceptsAlphabeticalOrderBy() {
    $vocab = $this->model->getVocabulary('collation');
    $graph = $vocab->getGraph();
    $sparql = new JenaTextSparql('http://localhost:13030/ds/sparql', $graph, $this->model);
    $actual = $sparql->queryConceptsAlphabetical('t', 'fi');
    $expected = array (
      0 => array (
        'uri' => 'http://www.skosmos.skos/collation/val1',
        'localname' => 'val1',
        'prefLabel' => 'tšekin kieli',
        'lang' => 'fi',
      ),
      1 => array (
        'uri' => 'http://www.skosmos.skos/collation/val2',
        'localname' => 'val2',
        'prefLabel' => 'töyhtöhyyppä',
        'lang' => 'fi',
      )
    );
    $this->assertEquals($expected, $actual);
  }

}
