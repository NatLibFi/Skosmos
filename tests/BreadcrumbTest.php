<?php

class BreadcrumbTest extends PHPUnit_Framework_TestCase
{
  
  /**
   * @covers Breadcrumb::__construct
   */
  public function testConstruct() {
    $bc = new Breadcrumb('http://skosmos.skos/onto/test/t001', 'testLabel');
    $this->assertInstanceOf('Breadcrumb', $bc);
  }

  /**
   * @covers Breadcrumb::hideLabel
   */
  public function testHideLabel() {
    $bc = new Breadcrumb('http://skosmos.skos/onto/test/t001', 'testLabel');
    $bc->hideLabel();
    $this->assertEquals('...', $bc->getPrefLabel());
  }
  
  /**
   * @covers Breadcrumb::getHiddenLabel
   */
  public function testHideLabelAndGetHiddenLabelBack() {
    $bc = new Breadcrumb('http://skosmos.skos/onto/test/t001', 'testLabel');
    $bc->hideLabel();
    $this->assertEquals('testLabel', $bc->getHiddenLabel());
  }

}
