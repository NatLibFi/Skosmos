<?php

use Symfony\Component\DomCrawler\Crawler;

class FeedbackTest extends PHPUnit\Framework\TestCase
{
  private $model;
  private $request;

  protected function setUp() : void
  {
    $config = new GlobalConfig('/../tests/testconfig-fordefaults.ttl');
    $this->model = new Model($config);
    $this->request = \Mockery::mock('Request', array($this->model))->makePartial();
    $this->request->setLang('en');
    $this->controller = \Mockery::mock('WebController[sendFeedback]', array($this->model))->makePartial();
  }

  /**
   * @covers Honeypot::decryptTime
   * @covers Honeypot::generate
   * @covers Honeypot::getEncryptedTime
   */
  public function testHoneypotFieldsGenerated() {
    $this->controller
        ->shouldReceive('sendFeedback')
        ->withAnyArgs()
        ->once()
        ->andReturn(true);

    $initialTime = time();
    ob_start();
    $this->controller->invokeFeedbackForm($this->request);
    $html = ob_get_contents();
    ob_end_clean();

    $crawler = new Crawler($html);
    $mustBeEmptyHoneypot = $crawler->filterXPath("//input[@name='item-description']");
    $value = $mustBeEmptyHoneypot->attr('value');
    $this->assertEquals('', $value);

    // the encrypted time will be at least equal, if not greater, than the initial time recorded
    $timeBaseHoneypot = $crawler->filterXPath("//input[@name='user-captcha']");
    $encryptedTime = $timeBaseHoneypot->attr('value');
    $decryptedTime = intval($this->controller->honeypot->decryptTime($encryptedTime));
    $this->assertTrue($decryptedTime >= $initialTime);
  }

  /**
   * @covers Honeypot::validateHoneypot
   * @covers Honeypot::validateHoneytime
   */
  public function testHoneypotAndHoneypot() {
    $this->expectNotToPerformAssertions();
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('message')
        ->andReturn('Test message');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('name')
        ->andReturn('Test name');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('email')
        ->andReturn('test@example.com');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('vocab')
        ->andReturn('test');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('item-description')
        ->andReturn('');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('user-captcha')
        // 6 seconds ago is more than the default 5 seconds
        ->andReturn(base64_encode(time() - 6));
    $this->controller
        ->shouldReceive('sendFeedback')
        ->withAnyArgs()
        ->once()
        ->andReturn(true);
    ob_start();
    $this->controller->invokeFeedbackForm($this->request);
    ob_end_clean();
    \Mockery::close();
  }

  /**
   * @covers Honeypot::validateHoneypot
   * @covers Honeypot::validateHoneytime
   */
  public function testHoneypotAndHoneypotDisabled() {
    $this->expectNotToPerformAssertions();
    $this->controller->honeypot->disable();
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('message')
        ->andReturn('Test message');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('name')
        ->andReturn('Test name');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('email')
        ->andReturn('test@example.com');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('vocab')
        ->andReturn('test');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('item-description')
        // supposed to be empty
        ->andReturn('Test item description');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('user-captcha')
        // 0 seconds ago is less than the default 5 seconds
        ->andReturn(base64_encode(time() - 0));
    $this->controller
        ->shouldReceive('sendFeedback')
        ->withAnyArgs()
        ->once()
        ->andReturn(true);
    ob_start();
    $this->controller->invokeFeedbackForm($this->request);
    ob_end_clean();
    \Mockery::close();
  }

  /**
   * @covers Honeypot::validateHoneytime
   */
  public function testHoneytimeTooFast() {
    $this->expectNotToPerformAssertions();
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('message')
        ->andReturn('Test message');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('name')
        ->andReturn('Test name');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('email')
        ->andReturn('test@example.com');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('vocab')
        ->andReturn('test');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('item-description')
        ->andReturn('');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('user-captcha')
        // 2 seconds ago is less than the default 5 seconds
        ->andReturn(base64_encode(time() - 2));
    $this->controller
        ->shouldReceive('sendFeedback')
        ->withAnyArgs()
        ->never();
    ob_start();
    $this->controller->invokeFeedbackForm($this->request);
    ob_end_clean();
    \Mockery::close();
  }

  /**
   * @covers Honeypot::validateHoneypot
   */
  public function testHoneypotNotEmpty() {
    $this->expectNotToPerformAssertions();
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('message')
        ->andReturn('Test message');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('name')
        ->andReturn('Test name');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('email')
        ->andReturn('test@example.com');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('vocab')
        ->andReturn('test');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('item-description')
        // supposed to be empty
        ->andReturn('Test item description');
    $this->request
        ->shouldReceive('getQueryParamPOST')
        ->with('user-captcha')
        ->andReturn(base64_encode(time() - 5));
    $this->controller
        ->shouldReceive('sendFeedback')
        ->withAnyArgs()
        ->never();
    ob_start();
    $this->controller->invokeFeedbackForm($this->request);
    ob_end_clean();
    \Mockery::close();
  }

}
