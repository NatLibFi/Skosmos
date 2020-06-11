<?php

class VocabularyConfigTest extends PHPUnit\Framework\TestCase
{
  /** @var Model */
  private $model;

  /**
   * @covers VocabularyConfig::getConfig
   * @throws Exception
   */
  protected function setUp() {
    putenv("LANGUAGE=en_GB.utf8");
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    $this->model = new Model(new GlobalConfig('/../tests/testconfig.ttl'));
    $this->assertNotNull($this->model->getVocabulary('test')->getConfig()->getPlugins(), "The PluginRegister of the model was not initialized!");
  }

  /**
   * @covers VocabularyConfig::getIndexClasses
   */
  public function testGetIndexClassesNotSet() {
    $vocab = $this->model->getVocabulary('test');
    $actual = $vocab->getConfig()->getIndexClasses();
    $this->assertEquals(array(), $actual);
  }

  /**
   * @covers VocabularyConfig::getIndexClasses
   */
  public function testGetIndexClasses() {
    $vocab = $this->model->getVocabulary('groups');
    $actual = $vocab->getConfig()->getIndexClasses();
    $expected = array('http://www.skosmos.skos/test-meta/TestClass','http://www.skosmos.skos/test-meta/TestClass2');
    $this->assertEquals($expected, $actual);
  }

  /**
   * @covers VocabularyConfig::getLanguages
   */
  public function testGetLanguages() {
    $vocab = $this->model->getVocabulary('testdiff');
    $langs = $vocab->getConfig()->getLanguages();
    $this->assertEquals(2, sizeof($langs));
  }

  /**
   * @covers VocabularyConfig::getFeedbackRecipient
   */
  public function testGetFeedbackRecipient() {
    $vocab = $this->model->getVocabulary('test');
    $email = $vocab->getConfig()->getFeedbackRecipient();
    $this->assertEquals('developer@vocabulary.org', $email);
  }

  /**
   * @covers VocabularyConfig::getExternalResourcesLoading
   */
  public function testGetExternalResourcesLoadingWhenNotSet() {
    $vocab = $this->model->getVocabulary('test');
    $external = $vocab->getConfig()->getExternalResourcesLoading();
    $this->assertTrue($external);
  }

  /**
   * @covers VocabularyConfig::getDefaultSidebarView
   */
  public function testGetDefaultSidebarView() {
    $vocab = $this->model->getVocabulary('testdiff');
    $default = $vocab->getConfig()->getDefaultSidebarView();
    $this->assertEquals('groups', $default);
  }

  /**
   * @covers VocabularyConfig::getDefaultSidebarView
   */
  public function testGetDefaultSidebarViewWhenNotSet() {
    $vocab = $this->model->getVocabulary('test');
    $default = $vocab->getConfig()->getDefaultSidebarView();
    $this->assertEquals('alphabetical', $default);
  }

  /**
   * @covers VocabularyConfig::getShowLangCodes
   */
  public function testGetShowLangCodesWhenSetToTrue() {
    $vocab = $this->model->getVocabulary('testdiff');
    $codes = $vocab->getConfig()->getShowLangCodes();
    $this->assertTrue($codes);
  }

  /**
   * @covers VocabularyConfig::getShowLangCodes
   */
  public function testGetShowLangCodesWhenNotSet() {
    $vocab = $this->model->getVocabulary('test');
    $codes = $vocab->getConfig()->getShowLangCodes();
    $this->assertFalse($codes);
  }

  /**
   * @covers VocabularyConfig::getDefaultLanguage
   * @covers VocabularyConfig::getLiteral
   */
  public function testGetDefaultLanguage() {
    $vocab = $this->model->getVocabulary('test');
    $lang = $vocab->getConfig()->getDefaultLanguage();
    $this->assertEquals('en', $lang);
  }

  /**
   * @covers VocabularyConfig::getDefaultLanguage
   * @expectedException PHPUnit\Framework\Error\Error
   */
  public function testGetDefaultLanguageWhenNotSet() {
    $vocab = $this->model->getVocabulary('testdiff');
    $lang = $vocab->getConfig()->getDefaultLanguage();
  }

