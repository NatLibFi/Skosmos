<?php

class PluginRegisterTest extends PHPUnit_Framework_TestCase
{
  private $model; 
  private $concept;

  protected function setUp() {
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
  
  /**
   * @covers Concept::getPluginsJS
   */
  public function testGetPluginsJS()
  {
    $plugins = new PluginRegister();
    $this->assertEquals(array(), $plugins->getPluginsJS());
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
