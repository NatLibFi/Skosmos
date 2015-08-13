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

  /**
   * @covers Breadcrumb::getNarrowerConcepts
   * @covers Breadcrumb::addNarrower
   */
  public function testAddAndGetNarrower() {
    $bc = new Breadcrumb('http://skosmos.skos/onto/test/t001', 'prefLabel');
    $bc2 = new Breadcrumb('http://skosmos.skos/onto/test/t007', 'narrower');
    $bc->addNarrower($bc2);
    $child = reset($bc->getNarrowerConcepts());
    $this->assertEquals($bc2, $child);
  }
}
