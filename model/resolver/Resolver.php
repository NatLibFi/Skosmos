<?php

class Resolver
{

    private function startsWith(string $prefix, string $target) : bool {
        return strpos($target, $prefix) === 0;
    }

    /**
     * Resolve the URI using the most appropriate resolver and return the
     * result as an ExternalResource.
     * @param string $uri URI to resolve
     * @return EasyRdf\Resource
     */
    public function resolve(string $uri, int $timeout): ?EasyRdf\Resource {
        if (preg_match('|http://id.loc.gov/[^/]+/[^/]+/.+|', $uri)) {
            $res = new LOCResource($uri);
        } elseif ($this->startsWith('http://www.wikidata.org/entity/', $uri)) {
            $res = new LDFResource($uri, 'https://query.wikidata.org/bigdata/ldf');
        } else {
            $res = new LinkedDataResource($uri);
        }
        return $res->resolve($timeout);
    }
}
