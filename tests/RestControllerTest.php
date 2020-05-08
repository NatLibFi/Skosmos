<?php

require_once('model/GlobalConfig.php');
require_once('model/Model.php');
require_once('model/Request.php');
require_once('controller/RestController.php');

class RestControllerTest extends \PHPUnit\Framework\TestCase
{
  /**
   * @var Model
   */
  private $model;
  /**
   * @var RestController
   */
  private $controller;
  protected function setUp() {
    putenv("LANGUAGE=en_GB.utf8");
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    bindtextdomain('skosmos', 'resource/translations');
    bind_textdomain_codeset('skosmos', 'UTF-8');
    textdomain('skosmos');

    $globalConfig = new GlobalConfig('/../tests/testconfig.ttl');
    $this->model = Mockery::mock(new Model($globalConfig));
    $this->controller = new RestController($this->model);
  }

  protected function tearDown() {
    ob_clean();
  }

  /**
   * @covers RestController::data
   */
  public function testDataAsJson() {
    $request = new Request($this->model);
    $request->setQueryParam('format', 'application/json');
    $request->setURI('http://www.skosmos.skos/test/ta117');
    $request->setVocab("test");
    $this->controller->data($request);

    $out = $this->getActualOutput();

    $this->assertJsonStringEqualsJsonString('{"@context":{"skos":"http://www.w3.org/2004/02/skos/core#","isothes":"http://purl.org/iso25964/skos-thes#","rdfs":"http://www.w3.org/2000/01/rdf-schema#","owl":"http://www.w3.org/2002/07/owl#","dct":"http://purl.org/dc/terms/","dc11":"http://purl.org/dc/elements/1.1/","uri":"@id","type":"@type","lang":"@language","value":"@value","graph":"@graph","label":"rdfs:label","prefLabel":"skos:prefLabel","altLabel":"skos:altLabel","hiddenLabel":"skos:hiddenLabel","broader":"skos:broader","narrower":"skos:narrower","related":"skos:related","inScheme":"skos:inScheme","exactMatch":"skos:exactMatch","closeMatch":"skos:closeMatch","broadMatch":"skos:broadMatch","narrowMatch":"skos:narrowMatch","relatedMatch":"skos:relatedMatch"},"graph":[{"uri":"http://www.skosmos.skos/test-meta/TestClass","type":"owl:Class","label":{"lang":"en","value":"Test class"}},{"uri":"http://www.skosmos.skos/test/conceptscheme","type":"skos:ConceptScheme","label":{"lang":"en","value":"Test conceptscheme"}},{"uri":"http://www.skosmos.skos/test/ta1","type":["http://www.skosmos.skos/test-meta/TestClass","skos:Concept"],"narrower":{"uri":"http://www.skosmos.skos/test/ta117"},"prefLabel":{"lang":"en","value":"Fish"}},{"uri":"http://www.skosmos.skos/test/ta115","type":["http://www.skosmos.skos/test-meta/TestClass","skos:Concept"],"prefLabel":{"lang":"en","value":"Eel"}},{"uri":"http://www.skosmos.skos/test/ta117","type":["http://www.skosmos.skos/test-meta/TestClass","skos:Concept"],"broader":{"uri":"http://www.skosmos.skos/test/ta1"},"inScheme":{"uri":"http://www.skosmos.skos/test/conceptscheme"},"prefLabel":{"lang":"en","value":"3D Bass"},"relatedMatch":{"uri":"http://www.skosmos.skos/test/ta115"}},{"uri":"skos:broader","label":{"lang":"en","value":"has broader"}},{"uri":"skos:prefLabel","label":{"lang":"en","value":"preferred label"}}]}', $out);
  }

