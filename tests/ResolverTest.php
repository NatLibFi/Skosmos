<?php

class ResolverTest extends PHPUnit\Framework\TestCase
{
  private $resolver;

  protected function setUp() {
    $model = new Model(new GlobalConfig('/../tests/testconfig.ttl'));
    $this->resolver = new Resolver($model);
  }

  /**
   * @covers Resolver::resolve
   * @covers RemoteResource::__construct
   * @covers LOCResource::resolve
   * @uses Resolver
   */
  public function testResolveLOCZeroTimeout()
  {
    $uri = "http://id.loc.gov/authorities/subjects/sh85016673"; // LCSH: Breakwaters
    $resource = $this->resolver->resolve($uri, 0);
    $this->assertNull($resource);
  }

  /**
   * @covers Resolver::resolve
   * @covers RemoteResource::__construct
   * @covers WDQSResource::resolve
   * @uses Resolver
   */
  public function testResolveWDQSZeroTimeout()
  {
    $uri = "http://www.wikidata.org/entity/Q42"; // Wikidata: Douglas Adams
    $resource = $this->resolver->resolve($uri, 0);
    $this->assertNull($resource);
  }

  /**
   * @covers Resolver::resolve
   * @covers RemoteResource::__construct
   * @covers LinkedDataResource::resolve
   * @uses Resolver
   */
  public function testResolveLDZeroTimeout()
  {
    $uri = "http://paikkatiedot.fi/so/1000772/10048472"; // PNR: Ahlainen
    $resource = $this->resolver->resolve($uri, 0);
    $this->assertNull($resource);
  }

}
