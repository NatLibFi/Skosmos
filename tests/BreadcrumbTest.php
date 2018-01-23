<?php

class BreadcrumbTest extends PHPUnit\Framework\TestCase
{

  /**
   * @covers Breadcrumb::__construct
   * @covers Breadcrumb::getUri
   */
  public function testConstruct() {
    $bc = new Breadcrumb('http://skosmos.skos/onto/test/t001', 'testLabel');
    $this->assertInstanceOf('Breadcrumb', $bc);
    $this->assertEquals('http://skosmos.skos/onto/test/t001', $bc->getUri());
  }

  /**
   * @covers Breadcrumb::hideLabel
   * @covers Breadcrumb::getPrefLabel
   */
  public function testHideLabel() {
    $bc = new Breadcrumb('http://skosmos.skos/onto/test/t001', 'testLabel');
    $this->assertEquals('testLabel', $bc->getPrefLabel());
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
    $children = $bc->getNarrowerConcepts();
    $this->assertEquals($bc2, $children['http://skosmos.skos/onto/test/t007']);
  }
}
