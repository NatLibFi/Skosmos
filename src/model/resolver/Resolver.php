<?php

class Resolver
{
    private $model;

    /**
     * Initializes the Resolver object
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    private function startsWith(string $prefix, string $target): bool
    {
        return strpos($target, $prefix) === 0;
    }

    /**
     * Resolve the URI using the most appropriate resolver and return the
     * result as an ExternalResource.
     * @param string $uri URI to resolve
     * @return EasyRdf\Resource
     */
    public function resolve(string $uri, int $timeout): ?EasyRdf\Resource
    {
        if (preg_match('|http://id.loc.gov/[^/]+/[^/]+/.+|', $uri)) {
            $res = new LOCResource($this->model, $uri);
        } elseif ($this->startsWith('http://www.wikidata.org/entity/', $uri)) {
            $res = new WDQSResource($this->model, $uri);
        } else {
            $res = new LinkedDataResource($this->model, $uri);
        }
        return $res->resolve($timeout);
    }
}
