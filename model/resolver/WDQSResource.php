<?php

class WDQSResource extends RemoteResource
{
    
    const WDQS_ENDPOINT = "https://query.wikidata.org/sparql";

    public function resolve(int $timeout) : ?EasyRdf\Resource {
        $client = new EasyRdf\Sparql\Client(self::WDQS_ENDPOINT);
        try {
            // unregister the legacy "json" format as it causes problems with CONSTRUCT requests
            EasyRdf\Format::unregister('json');
            // change the timeout setting for external requests
            $httpclient = EasyRdf\Http::getDefaultHttpClient();
            $httpclient->setConfig(array('timeout' => $timeout));
            $httpclient->setHeaders('Accept', 'text/turtle');
            EasyRdf\Http::setDefaultHttpClient($httpclient);
            
            $uri = $this->uri;
            $query = <<<EOQ
PREFIX wd:     <http://www.wikidata.org/entity/>
PREFIX rdfs:   <http://www.w3.org/2000/01/rdf-schema#>
PREFIX schema: <http://schema.org/>

CONSTRUCT {
  <$uri> rdfs:label ?label .
  ?link schema:about <$uri> .
}
WHERE
{
  { <$uri> rdfs:label ?label }
  UNION
  { ?link schema:about <$uri> }
}
EOQ;

            $client = new EasyRdf\Sparql\Client(self::WDQS_ENDPOINT);
            $graph = $client->query($query);
            return $graph->resource($this->uri);
        } catch (Exception $e) {
            $this->model->getLogger()->info("WDQS resolution failed for <{$this->uri}>: $e");
            return null;
        }
    }
}
