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
    $this->assertEquals(13, $actual);
  }
  
  /**
   * @covers GenericSparql::countLangConcepts
   */
  public function testCountLangConceptsOneLang() {
    $actual = $this->sparql->countLangConcepts(array('en'));
    $this->assertEquals(11, $actual['en']['skos:prefLabel']);
    $this->assertEquals(1, $actual['en']['skos:altLabel']);
  }
  
  /**
   * @covers GenericSparql::countLangConcepts
   */
  public function testCountLangConceptsMultipleLangs() {
    $actual = $this->sparql->countLangConcepts(array('en','fi'));
    $this->assertEquals(11, $actual['en']['skos:prefLabel']);
    $this->assertEquals(1, $actual['en']['skos:altLabel']);
    $this->assertEquals(2, $actual['fi']['skos:prefLabel']);
  }
  
  /**
   * @covers GenericSparql::queryFirstCharacters
   */
  public function testQueryFirstCharacters() {
    $actual = $this->sparql->queryFirstCharacters('en');
    sort($actual);
    $this->assertEquals(array("-","3","B","C","E","F","M","T"), $actual);
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
    $this->assertEquals('-"special" character \\example\\', $actual[0]['prefLabel']);
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
    $this->assertEquals(11, sizeof($actual));
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
   */
  public function testQueryConceptScheme()
  {
    $actual = $this->sparql->queryConceptScheme('http://www.skosmos.skos/test/conceptscheme');
    $this->assertInstanceOf('EasyRdf_Graph', $actual);
    $this->assertEquals('http://localhost:3030/ds/sparql', $actual->getUri());
  }

  /**
   * @covers GenericSparql::queryConceptSchemes
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
   * @covers GenericSparql::queryConcepts
   */
  public function testQueryConcepts()
  {
    $voc = $this->model->getVocabulary('test');
    $actual = $this->sparql->queryConcepts('bass*',array($voc),'en', 'en', 20, 0, null, array('skos:Concept'));
    $this->assertEquals(1, sizeof($actual));
    $this->assertEquals('Bass', $actual[0]['prefLabel']);
  }
  
  /**
   * @covers GenericSparql::queryConcepts
   */
  public function testQueryConceptsAsteriskBeforeTerm()
  {
    $voc = $this->model->getVocabulary('test');
    $actual = $this->sparql->queryConcepts('*bass',array($voc),'en', 'en', 20, 0, null, array('skos:Concept'));
    $this->assertEquals(3, sizeof($actual));
    foreach($actual as $match)
      $this->assertContains('bass', $match['prefLabel'], '',true);
  }
  
  /**
   * @covers GenericSparql::queryLabel
   */
  public function testQueryLabelNotExistingConcept()
  {
    $actual = $this->sparql->queryLabel('http://notfound', 'en');
    $this->assertEquals(null, $actual);
  }

  /**
   * @covers GenericSparql::queryLabel
   */
  public function testQueryLabel()
  {
    $actual = $this->sparql->queryLabel('http://www.skosmos.skos/test/ta112', 'en');
    $expected = array('en' => 'Carp');
    $this->assertEquals($expected, $actual);
  }
  
  /**
   * @covers GenericSparql::queryLabel
   */
  public function testQueryLabelWithoutLangParamGiven()
  {
    $actual = $this->sparql->queryLabel('http://www.skosmos.skos/test/ta112', null);
    $expected = array('en' => 'Carp', 'fi' => 'Karppi');
    $this->assertEquals($expected, $actual);
  }

  /**
   * @covers GenericSparql::queryProperty
   */
  public function testQueryPropertyForBroaderThatExists()
  {
    $actual = $this->sparql->queryProperty('http://www.skosmos.skos/test/ta116', 'skos:broader', 'en');
    $expected = array('http://www.skosmos.skos/test/ta1' => array('label' => 'Fish'));
    $this->assertEquals($expected, $actual);
  }
  
  /**
   * @covers GenericSparql::queryProperty
   */
  public function testQueryPropertyForNarrowerThatDoesntExist()
  {
    $actual = $this->sparql->queryProperty('http://www.skosmos.skos/test/ta116', 'skos:narrower', 'en');
    $expected = array();
    $this->assertEquals($expected, $actual);
  }
  
  /**
   * @covers GenericSparql::queryProperty
   */
  public function testQueryPropertyForNonexistentConcept()
  {
    $actual = $this->sparql->queryProperty('http://notfound', 'skos:narrower', 'en');
    $this->assertEquals(null, $actual);
  }

  /**
   * @covers GenericSparql::queryTransitiveProperty
   */
  public function testQueryTransitiveProperty()
  {
    $actual = $this->sparql->queryTransitiveProperty('http://www.skosmos.skos/test/ta111', 'skos:broader', 'en', '10');
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
   */
  public function testQueryTransitivePropertyLongerPath()
  {
    $actual = $this->sparql->queryTransitiveProperty('http://www.skosmos.skos/test/ta122', 'skos:broader', 'en', '10');
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
   */
  public function testQueryChildren()
  {
    $actual = $this->sparql->queryChildren('http://www.skosmos.skos/test/ta1', 'en', 'en');
    $actual_uris = array();
    foreach ($actual as $child)
      $actual_uris[$child['uri']] = $child['uri'];
    $expected = array ('http://www.skosmos.skos/test/ta111','http://www.skosmos.skos/test/ta112','http://www.skosmos.skos/test/ta114','http://www.skosmos.skos/test/ta115','http://www.skosmos.skos/test/ta116','http://www.skosmos.skos/test/ta117','http://www.skosmos.skos/test/ta119','http://www.skosmos.skos/test/ta120','http://www.skosmos.skos/test/ta113');
    foreach ($expected as $uri)
      $this->assertArrayHasKey($uri, $actual_uris);
  }
  
  /**
   * @covers GenericSparql::queryChildren
   */
  public function testQueryChildrenOfNonExistentConcept()
  {
    $actual = $this->sparql->queryChildren('http://notfound', 'en', 'en');
    $this->assertEquals(null, $actual);
  }

  /**
   * @covers GenericSparql::queryTopConcepts
   */
  public function testQueryTopConcepts()
  {
    $actual = $this->sparql->queryTopConcepts('http://www.skosmos.skos/test/conceptscheme', 'en');
    $this->assertEquals(array('http://www.skosmos.skos/test/ta1' => 'Fish'), $actual);
  }

  /**
   * @covers GenericSparql::queryParentList
   */
  public function testQueryParentList()
  {
    $actual = $this->sparql->queryParentList('http://www.skosmos.skos/test/ta122', 'en', 'en');
    $expected = array(
      'http://www.skosmos.skos/test/ta1' => 
      array (
        'uri' => 'http://www.skosmos.skos/test/ta1',
        'top' => 'http://www.skosmos.skos/test/conceptscheme',
        'narrower' => 
        array (
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
        ),
        'prefLabel' => 'Fish',
      ),
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
    $this->assertEquals($expected, $actual);
  }

  /**
   * @covers GenericSparql::listConceptGroups
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
   */
  public function testListConceptGroupContentsIncludingDeprecatedConcept()
  {
    $voc = $this->model->getVocabulary('groups');
    $graph = $voc->getGraph();
    $sparql = new GenericSparql('http://localhost:3030/ds/sparql', $graph, $this->model);
    $actual = $sparql->ListConceptGroupContents('http://www.w3.org/2004/02/skos/core#Collection', 'http://www.skosmos.skos/groups/salt', 'en');
    $this->assertEquals('http://www.skosmos.skos/groups/ta113', $actual[0]['uri']);
    $this->assertEquals(1, sizeof($actual));
  }
}
  
