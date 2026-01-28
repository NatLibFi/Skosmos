<?php

class WDQSResource extends RemoteResource
{
    public const WDQS_ENDPOINT = "https://query.wikidata.org/sparql";

    public function resolve(int $timeout): ?EasyRdf\Resource
    {
        try {
            // Use HTTP/2 client for Wikidata WDQS (blocks HTTP/1.1 since Jan 2026)
            // See: https://wikitech.wikimedia.org/wiki/Robot_policy
            $httpclient = new EasyRdf\Http\Http2Client();
            $httpclient->setConfig(array('timeout' => $timeout, 'useragent' => 'Skosmos'));
            $httpclient->setHeaders('Accept', 'text/turtle');
            EasyRdf\Http::setDefaultHttpClient($httpclient);

            $uri = $this->uri;
            $query = <<<EOQ
PREFIX wd:     <http://www.wikidata.org/entity/>
PREFIX rdfs:   <http://www.w3.org/2000/01/rdf-schema#>
PREFIX schema: <http://schema.org/>

CONSTRUCT {
  <$uri> rdfs:label ?label .
  ?link schema:about <$uri> ;
    a ?linktype ;
    schema:isPartOf ?whole ;
    schema:inLanguage ?lang .
}
WHERE
{
  { <$uri> rdfs:label ?label }
  UNION
  {
    ?link schema:about <$uri> ;
      a ?linktype ;
      schema:isPartOf ?whole ;
      schema:inLanguage ?lang .
  }
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