  /**
   * @covers RestController::search
   */
  public function testSearchJsonLd() {
    $request = new Request($this->model);
    $request->setQueryParam('format', 'application/json');
    $request->setQueryParam('query', '*bass');
    $this->controller->search($request);

    $out = $this->getActualOutput();

    $this->assertJsonStringEqualsJsonString('{"@context":{"skos":"http:\/\/www.w3.org\/2004\/02\/skos\/core#","isothes":"http:\/\/purl.org\/iso25964\/skos-thes#","onki":"http:\/\/schema.onki.fi\/onki#","uri":"@id","type":"@type","results":{"@id":"onki:results","@container":"@list"},"prefLabel":"skos:prefLabel","altLabel":"skos:altLabel","hiddenLabel":"skos:hiddenLabel"},"uri":"","results":[{"uri":"http:\/\/www.skosmos.skos\/test\/ta117","type":["skos:Concept","meta:TestClass"],"prefLabel":"3D Bass","lang":"en","vocab":"test"},{"uri":"http:\/\/www.skosmos.skos\/test\/ta116","type":["skos:Concept","meta:TestClass"],"prefLabel":"Bass","lang":"en","vocab":"test"},{"uri":"http:\/\/www.skosmos.skos\/test\/ta122","type":["skos:Concept","meta:TestClass"],"prefLabel":"Black sea bass","lang":"en","vocab":"test"}]}', $out);
  }

  /**
   * @covers RestController::search
   */
  public function testSearchJsonLdWithAdditionalFields() {
    $request = new Request($this->model);
    $request->setQueryParam('format', 'application/json');
    $request->setQueryParam('query', '*bass');
    $request->setQueryParam('fields', 'broader relatedMatch');
    $this->controller->search($request);

    $out = $this->getActualOutput();

    $this->assertJsonStringEqualsJsonString('{"@context":{"skos":"http:\/\/www.w3.org\/2004\/02\/skos\/core#","isothes":"http:\/\/purl.org\/iso25964\/skos-thes#","onki":"http:\/\/schema.onki.fi\/onki#","uri":"@id","type":"@type","results":{"@id":"onki:results","@container":"@list"},"prefLabel":"skos:prefLabel","altLabel":"skos:altLabel","hiddenLabel":"skos:hiddenLabel","broader":"skos:broader","relatedMatch":"skos:relatedMatch"},"uri":"","results":[{"uri":"http:\/\/www.skosmos.skos\/test\/ta117","type":["skos:Concept","meta:TestClass"],"broader":[{"uri":"http:\/\/www.skosmos.skos\/test\/ta1"}],"relatedMatch":[{"uri":"http:\/\/www.skosmos.skos\/test\/ta115"}],"prefLabel":"3D Bass","lang":"en","vocab":"test"},{"uri":"http:\/\/www.skosmos.skos\/test\/ta116","type":["skos:Concept","meta:TestClass"],"broader":[{"uri":"http:\/\/www.skosmos.skos\/test\/ta1"}],"prefLabel":"Bass","lang":"en","vocab":"test"},{"uri":"http:\/\/www.skosmos.skos\/test\/ta122","type":["skos:Concept","meta:TestClass"],"broader":[{"uri":"http:\/\/www.skosmos.skos\/test\/ta116"}],"prefLabel":"Black sea bass","lang":"en","vocab":"test"}]}', $out);
  }

  /**
   * @covers RestController::indexLetters
   */
  public function testIndexLettersJsonLd() {
    $request = new Request($this->model);
    $request->setVocab('test');
    $request->setLang('en');
    $this->controller->indexLetters($request);

    $out = $this->getActualOutput();

    $this->assertJsonStringEqualsJsonString('{"@context":{"skos":"http:\/\/www.w3.org\/2004\/02\/skos\/core#","uri":"@id","type":"@type","indexLetters":{"@id":"skosmos:indexLetters","@container":"@list","@language":"en"}},"uri":"","indexLetters":["B","C","E","F","M","T","!*","0-9"]}', $out);
  }

  /**
   * @covers RestController::indexConcepts
   */
  public function testIndexConceptsJsonLd() {
    $request = new Request($this->model);
    $request->setVocab('test');
    $request->setLang('en');
    $this->controller->indexConcepts("B", $request);

    $out = $this->getActualOutput();

    $this->assertJsonStringEqualsJsonString('{"@context":{"skos":"http:\/\/www.w3.org\/2004\/02\/skos\/core#","uri":"@id","type":"@type","indexConcepts":{"@id":"skosmos:indexConcepts","@container":"@list"}},"uri":"","indexConcepts":[{"uri":"http:\/\/www.skosmos.skos\/test\/ta116","localname":"ta116","prefLabel":"Bass","lang":"en"},{"uri":"http:\/\/www.skosmos.skos\/test\/ta122","localname":"ta122","prefLabel":"Black sea bass","lang":"en"},{"uri":"http:\/\/www.skosmos.skos\/test\/ta114","localname":"ta114","prefLabel":"Buri","lang":"en"}]}', $out);
  }

