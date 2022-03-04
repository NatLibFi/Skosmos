<?php

class PluginRegisterTest extends PHPUnit\Framework\TestCase
{
  private $model;
  private $concept;
  private $mockpr;

  protected function setUp() : void
  {
    $this->mockpr = $this->getMockBuilder('PluginRegister')->setConstructorArgs(array(array('test-plugin2',
                                                                                            'global-plugin-Bravo',
                                                                                            'imaginary-plugin',
                                                                                            'test-plugin1',
                                                                                            'global-plugin-alpha',
                                                                                            'global-plugin-charlie',
                                                                                            'test-plugin3'
                                                                                            )))
                                                           ->setMethods(['getPlugins'])
                                                           ->getMock();
    $this->stubplugs = array ('imaginary-plugin' => array ( 'js' => array ( 0 => 'imaginaryPlugin.js', ),
                                                 'css' => array ( 0 => 'stylesheet.css', ),
                                                 'templates' => array ( 0 => 'template.html', ),
                                                 'callback' => array ( 0 => 'imaginaryPlugin')
                        ),
                        'test-plugin1' => array ( 'js' => array ( 0 => 'plugin1.js', 1 => 'second.min.js'),
                                                 'css' => array ( 0 => 'stylesheet.css', ),
                                                 'templates' => array ( 0 => 'template.html', ),
                                                 'callback' => array ( 0 => 'callplugin1')
                        ),
                        'test-plugin2' => array ( 'js' => array ( 0 => 'plugin2.js', ),
                                                 'css' => array ( 0 => 'stylesheet.css', ),
                                                 'templates' => array ( 0 => 'template.html', ),
                                                 'callback' => array ( 0 => 'callplugin2')
                        ),
                        'test-plugin3' => array ( 'js' => array ( 0 => 'plugin3.js', ),
                                                 'css' => array ( 0 => 'stylesheet.css', ),
                                                 'templates' => array ( 0 => 'template.html', ),
                                                 'callback' => array ( 0 => 'callplugin3')
                        ),
                        'only-css' => array ( 'css' => array ( 0 => 'super.css')),
                        'global-plugin-alpha' => array('js' => array('alpha.js'),
                                                  'callback' => array ( 0 => 'alpha')),
                        'global-plugin-Bravo' => array('js' => array('Bravo.js'),
                                                  'callback' => array ( 0 => 'bravo')),
                        'global-plugin-charlie' => array('js' => array('charlie.js'),
                                                  'callback' => array ( 0 => 'charlie')));
    $this->mockpr->method('getPlugins')->will($this->returnValue($this->stubplugs));
    $this->model = new Model(new GlobalConfig('/../tests/testconfig.ttl'));
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
   * @covers PluginRegister::sortPlugins
   */
  public function testGetPluginsJSInOrder()
  {
    $this->assertEquals(['test-plugin2',
                         'global-plugin-Bravo',
                         'imaginary-plugin',
                         'test-plugin1',
                         'global-plugin-alpha',
                         'global-plugin-charlie',
                         'test-plugin3'],
                        array_keys($this->mockpr->getPluginsJS()));

  }

  /**
   * @covers PluginRegister::getPlugins
   * @covers PluginRegister::getPluginsJS
   * @covers PluginRegister::filterPlugins
   * @covers PluginRegister::filterPluginsByName
   * @covers PluginRegister::sortPlugins
   */
  public function testGetPluginsJSWithName()
  {
    $this->assertEquals(array('plugins/test-plugin1/plugin1.js', 'plugins/test-plugin1/second.min.js'),
                        $this->mockpr->getPluginsJS()['test-plugin1']);
  }

  /**
   * @covers PluginRegister::getPlugins
   * @covers PluginRegister::getPluginsJS
   * @covers PluginRegister::filterPlugins
   * @covers PluginRegister::filterPluginsByName
   * @covers PluginRegister::sortPlugins
   */
  public function testGetPluginsJSWithGlobalPlugin()
  {
    $this->assertEquals(array('plugins/global-plugin-alpha/alpha.js'),
                        $this->mockpr->getPluginsJS()['global-plugin-alpha']);
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
   * @covers PluginRegister::sortPlugins
   */
  public function testGetPluginsCSSWithName()
  {
    $this->assertEquals(array('plugins/test-plugin1/stylesheet.css'),
                        $this->mockpr->getPluginsCSS()['test-plugin1']);
  }

  /**
   * @covers PluginRegister::getPluginCallbacks
   * @covers PluginRegister::filterPlugins
   * @covers PluginRegister::filterPluginsByName
   * @covers PluginRegister::sortPlugins
   */
  public function testGetPluginCallbacks()
  {
    $plugins = new PluginRegister();
    $this->assertEquals(array('plugins/test-plugin1/callplugin1'),
                        $this->mockpr->getPluginCallbacks()['test-plugin1']);
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
    $this->assertEquals(array('callplugin2', 'bravo', 'imaginaryPlugin', 'callplugin1', 'alpha', 'charlie', 'callplugin3'),
                        $this->mockpr->getCallbacks()
    );
  }

}
