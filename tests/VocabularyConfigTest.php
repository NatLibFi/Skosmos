<?php

require_once('model/Model.php');

class VocabularyConfigTest extends PHPUnit_Framework_TestCase
{
  
  private $model; 

  protected function setUp() {
    require_once 'testconfig.inc';
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    $this->model = new Model();
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
   */
  public function testGetDefaultLanguage() {
    $vocab = $this->model->getVocabulary('test');
    $lang = $vocab->getConfig()->getDefaultLanguage();
    $this->assertEquals('en', $lang);
  }
  
  /**
   * @covers VocabularyConfig::getDefaultLanguage
   * @expectedException \Exception
   * @expectedExceptionMessage Default language for vocabulary 'testdiff' unknown, choosing
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
   * @expectedException PHPUnit_Framework_Error_Warning
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

}