  /**
   * @covers VocabularyConfig::getAlphabeticalFull
   */
  public function testGetFullAlphabeticalIndex() {
    $vocab = $this->model->getVocabulary('testdiff');
    $boolean = $vocab->getConfig()->getAlphabeticalFull();
    $this->assertEquals(true, $boolean);
  }

  /**
   * @covers VocabularyConfig::getAlphabeticalFull
   */
  public function testGetFullAlphabeticalIndexWhenNotSet() {
    $vocab = $this->model->getVocabulary('test');
    $boolean = $vocab->getConfig()->getAlphabeticalFull();
    $this->assertEquals(false, $boolean);
  }

  /**
   * @covers VocabularyConfig::getShortName
   * @covers VocabularyConfig::getLiteral
   */
  public function testGetShortName() {
    $vocab = $this->model->getVocabulary('test');
    $name = $vocab->getConfig()->getShortName();
    $this->assertEquals('Test short', $name);
  }

  /**
   * @covers VocabularyConfig::getShortName
   */
  public function testGetShortNameNotDefined() {
    $vocab = $this->model->getVocabulary('testdiff');
    $name = $vocab->getConfig()->getShortName();
    $this->assertEquals('testdiff', $name);
  }

  /**
   * @covers VocabularyConfig::getDataURLs
   */
  public function testGetDataURLs() {
    $vocab = $this->model->getVocabulary('groups');
    $url = $vocab->getConfig()->getDataURLs();
    ksort($url); // sort by mime type to make order deterministic
    $this->assertEquals(array(
        'application/rdf+xml' => 'http://skosmos.skos/dump/test/groups',
        'text/turtle' => 'http://skosmos.skos/dump/test/groups.ttl',
      ), $url);
  }

  /**
   * @covers VocabularyConfig::getDataURLs
   * @expectedException PHPUnit\Framework\Error\Warning
   */
  public function testGetDataURLsNotGuessable() {
    $vocab = $this->model->getVocabulary('test');
    $url = $vocab->getConfig()->getDataURLs();
  }

  /**
   * @covers VocabularyConfig::getDataURLs
   */
  public function testGetDataURLsNotSet() {
    $vocab = $this->model->getVocabulary('testdiff');
    $url = $vocab->getConfig()->getDataURLs();
    $this->assertEquals(array(), $url);
  }

  /**
   * @covers VocabularyConfig::getDataURLs
   */
  public function testGetDataURLsMarc() {
    $vocab = $this->model->getVocabulary('test-marc');
    $url = $vocab->getConfig()->getDataURLs();
    $marcArray = $url['application/marcxml+xml'];
    $this->assertEquals( ( in_array('http://skosmos.skos/dump/test/marc-fi.mrcx', $marcArray) &&
                           in_array('http://skosmos.skos/dump/test/marc-sv.mrcx', $marcArray) ), true);
  }

  /**
   * @covers VocabularyConfig::getDataURLs
   * @expectedException PHPUnit\Framework\Error\Warning
   */
  public function testGetDataURLsMarcNotDefined() {
    $vocab = $this->model->getVocabulary('marc-undefined');
    $url = $vocab->getConfig()->getDataURLs();
    $this->assertEquals(array(), $url);
  }

  /**
   * @covers VocabularyConfig::getGroupClassURI
   */
  public function testGetGroupClassURI() {
    $vocab = $this->model->getVocabulary('test');
    $uri = $vocab->getConfig()->getGroupClassURI();
    $this->assertEquals('http://www.w3.org/2004/02/skos/core#Collection', $uri);
  }

  /**
   * @covers VocabularyConfig::getGroupClassURI
   */
  public function testGetGroupClassURINotSet() {
    $vocab = $this->model->getVocabulary('testdiff');
    $uri = $vocab->getConfig()->getGroupClassURI();
    $this->assertEquals(null, $uri);
  }

