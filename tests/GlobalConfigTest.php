<?php

class GlobalConfigTest extends PHPUnit_Framework_TestCase
{
  
  private $config; 

  protected function setUp() {
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    $this->config = new GlobalConfig('/../tests/testconfig.inc');
  }

  /**
   * @covers GlobalConfig::getLanguages
   */
  public function testLanguagesWithoutConfiguration() {
    $actual = $this->config->getLanguages();
    $this->assertEquals(array('en' => 'en_GB.utf8'), $actual);
  }

  /**
   * @covers GlobalConfig::getHttpTimeout
   * @covers GlobalConfig::getConstant
   */
  public function testTimeoutDefaultValue() {
    $actual = $this->config->getHttpTimeout();
    $this->assertEquals(5, $actual);
  }

  /**
   * @covers GlobalConfig::getDefaultEndpoint
   * @covers GlobalConfig::getConstant
   */
  public function testEndpointDefaultValue() {
    $actual = $this->config->getDefaultEndpoint();
    $this->assertEquals('http://localhost:3030/ds/sparql', $actual);
  }

  /**
   * @covers GlobalConfig::getSparqlGraphStore
   * @covers GlobalConfig::getConstant
   */
  public function testSparqlGraphDefaultValue() {
    $actual = $this->config->getSparqlGraphStore();
    $this->assertEquals(null, $actual);
  }

  /**
   * @covers GlobalConfig::getDefaultTransitiveLimit
   * @covers GlobalConfig::getConstant
   */
  public function testTransitiveLimitDefaultValue() {
    $actual = $this->config->getDefaultTransitiveLimit();
    $this->assertEquals(1000, $actual);
  }

  /**
   * @covers GlobalConfig::getSearchResultsSize
   * @covers GlobalConfig::getConstant
   */
  public function testSearchLimitDefaultValue() {
    $actual = $this->config->getSearchResultsSize();
    $this->assertEquals(20, $actual);
  }

  /**
   * @covers GlobalConfig::getTemplateCache
   * @covers GlobalConfig::getConstant
   */
  public function testTemplateCacheDefaultValue() {
    $actual = $this->config->getTemplateCache();
    $this->assertEquals('/tmp/skosmos-template-cache', $actual);
  }

  /**
   * @covers GlobalConfig::getDefaultSparqlDialect
   * @covers GlobalConfig::getConstant
   */
  public function testSparqlDialectDefaultValue() {
    $actual = $this->config->getDefaultSparqlDialect();
    $this->assertEquals('Generic', $actual);
  }

  /**
   * @covers GlobalConfig::getFeedbackAddress
   * @covers GlobalConfig::getConstant
   */
  public function testFeedbackAddressDefaultValue() {
    $actual = $this->config->getFeedbackAddress();
    $this->assertEquals(null, $actual);
  }

  /**
   * @covers GlobalConfig::getLogCaughtExceptions
   * @covers GlobalConfig::getConstant
   */
  public function testExceptionLoggingDefaultValue() {
    $actual = $this->config->getLogCaughtExceptions();
    $this->assertEquals(null, $actual);
  }

  /**
   * @covers GlobalConfig::getServiceName
   * @covers GlobalConfig::getConstant
   */
  public function testServiceNameDefaultValue() {
    $actual = $this->config->getServiceName();
    $this->assertEquals('Skosmos', $actual);
  }

  /**
   * @covers GlobalConfig::getServiceTagline
   * @covers GlobalConfig::getConstant
   */
  public function testgetServiceTaglineDefaultValue() {
    $actual = $this->config->getServiceTagline();
    $this->assertEquals(null, $actual);
  }

  /**
   * @covers GlobalConfig::getServiceLogo
   * @covers GlobalConfig::getConstant
   */
  public function testServiceLogoDefaultValue() {
    $actual = $this->config->getServiceLogo();
    $this->assertEquals(null, $actual);
  }

  /**
   * @covers GlobalConfig::getCustomCss
   * @covers GlobalConfig::getConstant
   */
  public function testCustomCssDefaultValue() {
    $actual = $this->config->getCustomCss();
    $this->assertEquals(null, $actual);
  }

  /**
   * @covers GlobalConfig::getUILanguageDropdown
   * @covers GlobalConfig::getConstant
   */
  public function testDefaultValue() {
    $actual = $this->config->getUILanguageDropdown();
    $this->assertFalse($actual);
  }

  /**
   * @covers GlobalConfig::getBaseHref
   * @covers GlobalConfig::getConstant
   */
  public function testBaseHrefDefaultValue() {
    $actual = $this->config->getBaseHref();
    $this->assertEquals(null, $actual);
  }

  /**
   * @covers GlobalConfig::getVocabularyConfigFile
   * @covers GlobalConfig::getConstant
   */
  public function testGetVocabularyConfigFile() {
    $actual = $this->config->getVocabularyConfigFile();
    $this->assertEquals('tests/testvocabularies.ttl', $actual);
  }

  /**
   * @covers GlobalConfig::getCollationEnabled
   */
  public function testGetCollationEnabled() {
    $actual = $this->config->getCollationEnabled();
    $this->assertFalse($actual);
  }
}

