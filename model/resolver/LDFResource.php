<?php

class LDFResource implements RemoteResource
{
    private $uri;
    private $ldfEndpoint;

    public function __construct(string $uri, string $ldfEndpoint) {
        $this->uri = $uri;
        $this->ldfEndpoint = $ldfEndpoint;
    }
    
    public function resolve(int $timeout) : ?EasyRdf\Resource {
        try {
            // change the timeout setting for external requests
            $httpclient = EasyRdf\Http::getDefaultHttpClient();
            $httpclient->setConfig(array('timeout' => $timeout));
            EasyRdf\Http::setDefaultHttpClient($httpclient);

            $graph = new EasyRdf\Graph($this->uri);
            $params1 = http_build_query(array('subject' => $this->uri, 'predicate' => 'rdfs:label'));
            $graph->load($this->ldfEndpoint . '?' . $params1);
            $params2 = http_build_query(array('object' => $this->uri, 'predicate' => 'schema:about'));
            $graph->load($this->ldfEndpoint . '?' . $params2);
            return $graph->resource($this->uri);
        } catch (Exception $e) {
            // FIXME proper logging needed
            echo $e;
            return null;
        }

    }
    
}