  /**
   * @covers VocabularyConfig::getArrayClassURI
   */
  public function testGetArrayClassURI() {
    $vocab = $this->model->getVocabulary('test');
    $uri = $vocab->getConfig()->getArrayClassURI();
    $this->assertEquals('http://purl.org/iso25964/skos-thes#ThesaurusArray', $uri);
  }

  /**
   * @covers VocabularyConfig::getArrayClassURI
   */
  public function testGetArrayClassURINotSet() {
    $vocab = $this->model->getVocabulary('testdiff');
    $uri = $vocab->getConfig()->getArrayClassURI();
    $this->assertEquals(null, $uri);
  }

  /**
   * @covers VocabularyConfig::getShowHierarchy
   */
  public function testGetShowHierarchy() {
    $vocab = $this->model->getVocabulary('test');
    $uri = $vocab->getConfig()->getShowHierarchy();
    $this->assertEquals(true, $uri);
  }


  /**
   * @covers VocabularyConfig::getShowHierarchy
   */
  public function testGetShowHierarchySetToFalse() {
    $vocab = $this->model->getVocabulary('testdiff');
    $uri = $vocab->getConfig()->getShowHierarchy();
    $this->assertEquals(false, $uri);
  }

  /**
   * @covers VocabularyConfig::getShowHierarchy
   */
  public function testGetShowHierarchyNotSet() {
    $vocab = $this->model->getVocabulary('groups');
    $uri = $vocab->getConfig()->getShowHierarchy();
    $this->assertEquals(false, $uri);
  }

  /**
   * @covers VocabularyConfig::getAdditionalSearchProperties
   */
  public function testGetAdditionalSearchProperties() {
    $vocab = $this->model->getVocabulary('dates');
    $prop = $vocab->getConfig()->getAdditionalSearchProperties();
    $this->assertEquals('skos:exactMatch', $prop[0]);
  }

  /**
   * @covers VocabularyConfig::hasMultiLingualProperty
   */
  public function testHasMultiLingualProperty() {
    $vocab = $this->model->getVocabulary('dates');
    $this->assertEquals(true, $vocab->getConfig()->hasMultiLingualProperty('skos:altLabel'));
  }

  /**
   * @covers VocabularyConfig::hasMultiLingualProperty
   */
  public function testHasMultiLingualPropertyWhenItIsNot() {
    $vocab = $this->model->getVocabulary('dates');
    $this->assertEquals(false, $vocab->getConfig()->hasMultiLingualProperty('skos:exactMatch'));
  }

  /**
   * @covers VocabularyConfig::getTitle
   */
  public function testGetTitle() {
    $vocab = $this->model->getVocabulary('test');
    $this->assertEquals('Test ontology', $vocab->getConfig()->getTitle('en'));
  }

  /**
   * @covers VocabularyConfig::showChangeList
   * @covers VocabularyConfig::getBoolean
   */
  public function testShowChangeListDefaultValue() {
    $vocab = $this->model->getVocabulary('test');
    $this->assertEquals(false, $vocab->getConfig()->showChangeList());
  }

  /**
   * @covers VocabularyConfig::sortByNotation
   * @covers VocabularyConfig::getBoolean
   */
  public function testShowSortByNotationDefaultValue() {
    $vocab = $this->model->getVocabulary('test');
    $this->assertEquals(false, $vocab->getConfig()->sortByNotation());
  }

  /**
   * @covers VocabularyConfig::searchByNotation
   * @covers VocabularyConfig::getBoolean
   */
  public function testShowSearchByNotationDefaultValue() {
      $vocab = $this->model->getVocabulary('test');
      $this->assertEquals(false, $vocab->getConfig()->searchByNotation());
  }

  /**
   * @covers VocabularyConfig::searchByNotation
   * @covers VocabularyConfig::getBoolean
   */
  public function testShowSearchByNotationValue() {
      $vocab = $this->model->getVocabulary('testNotation');
      $this->assertEquals(true, $vocab->getConfig()->searchByNotation());
  }

