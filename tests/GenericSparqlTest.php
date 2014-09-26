<?php

class GenericSparqlTest extends PHPUnit_Framework_TestCase
{
  private $model; 
  private $graph; 
  private $sparql;

  protected function setUp() {
    require_once 'testconfig.inc';
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    $this->model = new Model();
    $voc = $this->model->getVocabulary('test');
    $this->graph = $voc->getGraph();
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
   */
  public function testCountConcepts() {
    $actual = $this->sparql->countConcepts();
    $this->assertEquals(10, $actual);
  }
  
  /**
   * @covers GenericSparql::countLangConcepts
   */
  public function testCountLangConceptsOneLang() {
    $actual = $this->sparql->countLangConcepts(array('en'));
    $this->assertEquals(8, $actual['en']['skos:prefLabel']);
    $this->assertEquals(1, $actual['en']['skos:altLabel']);
  }
  
  /**
   * @covers GenericSparql::countLangConcepts
   */
  public function testCountLangConceptsMultipleLangs() {
    $actual = $this->sparql->countLangConcepts(array('en','fi'));
    $this->assertEquals(8, $actual['en']['skos:prefLabel']);
    $this->assertEquals(1, $actual['en']['skos:altLabel']);
    $this->assertEquals(2, $actual['fi']['skos:prefLabel']);
  }
  
  /**
   * @covers GenericSparql::queryFirstCharacters
   */
  public function testQueryFirstCharacters() {
    $actual = $this->sparql->queryFirstCharacters('en');
    $this->assertEquals(array("T","C","B","E","3","-", "F"), $actual);
  }

  /**
   * @covers GenericSparql::queryLabel
   */
  public function estQueryLabel() {
    $actual = $this->sparql->queryLabel('http://www.skosmos.skos/test/ta112','en');
    $this->assertEquals(array('en' => 'Carp'), $actual);
  }
  
  /**
   * @covers GenericSparql::queryLabel
   */
  public function estQueryLabelWithoutLangParamGiven() {
    $actual = $this->sparql->queryLabel('http://www.skosmos.skos/test/ta112', null);
    $this->assertEquals(array('en' => 'Carp', 'fi' => 'Karppi'), $actual);
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
   */
  public function testQueryConceptsAlphabeticalNoResults() {
    $actual = $this->sparql->queryConceptsAlphabetical('x', 'en');
    $expected = array();
    $this->assertEquals($expected, $actual);
  }

  /**
   * @covers GenericSparql::queryConceptsAlphabetical
   */
  public function testQueryConceptsAlphabeticalSpecialChars() {
    $actual = $this->sparql->queryConceptsAlphabetical('!*', 'en');
    $this->assertEquals(1, sizeof($actual));
    $this->assertEquals("-special character example", $actual[0]['prefLabel']);
  }
  
  /**
   * @covers GenericSparql::queryConceptsAlphabetical
   */
  public function testQueryConceptsAlphabeticalNumbers() {
    $actual = $this->sparql->queryConceptsAlphabetical('0-9', 'en');
    $this->assertEquals(1, sizeof($actual));
    $this->assertContains("3D", $actual[0]['prefLabel']);
  }
  
  /**
   * @covers GenericSparql::queryConceptsAlphabetical
   */
  public function testQueryConceptsAlphabeticalFull() {
    $actual = $this->sparql->queryConceptsAlphabetical('*', 'en');
    $this->assertEquals(9, sizeof($actual));
  }

  /**
   * @covers GenericSparql::queryConceptInfo
   * @todo   Implement testQueryConceptInfo().
   */
  public function testQueryConceptInfo()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  /**
   * @covers GenericSparql::queryTypes
   * @todo   Implement testQueryTypes().
   */
  public function testQueryTypes()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  /**
   * @covers GenericSparql::queryConceptScheme
   * @todo   Implement testQueryConceptScheme().
   */
  public function testQueryConceptScheme()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  /**
   * @covers GenericSparql::queryConceptSchemes
   * @todo   Implement testQueryConceptSchemes().
   */
  public function testQueryConceptSchemes()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  /**
   * @covers GenericSparql::queryConcepts
   * @todo   Implement testQueryConcepts().
   */
  public function testQueryConcepts()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  /**
   * @covers GenericSparql::queryLabel
   * @todo   Implement testQueryLabel().
   */
  public function testQueryLabel()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  /**
   * @covers GenericSparql::queryProperty
   * @todo   Implement testQueryProperty().
   */
  public function testQueryProperty()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  /**
   * @covers GenericSparql::queryTransitiveProperty
   * @todo   Implement testQueryTransitiveProperty().
   */
  public function testQueryTransitiveProperty()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  /**
   * @covers GenericSparql::queryChildren
   * @todo   Implement testQueryChildren().
   */
  public function testQueryChildren()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  /**
   * @covers GenericSparql::queryTopConcepts
   * @todo   Implement testQueryTopConcepts().
   */
  public function testQueryTopConcepts()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  /**
   * @covers GenericSparql::queryParentList
   * @todo   Implement testQueryParentList().
   */
  public function testQueryParentList()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  /**
   * @covers GenericSparql::listConceptGroups
   * @todo   Implement testListConceptGroups().
   */
  public function testListConceptGroups()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  /**
   * @covers GenericSparql::listConceptGroupContents
   * @todo   Implement testListConceptGroupContents().
   */
  public function testListConceptGroupContents()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }
}
  
