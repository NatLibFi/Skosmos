<?php

class ResolverTest extends PHPUnit\Framework\TestCase
{
    private $resolver;

    protected function setUp(): void
    {
        $model = new Model('../../tests/testconfig.ttl');
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
        // use @ to suppress the timeout warning
        @$resource = $this->resolver->resolve($uri, 0);
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
        // use @ to suppress the timeout warning
        @$resource = $this->resolver->resolve($uri, 0);
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
        // use @ to suppress the timeout warning
        @$resource = $this->resolver->resolve($uri, 0);
        $this->assertNull($resource);
    }

}
