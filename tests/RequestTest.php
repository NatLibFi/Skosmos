<?php

class RequestTest extends PHPUnit\Framework\TestCase
{
  private $model;
  private $request;

  protected function setUp() : void
  {
    putenv("LANGUAGE=en_GB.utf8");
    putenv("LC_ALL=en_GB.utf8");
    setlocale(LC_ALL, 'en_GB.utf8');
    bindtextdomain('skosmos', 'resource/translations');
    bind_textdomain_codeset('skosmos', 'UTF-8');
    textdomain('skosmos');

    $config = new GlobalConfig('/../../tests/testconfig.ttl');
    $this->model = new Model($config);
    $this->request = new Request($this->model);
  }

  /**
   * @covers Request::__construct
   */
  public function testConstructor() {
      $this->assertInstanceOf('Request', new Request($this->model));
  }

  /**
   * @covers Request::getQueryParamBoolean
   */
  public function testGetQueryParamBooleanDefaultValue() {
      $this->assertTrue($this->request->getQueryParamBoolean('notfoundatall', true));
  }

  /**
   * @covers Request::getVocabList
   */
  public function testGetVocabList() {
    $list = $this->request->getVocabList();
    foreach ($list as $cat) {
      foreach ($cat as $voc) {
        $this->assertInstanceOf('Vocabulary', $voc);
      }
    }
  }

  /**
   * @covers Request::setVocabids
   * @covers Request::getVocabid
   */
  public function testSetVocabids() {
    $this->request->setVocabids(array('testing', 'setter'));
    $this->assertEquals(array('testing', 'setter'), $this->request->getVocabid());
  }

  /**
   * @covers Request::setVocab
   * @covers Request::getVocab
   */
  public function testSetVocab() {
    $this->request->setVocab('test');
    $this->assertInstanceOf('Vocabulary', $this->request->getVocab());
  }

  /**
   * @covers Request::setVocab
   * @covers Request::getVocabid
   */
  public function testSetVocabWithMultipleVocabularies() {
    $this->request->setVocab('test dates');
    $this->assertEquals('test dates', $this->request->getVocabid());
  }

  /**
   * @covers Request::getVocabid
   */
  public function testGetVocabid() {
    $this->request->setVocab('test');
    $this->assertEquals('test', $this->request->getVocabId());
  }

  /**
   * @covers Request::setUri
   * @covers Request::getUri
   */
  public function testSetAndGetUri() {
    $this->request->setVocab('test');
    $this->request->setUri('www.skosmos.org');
    $this->assertEquals('www.skosmos.org', $this->request->getUri());
  }

  /**
   * @covers Request::setContentLang
   * @covers Request::getContentLang
   * @covers Request::verifyContentLang
   */
  public function testSetContentLang() {
    $this->request->setVocab('test');
    $clang = $this->request->setContentLang('en');
    $this->assertEquals('en', $this->request->getContentLang());
  }

  /**
   * @covers Request::setContentLang
   * @covers Request::verifyContentLang
   */
  public function testSetContentLangWhenNoVocabularyAvailable() {
    $clang = $this->request->setContentLang('fi');
    $this->assertEquals('fi', $this->request->getContentLang());
  }

  /**
   * @covers Request::setContentLang
   * @covers Request::getContentLang
   * @covers Request::verifyContentLang
   */
  public function testSetContentLangWithUnsupportedLanguage() {
    $this->request->setVocab('test');
    $clang = $this->request->setContentLang('ru');
    $this->assertEquals('en', $this->request->getContentLang());
  }

  /**
   * @covers Request::setLang
   * @covers Request::getLang
   */
  public function testSetAndGetLang() {
    $this->request->setVocab('test');
    $clang = $this->request->setLang('en');
    $this->assertEquals('en', $this->request->getLang());
  }

  /**
   * @covers Request::getLetter
   */
  public function testGetLetterWhenNotSet() {
    $this->request->setVocab('test');
    $this->assertEquals('', $this->request->getLetter());
  }

  /**
   * @covers Request::setLetter
   * @covers Request::getLetter
   */
  public function testSetAndGetLetter() {
    $this->request->setVocab('test');
    $this->request->setLetter('X');
    $this->assertEquals('X', $this->request->getLetter());
  }

  /**
   * @covers Request::setPage
   * @covers Request::getPage
   */
  public function testSetAndGetPage() {
    $this->request->setPage('index');
    $this->assertEquals('index', $this->request->getPage());
  }

  /**
   * @covers Request::getVersion
   */
  public function testGetVersion() {
    $version = $this->request->getVersion();
    $this->assertNotEmpty($version);
  }

  /**
   * @covers Request::getLangUrl
   */
  public function testGetLangUrlNoParamRoot() {
    $this->request->setServerConstant('SCRIPT_NAME', '/Skosmos/index.php');
    $this->request->setServerConstant('REQUEST_URI', '/Skosmos/en/');
    $langurl = $this->request->getLangUrl();
    $this->assertEquals("en/", $langurl);
  }

  /**
   * @covers Request::getLangUrl
   */
  public function testGetLangUrlNoParamVocab() {
    $this->request->setServerConstant('SCRIPT_NAME', '/Skosmos/index.php');
    $this->request->setServerConstant('REQUEST_URI', '/Skosmos/myvocab/en/');
    $langurl = $this->request->getLangUrl();
    $this->assertEquals("myvocab/en/", $langurl);
  }

  /**
   * @covers Request::getLangUrl
   */
  public function testGetLangUrlNoParamVocabIndex() {
    $this->request->setServerConstant('SCRIPT_NAME', '/Skosmos/index.php');
    $this->request->setServerConstant('REQUEST_URI', '/Skosmos/myvocab/en/index');
    $langurl = $this->request->getLangUrl();
    $this->assertEquals("myvocab/en/index", $langurl);
  }

  /**
   * @covers Request::getLangUrl
   */
  public function testGetLangUrlNewLangRoot() {
    $this->request->setServerConstant('SCRIPT_NAME', '/Skosmos/index.php');
    $this->request->setServerConstant('REQUEST_URI', '/Skosmos/en/');
    $this->request->setLang('en');
    $langurl = $this->request->getLangUrl("sv");
    $this->assertEquals("sv/", $langurl);
  }

  /**
   * @covers Request::getLangUrl
   */
  public function testGetLangUrlNewLangVocab() {
    $this->request->setServerConstant('SCRIPT_NAME', '/Skosmos/index.php');
    $this->request->setServerConstant('REQUEST_URI', '/Skosmos/myvocab/en/');
    $this->request->setLang('en');
    $langurl = $this->request->getLangUrl("sv");
    $this->assertEquals("myvocab/sv/", $langurl);
  }

  /**
   * @covers Request::getLangUrl
   */
  public function testGetLangUrlNewLangVocabIndex() {
    $this->request->setServerConstant('SCRIPT_NAME', '/Skosmos/index.php');
    $this->request->setServerConstant('REQUEST_URI', '/Skosmos/myvocab/en/index');
    $this->request->setLang('en');
    $langurl = $this->request->getLangUrl("sv");
    $this->assertEquals("myvocab/sv/index", $langurl);
  }

  /**
   * @covers Request::getLangUrl
   */
  public function testGetLangUrlSanitizeSpecialChars() {
    $this->request->setServerConstant('SCRIPT_NAME', '/Skosmos/index.php');
    $this->request->setServerConstant('REQUEST_URI', '/Skosmos/http://example.com');
    $this->request->setLang('en');
    $langurl = $this->request->getLangUrl();
    $this->assertEquals("http//example.com", $langurl);
  }

}