  /**
   * @covers VocabularyConfig::showConceptSchemesInHierarchy
   * @covers VocabularyConfig::getBoolean
   */
  public function testShowConceptSchemesInHierarchy() {
    $vocab = $this->model->getVocabulary('test');
    $this->assertEquals(false, $vocab->getConfig()->showConceptSchemesInHierarchy());
  }

  /**
   * @covers VocabularyConfig::getShowStatistics
   * @covers VocabularyConfig::getBoolean
   */
  public function testShowStatisticsDefaultValue() {
    $vocab = $this->model->getVocabulary('test');
    $this->assertEquals(true, $vocab->getConfig()->getShowStatistics());
  }

  /**
   * @covers VocabularyConfig::getId
   */
  public function testGetIdWithSlashNamespace() {
    $mockres = $this->getMockBuilder('EasyRdf\Resource')->disableOriginalConstructor()->getMock();
    $mockres->method('getUri')->will($this->returnValue('http://www.skosmos.skos/onto/test'));
    $conf = new VocabularyConfig($mockres);
    $this->assertEquals('test', $conf->getId());
  }

  /**
   * @covers VocabularyConfig::getHierarchyProperty
   */
  public function testGetHierarchyPropertyDefaultValue() {
    $vocab = $this->model->getVocabulary('test');
    $this->assertEquals(array('skos:broader'), $vocab->getConfig()->getHierarchyProperty());
  }

  /**
   * @covers VocabularyConfig::getHierarchyProperty
   */
  public function testGetHierarchyProperty() {
    $vocab = $this->model->getVocabulary('testdiff');
    $this->assertEquals(array('isothes:broaderGeneric'), $vocab->getConfig()->getHierarchyProperty());
  }

  /**
   * @covers VocabularyConfig::getTypes
   */
  public function testGetTypes() {
    $vocab = $this->model->getVocabulary('test');
    $this->assertEquals(array(0 => array('uri' => 'http://publications.europa.eu/resource/authority/dataset-type/ONTOLOGY', 'prefLabel' => 'Ontology')), $vocab->getConfig()->getTypes('en'));
  }

  /**
   * @covers VocabularyConfig::getShowDeprecated
   */
  public function testShowDeprecated() {
      $vocab = $this->model->getVocabulary('showDeprecated');
      $this->assertEquals(true, $vocab->getConfig()->getShowDeprecated());
  }

  /**
   * @covers VocabularyConfig::getTypes
   */
  public function testGetTypesWhenNotSet() {
    $vocab = $this->model->getVocabulary('testdiff');
    $this->assertEquals(array(), $vocab->getConfig()->getTypes('en'));
  }

  /**
   * @covers VocabularyConfig::getLanguageOrder
   */
  public function testGetLanguageOrderNotSet() {
    $vocab = $this->model->getVocabulary('test');
    $this->assertEquals(array('en'), $vocab->getConfig()->getLanguageOrder('en'));
  }

  /**
   * @covers VocabularyConfig::getLanguageOrder
   */
  public function testGetLanguageOrder() {
    $vocab = $this->model->getVocabulary('subtag');
    $this->assertEquals(array('en', 'fr', 'de', 'sv'), $vocab->getConfig()->getLanguageOrder('en'));
    $this->assertEquals(array('fi', 'fr', 'de', 'sv', 'en'), $vocab->getConfig()->getLanguageOrder('fi'));
  }

  /**
   * @covers VocabularyConfig::showAlphabeticalIndex
   */
  public function testShowAlphabeticalIndex() {
    $vocab = $this->model->getVocabulary('testdiff');
    $this->assertTrue($vocab->getConfig()->showAlphabeticalIndex());
  }

  /**
   * @covers VocabularyConfig::showNotation
   */
  public function testShowNotation() {
    $vocab = $this->model->getVocabulary('test');
    $this->assertTrue($vocab->getConfig()->showNotation());
  }

  /**
   * @covers VocabularyConfig::getId
   */
  public function testGetId() {
    $vocab = $this->model->getVocabulary('testdiff');
    $this->assertEquals('testdiff' , $vocab->getConfig()->getId());
  }

