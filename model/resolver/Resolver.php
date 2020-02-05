<?php

class Resolver {


    /**
     * Resolve the URI using the most appropriate resolver and return the
     * result as an ExternalResource.
     * @param string $uri URI to resolve
     * @return EasyRdf\Resource
     */
    public function resolve(string $uri, int $timeout): ?EasyRdf\Resource {
        $res = new LinkedDataResource($uri);
        return $res->resolve($timeout);
    }
}
