<?php

class PluginRegisterTest extends PHPUnit\Framework\TestCase
{
  private $model;
  private $concept;
  private $mockpr;

  protected function setUp() {
    $this->mockpr = $this->getMockBuilder('PluginRegister')->setConstructorArgs(array(array('global-plugin')))->setMethods(array('getPlugins'))->getMock();
    $stubplugs = array ('test-plugin' => array ( 'js' => array ( 0 => 'first.js', 1 => 'second.min.js', ), 'css' => array ( 0 => 'stylesheet.css', ), 'templates' => array ( 0 => 'template.html', ), ), 'only-css' => array ( 'css' => array ( 0 => 'super.css')), 'global-plugin' => array('js' => array('everywhere.js')));
    $this->mockpr->method('getPlugins')->will($this->returnValue($stubplugs));
    $this->model = new Model(new GlobalConfig('/../tests/testconfig.inc'));
    $this->vocab = $this->model->getVocabulary('test');
  }

  /**
   * @covers PluginRegister::__construct
   */
  public function testConstructor()
  {
    $plugins = new PluginRegister();
    $this->assertInstanceOf('PluginRegister', $plugins);
  }

  /**
   * @covers PluginRegister::getPlugins
   * @covers PluginRegister::getPluginsJS
   */
  public function testGetPluginsJS()
  {
    $plugins = new PluginRegister();
    $this->assertEquals(array(), $plugins->getPluginsJS());
  }

  /**
   * @covers PluginRegister::getPlugins
   * @covers PluginRegister::getPluginsJS
   * @covers PluginRegister::filterPlugins
   * @covers PluginRegister::filterPluginsByName
   */
  public function testGetPluginsJSWithName()
  {
    $this->assertEquals(array('global-plugin' => array('plugins/global-plugin/everywhere.js'), 'test-plugin' => array('plugins/test-plugin/first.js', 'plugins/test-plugin/second.min.js')), $this->mockpr->getPluginsJS(array('test-plugin')));
  }

  /**
   * @covers PluginRegister::getPlugins
   * @covers PluginRegister::getPluginsJS
   * @covers PluginRegister::filterPlugins
   * @covers PluginRegister::filterPluginsByName
   */
  public function testGetPluginsJSWithGlobalPlugin()
  {
    $this->assertEquals(array('global-plugin' => array('plugins/global-plugin/everywhere.js')), $this->mockpr->getPluginsJS());
  }

  /**
   * @covers PluginRegister::getPluginsCSS
   */
  public function testGetPluginsCSS()
  {
    $plugins = new PluginRegister();
    $this->assertEquals(array(), $plugins->getPluginsCSS());
  }

  /**
   * @covers PluginRegister::getPluginsCSS
   * @covers PluginRegister::filterPlugins
   * @covers PluginRegister::filterPluginsByName
   */
  public function testGetPluginsCSSWithName()
  {
    $plugins = new PluginRegister();
    $this->assertEquals(array('test-plugin' => array('plugins/test-plugin/stylesheet.css')), $this->mockpr->getPluginsCSS(array('test-plugin')));
  }

  /**
   * @covers PluginRegister::getPluginsTemplates
   */
  public function testGetPluginsTemplates()
  {
    $plugins = new PluginRegister();
    $this->assertEquals(array(), $plugins->getPluginsTemplates());
  }

  /**
   * @covers PluginRegister::getTemplates
   */
  public function testGetTemplates()
  {
    $plugins = new PluginRegister();
    $this->assertEquals(array(), $plugins->getTemplates());
  }

  /**
   * @covers PluginRegister::getCallbacks
   */
  public function testGetCallbacks()
  {
    $plugins = new PluginRegister();
    $this->assertEquals(array(), $plugins->getCallbacks());
  }

}