  /**
   * @covers RestController::indexConcepts
   */
  public function testIndexConceptsJsonLdLimit() {
    $request = new Request($this->model);
    $request->setVocab('test');
    $request->setLang('en');
    $request->setQueryParam('limit', '2');
    $this->controller->indexConcepts("B", $request);

    $out = $this->getActualOutput();

    $this->assertJsonStringEqualsJsonString('{"@context":{"skos":"http:\/\/www.w3.org\/2004\/02\/skos\/core#","uri":"@id","type":"@type","indexConcepts":{"@id":"skosmos:indexConcepts","@container":"@list"}},"uri":"","indexConcepts":[{"uri":"http:\/\/www.skosmos.skos\/test\/ta116","localname":"ta116","prefLabel":"Bass","lang":"en"},{"uri":"http:\/\/www.skosmos.skos\/test\/ta122","localname":"ta122","prefLabel":"Black sea bass","lang":"en"}]}', $out);
  }

  /**
   * @covers RestController::indexConcepts
   */
  public function testIndexConceptsJsonLdOffset() {
    $request = new Request($this->model);
    $request->setVocab('test');
    $request->setLang('en');
    $request->setQueryParam('offset', '1');
    $this->controller->indexConcepts("B", $request);

    $out = $this->getActualOutput();

    $this->assertJsonStringEqualsJsonString('{"@context":{"skos":"http:\/\/www.w3.org\/2004\/02\/skos\/core#","uri":"@id","type":"@type","indexConcepts":{"@id":"skosmos:indexConcepts","@container":"@list"}},"uri":"","indexConcepts":[{"uri":"http:\/\/www.skosmos.skos\/test\/ta122","localname":"ta122","prefLabel":"Black sea bass","lang":"en"},{"uri":"http:\/\/www.skosmos.skos\/test\/ta114","localname":"ta114","prefLabel":"Buri","lang":"en"}]}', $out);
  }

  /**
   * @covers RestController::indexConcepts
   */
  public function testIndexConceptsJsonLdLimitOffset() {
    $request = new Request($this->model);
    $request->setVocab('test');
    $request->setLang('en');
    $request->setQueryParam('limit', '1');
    $request->setQueryParam('offset', '1');
    $this->controller->indexConcepts("B", $request);

    $out = $this->getActualOutput();

    $this->assertJsonStringEqualsJsonString('{"@context":{"skos":"http:\/\/www.w3.org\/2004\/02\/skos\/core#","uri":"@id","type":"@type","indexConcepts":{"@id":"skosmos:indexConcepts","@container":"@list"}},"uri":"","indexConcepts":[{"uri":"http:\/\/www.skosmos.skos\/test\/ta122","localname":"ta122","prefLabel":"Black sea bass","lang":"en"}]}', $out);
  }

   /**
   * @covers RestController::label
   */
  public function testLabelOnePrefOneAltLabel() {
      $request = new Request($this->model);
      $request->setQueryParam('format', 'application/json');
      $request->setURI('http://www.skosmos.skos/test/ta112');
      $request->setVocab('test');
      $request->setLang('en');

      $this->controller->label($request);
      $out = $this->getActualOutput();

      $expected = <<<EOD
 {"@context": {
         "skos": "http://www.w3.org/2004/02/skos/core#",
         "uri": "@id",
         "type": "@type",
         "prefLabel": "skos:prefLabel",
         "altLabel": "skos:altLabel",
        "hiddenLabel": "skos:hiddenLabel",
        "@language": "en"
     },
    "uri": "http://www.skosmos.skos/test/ta112",
    "prefLabel": "Carp",
    "altLabel": [
        "Golden crucian"
     ]
 }
EOD;
      $this->assertJsonStringEqualsJsonString($expected, $out);
  }

