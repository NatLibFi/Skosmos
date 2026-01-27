<?php

class WikidataResource extends RemoteResource
{
    // use the QLever Wikidata endpoint as it is much faster than WDQS
    public const WIKIDATA_ENDPOINT = "https://qlever.dev/api/wikidata";

    public function resolve(int $timeout): ?EasyRdf\Resource
    {
        try {
            // change the timeout setting for external requests
            $httpclient = EasyRdf\Http::getDefaultHttpClient();
            $httpclient->setConfig(array('timeout' => $timeout, 'useragent' => 'Skosmos'));
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

            $client = new EasyRdf\Sparql\Client(self::WIKIDATA_ENDPOINT);
            $graph = $client->query($query);
            return $graph->resource($this->uri);
        } catch (Exception $e) {
            $this->model->getLogger()->info("WDQS resolution failed for <{$this->uri}>: $e");
            return null;
        }
    }
}
