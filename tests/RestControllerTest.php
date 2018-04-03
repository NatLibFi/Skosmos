<?php

require_once('model/GlobalConfig.php');
require_once('model/Model.php');
require_once('model/Request.php');
require_once('controller/RestController.php');

class RestControllerTest extends \PHPUnit\Framework\TestCase
{
  protected function setUp() {
    require_once 'testconfig.inc';
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    bindtextdomain('skosmos', 'resource/translations');
    bind_textdomain_codeset('skosmos', 'UTF-8');
    textdomain('skosmos');

    $this->model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $this->controller = new RestController($this->model);
  }

  /**
   * @covers RestController::data
   */
  public function testDataAsJson() {
    $request = new Request($this->model);
    $request->setQueryParam('format', 'application/json');
    $request->setURI('http://www.skosmos.skos/test/ta117');
    $this->controller->data($request);

    $out = $this->getActualOutput();

    $this->assertJsonStringEqualsJsonString('{"@context":{"skos":"http://www.w3.org/2004/02/skos/core#","isothes":"http://purl.org/iso25964/skos-thes#","rdfs":"http://www.w3.org/2000/01/rdf-schema#","owl":"http://www.w3.org/2002/07/owl#","dct":"http://purl.org/dc/terms/","dc11":"http://purl.org/dc/elements/1.1/","uri":"@id","type":"@type","lang":"@language","value":"@value","graph":"@graph","label":"rdfs:label","prefLabel":"skos:prefLabel","altLabel":"skos:altLabel","hiddenLabel":"skos:hiddenLabel","broader":"skos:broader","narrower":"skos:narrower","related":"skos:related","inScheme":"skos:inScheme","exactMatch":"skos:exactMatch","closeMatch":"skos:closeMatch","broadMatch":"skos:broadMatch","narrowMatch":"skos:narrowMatch","relatedMatch":"skos:relatedMatch"},"graph":[{"uri":"http://www.skosmos.skos/test-meta/TestClass","type":"owl:Class","label":{"lang":"en","value":"Test class"}},{"uri":"http://www.skosmos.skos/test/conceptscheme","type":"skos:ConceptScheme","label":{"lang":"en","value":"Test conceptscheme"}},{"uri":"http://www.skosmos.skos/test/ta1","type":["http://www.skosmos.skos/test-meta/TestClass","skos:Concept"],"narrower":{"uri":"http://www.skosmos.skos/test/ta117"},"prefLabel":{"lang":"en","value":"Fish"}},{"uri":"http://www.skosmos.skos/test/ta115","type":["http://www.skosmos.skos/test-meta/TestClass","skos:Concept"],"prefLabel":{"lang":"en","value":"Eel"}},{"uri":"http://www.skosmos.skos/test/ta117","type":["http://www.skosmos.skos/test-meta/TestClass","skos:Concept"],"broader":{"uri":"http://www.skosmos.skos/test/ta1"},"inScheme":{"uri":"http://www.skosmos.skos/test/conceptscheme"},"prefLabel":{"lang":"en","value":"3D Bass"},"relatedMatch":{"uri":"http://www.skosmos.skos/test/ta115"}},{"uri":"skos:broader","label":{"lang":"en","value":"has broader"}},{"uri":"skos:prefLabel","label":{"lang":"en","value":"preferred label"}}]}', $out);
  }
}