   /**
   * @covers RestController::label
   */
  public function testLabelGlobal() {
      $request = new Request($this->model);
      $request->setQueryParam('format', 'application/json');
      $request->setURI('http://www.skosmos.skos/test/ta112');
      $request->setLang('en');

      $this->controller->label($request);
      $out = $this->getActualOutput();

      $expected = <<<EOD
 {"@context": {
         "skos": "http://www.w3.org/2004/02/skos/core#",
         "uri": "@id",
         "type": "@type",
         "prefLabel": "skos:prefLabel",
         "altLabel": "skos:altLabel",
        "hiddenLabel": "skos:hiddenLabel",
        "@language": "en"
     },
    "uri": "http://www.skosmos.skos/test/ta112",
    "prefLabel": "Carp",
    "altLabel": [
        "Golden crucian"
     ]
 }
EOD;
      $this->assertJsonStringEqualsJsonString($expected, $out);
  }

   /**
   * @covers RestController::label
   */
  public function testLabelGlobalNonexistentVocab() {
      $request = new Request($this->model);
      $request->setQueryParam('format', 'application/json');
      $request->setURI('http://www.skosmos.skos/nonexistent/vocab');
      $request->setLang('en');

      $this->controller->label($request);
      $out = $this->getActualOutput();

      $expected = "404 Not Found : Could not find concept <http://www.skosmos.skos/nonexistent/vocab>";
      $this->assertEquals($expected, $out);
  }

  /**
   * @covers RestController::label
   */
  public function testLabelOnePrefOneHiddenLabel() {
      $request = new Request($this->model);
      $request->setQueryParam('format', 'application/json');
      $request->setURI('http://www.skosmos.skos/test/ta112');
      $request->setVocab('test');
      $request->setLang('fi');

      $this->controller->label($request);
      $out = $this->getActualOutput();

      $expected = <<<EOD
 {"@context": {
         "skos": "http://www.w3.org/2004/02/skos/core#",
         "uri": "@id",
         "type": "@type",
         "prefLabel": "skos:prefLabel",
         "altLabel": "skos:altLabel",
        "hiddenLabel": "skos:hiddenLabel",
        "@language": "fi"
     },
    "uri": "http://www.skosmos.skos/test/ta112",
    "prefLabel": "Karppi",
    "hiddenLabel": [
        "Karpit"
     ]
 }
EOD;
      $this->assertJsonStringEqualsJsonString($expected, $out);
  }

  /**
   * @covers RestController::label
   */
  public function testLabelOnePrefLabel() {
      $request = new Request($this->model);
      $request->setQueryParam('format', 'application/json');
      $request->setURI('http://www.skosmos.skos/test/ta111');
      $request->setVocab('test');
      $request->setLang('en');

      $this->controller->label($request);
      $out = $this->getActualOutput();

      $expected = <<<EOD
 {"@context": {
         "skos": "http://www.w3.org/2004/02/skos/core#",
         "uri": "@id",
         "type": "@type",
         "prefLabel": "skos:prefLabel",
         "altLabel": "skos:altLabel",
        "hiddenLabel": "skos:hiddenLabel",
        "@language": "en"
     },
    "uri": "http://www.skosmos.skos/test/ta111",
    "prefLabel": "Tuna"
 }
EOD;
      $this->assertJsonStringEqualsJsonString($expected, $out);
  }

  /**
   * @covers RestController::label
   */
  public function testLabelNoPrefLabel() {
      $request = new Request($this->model);
      $request->setQueryParam('format', 'application/json');
      $request->setURI('http://www.skosmos.skos/test/ta111');
      $request->setVocab('test');
      $request->setLang('sv');

      $this->controller->label($request);
      $out = $this->getActualOutput();

      $expected = <<<EOD
 {"@context": {
         "skos": "http://www.w3.org/2004/02/skos/core#",
         "uri": "@id",
         "type": "@type",
         "prefLabel": "skos:prefLabel",
         "altLabel": "skos:altLabel",
        "hiddenLabel": "skos:hiddenLabel",
        "@language": "sv"
     },
    "uri": "http://www.skosmos.skos/test/ta111"
 }
EOD;
      $this->assertJsonStringEqualsJsonString($expected, $out);
  }

  /**
   * @covers RestController::label
   */
  public function testLabelNoConcept() {
      $request = new Request($this->model);
      $request->setQueryParam('format', 'application/json');
      $request->setURI('http://www.skosmos.skos/test/nonexistent');
      $request->setVocab('test');
      $request->setLang('en');

      $this->controller->label($request);
      $out = $this->getActualOutput();

      $expected = "404 Not Found : Could not find concept <http://www.skosmos.skos/test/nonexistent>";
      $this->assertEquals($expected, $out);
  }

}
