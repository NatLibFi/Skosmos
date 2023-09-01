<?php

class JenaTextSparqlTest extends PHPUnit\Framework\TestCase
{
    private $model;
    private $graph;
    private $sparql;
    private $vocab;
    private $params;

    protected function setUp(): void
    {
        putenv("LANGUAGE=en_GB.utf8");
        putenv("LC_ALL=en_GB.utf8");
        setlocale(LC_ALL, 'en_GB.utf8');
        $this->model = new Model(new GlobalConfig('/../../tests/jenatestconfig.ttl'));
        $this->vocab = $this->model->getVocabulary('test');
        $this->graph = $this->vocab->getGraph();
        $this->params = $this->getMockBuilder('ConceptSearchParameters')->disableOriginalConstructor()->getMock();
        $this->endpoint = getenv('SKOSMOS_SPARQL_ENDPOINT');
        $this->sparql = new JenaTextSparql($this->endpoint, $this->graph, $this->model);
    }

    /**
     * @covers JenaTextSparql::__construct
     */
    public function testConstructor()
    {
        $gs = new JenaTextSparql($this->endpoint, $this->graph, $this->model);
        $this->assertInstanceOf('JenaTextSparql', $gs);
    }

    /**
     * @covers JenaTextSparql::generateAlphabeticalListQuery
     */
    public function testQueryConceptsAlphabetical()
    {
        $actual = $this->sparql->queryConceptsAlphabetical('b', 'en');
        $expected = array(
          0 => array(
            'uri' => 'http://www.skosmos.skos/test/ta116',
            'localname' => 'ta116',
            'prefLabel' => 'Bass',
            'lang' => 'en',
          ),
          1 => array(
            'uri' => 'http://www.skosmos.skos/test/ta122',
            'localname' => 'ta122',
            'prefLabel' => 'Black sea bass',
            'lang' => 'en',
          ),
          2 => array(
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
    public function testQueryConceptsAlphabeticalLimit()
    {
        $actual = $this->sparql->queryConceptsAlphabetical('b', 'en', 2);
        $expected = array(
          0 => array(
            'uri' => 'http://www.skosmos.skos/test/ta116',
            'localname' => 'ta116',
            'prefLabel' => 'Bass',
            'lang' => 'en',
          ),
          1 => array(
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
    public function testQueryConceptsAlphabeticalEmpty()
    {
        $actual = $this->sparql->queryConceptsAlphabetical('', 'en');
        $expected = array();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers JenaTextSparql::generateAlphabeticalListQuery
     */
    public function testQueryConceptsAlphabeticalLimitAndOffset()
    {
        $actual = $this->sparql->queryConceptsAlphabetical('b', 'en', 2, 1);
        $expected = array(
          0 => array(
            'uri' => 'http://www.skosmos.skos/test/ta122',
            'localname' => 'ta122',
            'prefLabel' => 'Black sea bass',
            'lang' => 'en',
          ),
          1 => array(
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
     * @covers GenericSparql::transformAlphabeticalListResults
     */
    public function testQualifiedNotationAlphabeticalList()
    {
        $voc = $this->model->getVocabulary('test-qualified-notation');
        $res = new EasyRdf\Resource("http://www.w3.org/2004/02/skos/core#notation");
        $sparql = new GenericSparql($this->endpoint, $voc->getGraph(), $this->model);

        $actual = $sparql->queryConceptsAlphabetical("a", "en", null, null, null, false, $res);

        $expected = array(
          0 => array(
            'uri' => 'http://www.skosmos.skos/test/qn1',
            'localname' => 'qn1',
            'prefLabel' => 'A',
            'lang' => 'en',
            'qualifier' => 'A'
          ),
          1 => array(
            'uri' => 'http://www.skosmos.skos/test/qn1b',
            'localname' => 'qn1b',
            'prefLabel' => 'A',
            'lang' => 'en',
            'qualifier' => 'A'
          ),
          2 => array(
            'uri' => 'http://www.skosmos.skos/test/qn1c',
            'localname' => 'qn1c',
            'prefLabel' => 'A',
            'lang' => 'en',
          ),
        );
        $this->assertEquals($expected, $actual);

        $actual = $sparql->queryConceptsAlphabetical("b", "en", null, null, null, false, $res);

        $expected = array(
          0 => array(
            'uri' => 'http://www.skosmos.skos/test/qn2',
            'localname' => 'qn2',
            'prefLabel' => 'B',
            'lang' => 'en',
            'qualifier' => 'B'
          ),
          1 => array(
            'uri' => 'http://www.skosmos.skos/test/qn2',
            'localname' => 'qn2',
            'prefLabel' => 'B',
            'lang' => 'en',
            'qualifier' => 'C'
          ),
          2 => array(
            'uri' => 'http://www.skosmos.skos/test/qn2b',
            'localname' => 'qn2b',
            'prefLabel' => 'B',
            'lang' => 'en',
            'qualifier' => 'B'
          ),
          3 => array(
            'uri' => 'http://www.skosmos.skos/test/qn2b',
            'localname' => 'qn2b',
            'prefLabel' => 'B',
            'lang' => 'en',
            'qualifier' => 'C'
          ),
        );
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers JenaTextSparql::queryConceptsAlphabetical
     * @covers JenaTextSparql::generateAlphabeticalListQuery
     */
    public function testQualifiedBroaderAlphabeticalList()
    {
        $voc = $this->model->getVocabulary('test-qualified-broader');
        $res = new EasyRdf\Resource("http://www.w3.org/2004/02/skos/core#broader");
        $sparql = new JenaTextSparql($this->endpoint, $voc->getGraph(), $this->model);

        $actual = $sparql->queryConceptsAlphabetical("a", "en", null, null, null, false, $res);

        $expected = array(
          0 => array(
            'uri' => 'http://www.skosmos.skos/test/qb1',
            'localname' => 'qb1',
            'prefLabel' => 'A',
            'lang' => 'en',
          ),
        );
        $this->assertEquals($expected, $actual);

        $actual = $sparql->queryConceptsAlphabetical("b", "en", null, null, null, false, $res);

        $expected = array(
          0 => array(
            'uri' => 'http://www.skosmos.skos/test/qb2',
            'localname' => 'qb2',
            'prefLabel' => 'B',
            'lang' => 'en',
            'qualifier' => 'qb1'
          ),
        );
        $this->assertEquals($expected, $actual);

        $actual = $sparql->queryConceptsAlphabetical("c", "en", null, null, null, false, $res);

        $expected = array(
          0 => array(
            'uri' => 'http://www.skosmos.skos/test/qb3',
            'localname' => 'qb3',
            'prefLabel' => 'C',
            'lang' => 'en',
            'qualifier' => 'qb1'
          ),
          1 => array(
            'uri' => 'http://www.skosmos.skos/test/qb3',
            'localname' => 'qb3',
            'prefLabel' => 'C',
            'lang' => 'en',
            'qualifier' => 'qb2'
          ),
        );
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers JenaTextSparql::generateAlphabeticalListQuery
     */
    public function testQueryConceptsAlphabeticalNoResults()
    {
        $actual = $this->sparql->queryConceptsAlphabetical('x', 'en');
        $expected = array();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers JenaTextSparql::generateAlphabeticalListQuery
     */
    public function testQueryConceptsAlphabeticalMatchLanguage()
    {
        $actual = $this->sparql->queryConceptsAlphabetical('e', 'en');
        // there are two prefLabels starting with E, "Eel"@en and "Europa"@nb
        // checking that only the first one is returned
        $this->assertEquals(1, sizeof($actual));
        $this->assertEquals('Eel', $actual[0]['prefLabel']);
    }

    /**
     * @covers JenaTextSparql::generateAlphabeticalListQuery
     */
    public function testQueryConceptsAlphabeticalSpecialChars()
    {
        $actual = $this->sparql->queryConceptsAlphabetical('!*', 'en');
        $this->assertEquals(1, sizeof($actual));
        $this->assertEquals('-"special" character \\example\\', $actual[0]['prefLabel']);
    }

    /**
     * @covers JenaTextSparql::generateAlphabeticalListQuery
     */
    public function testQueryConceptsAlphabeticalNumbers()
    {
        $actual = $this->sparql->queryConceptsAlphabetical('0-9', 'en');
        $this->assertEquals(1, sizeof($actual));
        $this->assertStringContainsString("3D", $actual[0]['prefLabel']);
    }

    /**
     * @covers JenaTextSparql::generateAlphabeticalListQuery
     */
    public function testQueryConceptsAlphabeticalFull()
    {
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
     * @covers JenaTextSparql::generateConceptSearchQueryInner
     */
    public function testQueryConceptsMatchLanguage()
    {
        $voc = $this->model->getVocabulary('test');
        $this->params->method('getSearchTerm')->will($this->returnValue('e*'));
        $this->params->method('getVocabIds')->will($this->returnValue(array('test')));
        $this->params->method('getSearchLang')->will($this->returnValue('en'));
        $actual = $this->sparql->queryConcepts(array($voc), null, null, $this->params);
        $this->assertEquals(1, sizeof($actual));
        $this->assertEquals('Eel', $actual[0]['prefLabel']);
    }

    /**
     * @covers JenaTextSparql::createTextQueryCondition
     * @covers JenaTextSparql::generateConceptSearchQueryCondition
     * @covers JenaTextSparql::generateConceptSearchQueryInner
     */
    public function testQueryConceptsWithNotation()
    {
        $this->params->method('getSearchTerm')->will($this->returnValue('12*'));
        $this->params->method('getVocabs')->will($this->returnValue(array($this->model->getVocabulary('test'))));
        $actual = $this->sparql->queryConcepts(array($this->vocab), null, null, $this->params);
        $this->assertEquals(1, sizeof($actual));
        $this->assertEquals('Europa', $actual[0]['prefLabel']);
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
        foreach($actual as $match) {
            $this->assertStringContainsStringIgnoringCase('bass', $match['prefLabel']);
        }
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
    public function testQueryConceptsAlphabeticalOrderBy()
    {
        $this->markTestSkipped("disabled because ARQ collation doesn't work in dockerized Fuseki (Jena issue #1998)");
        $vocab = $this->model->getVocabulary('collation');
        $graph = $vocab->getGraph();
        $sparql = new JenaTextSparql($this->endpoint, $graph, $this->model);
        $actual = $sparql->queryConceptsAlphabetical('t', 'fi');
        $expected = array(
          0 => array(
            'uri' => 'http://www.skosmos.skos/collation/val1',
            'localname' => 'val1',
            'prefLabel' => 'tšekin kieli',
            'lang' => 'fi',
          ),
          1 => array(
            'uri' => 'http://www.skosmos.skos/collation/val2',
            'localname' => 'val2',
            'prefLabel' => 'töyhtöhyyppä',
            'lang' => 'fi',
          )
        );
        $this->assertEquals($expected, $actual);
    }

}
