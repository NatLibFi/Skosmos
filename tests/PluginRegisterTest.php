<?php

class PluginRegisterTest extends PHPUnit_Framework_TestCase
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
   * @covers Concept::__construct
   */
  public function testConstructor()
  {
    $plugins = new PluginRegister();
    $this->assertInstanceOf('PluginRegister', $plugins);
  }
  //array ( 'finna' => array ( 'js' => array ( 0 => 'widget.js', 1 => 'node_modules/i18next/i18next.min.js', ), 'css' => array ( 0 => 'stylesheet.css', ), 'templates' => array ( 0 => 'template.html', ), ), 'finto' => array ( 'css' => array ( 0 => 'finto.css', ), ), )
  
  /**
   * @covers Concept::getPluginsJS
   */
  public function testGetPluginsJS()
  {
    $plugins = new PluginRegister();
    $this->assertEquals(array(), $plugins->getPluginsJS());
  }
  
  /**
   * @covers Concept::getPluginsJS
   * @covers Concept::filterPlugins
   * @covers Concept::filterPluginsByName
   */
  public function testGetPluginsJSWithName()
  {
    $this->assertEquals(array('global-plugin' => array('plugins/global-plugin/everywhere.js'), 'test-plugin' => array('plugins/test-plugin/first.js', 'plugins/test-plugin/second.min.js')), $this->mockpr->getPluginsJS(array('test-plugin')));
  }
  
  /**
   * @covers Concept::getPluginsJS
   * @covers Concept::filterPlugins
   * @covers Concept::filterPluginsByName
   */
  public function testGetPluginsJSWithGlobalPlugin()
  {
    $this->assertEquals(array('global-plugin' => array('plugins/global-plugin/everywhere.js')), $this->mockpr->getPluginsJS());
  }
  
  /**
   * @covers Concept::getPluginsCSS
   */
  public function testGetPluginsCSS()
  {
    $plugins = new PluginRegister();
    $this->assertEquals(array(), $plugins->getPluginsCSS());
  }
  
  
  /**
   * @covers Concept::getPluginsTemplates
   */
  public function testGetPluginsTemplates()
  {
    $plugins = new PluginRegister();
    $this->assertEquals(array(), $plugins->getPluginsTemplates());
  }
}