  /**
   * @covers VocabularyConfig::getMainConceptSchemeURI
   */
  public function testGetMainConceptSchemeURI() {
    $vocab = $this->model->getVocabulary('testdiff');
    $this->assertEquals('http://www.skosmos.skos/testdiff#conceptscheme' , $vocab->getConfig()->getMainConceptSchemeURI());
    $vocab = $this->model->getVocabulary('test');
    $this->assertNull($vocab->getConfig()->getMainConceptSchemeURI());
  }

  /**
   * @covers VocabularyConfig::getExtProperties
   */
  public function testGetExtProperties() {
    $vocab = $this->model->getVocabulary('cbd');
    $this->assertEquals(4 , count($vocab->getConfig()->getExtProperties()));
  }

  /**
   * @covers VocabularyConfig::getMarcSourceCode
   */
  public function testGetMarcSourceCode() {
    $vocab = $this->model->getVocabulary('test');
    $this->assertEquals("ysa/fi" , $vocab->getConfig()->getMarcSourceCode("fi"));
  }

 /**
   * @covers VocabularyConfig::getMarcSourceCode
   */
  public function testGetMarcSourceCodeWithoutLang() {
    $vocab = $this->model->getVocabulary('multiple-schemes');
    $this->assertEquals("ysa/gen" , $vocab->getConfig()->getMarcSourceCode("fi"));
  }

  /**
   * @covers VocabularyConfig::isUseModifiedDate
   */
  public function testGetVocabularyUseModifiedDate() {
    $vocab = $this->model->getVocabulary('http304');
    $this->assertEquals(true , $vocab->getConfig()->isUseModifiedDate());
    $vocab = $this->model->getVocabulary('http304disabled');
    $this->assertEquals(false , $vocab->getConfig()->isUseModifiedDate());
  }

  /**
   * @covers VocabularyConfig::getPluginParameters
   */
  public function testGetPluginParameters() {
    $vocab = $this->model->getVocabulary('paramPluginTest');
    $params = $vocab->getConfig()->getPluginParameters();
    $this->assertEquals(json_encode(array('imaginaryPlugin' => array('poem_fi' => "Roses are red", 'poem' => "Violets are blue", 'color' => "#800000")),true), $params);
  }

  /**
   * @covers VocabularyConfig::getPropertyOrder
   */
  public function testGetPropertyOrderNotSet() {
    $vocab = $this->model->getVocabulary('test');
    $params = $vocab->getConfig()->getPropertyOrder();
    $this->assertEquals(VocabularyConfig::DEFAULT_PROPERTY_ORDER, $params);
  }

  /**
   * @covers VocabularyConfig::getPropertyOrder
   */
  public function testGetPropertyOrderDefault() {
    $vocab = $this->model->getVocabulary('testDefaultPropertyOrder');
    $params = $vocab->getConfig()->getPropertyOrder();
    $this->assertEquals(VocabularyConfig::DEFAULT_PROPERTY_ORDER, $params);
  }

  /**
   * @covers VocabularyConfig::getPropertyOrder
   */
  public function testGetPropertyOrderISO() {
    $vocab = $this->model->getVocabulary('testISOPropertyOrder');
    $params = $vocab->getConfig()->getPropertyOrder();
    $this->assertEquals(VocabularyConfig::ISO25964_PROPERTY_ORDER, $params);
  }


  /**
   * @covers VocabularyConfig::getPropertyOrder
   */
  public function testGetPropertyOrderCustom() {
    $vocab = $this->model->getVocabulary('testCustomPropertyOrder');
    $params = $vocab->getConfig()->getPropertyOrder();

    $order = array('rdf:type', 'skos:definition', 'skos:broader',
    'skos:narrower', 'skos:related', 'skos:altLabel', 'skos:note',
    'skos:scopeNote', 'skos:historyNote', 'skos:prefLabel');

    $this->assertEquals($order, $params);
  }

}
