<?php

class LOCResource extends RemoteResource
{
    public function resolve(int $timeout) : ?EasyRdf\Resource {
        $graph = new EasyRdf\Graph($this->uri);
        // guess the concept scheme based on the URI
        if (preg_match('|(http://id.loc.gov/[^/]+/[^/]+)/.*|', $this->uri, $matches)) {
            $graph->addResource($this->uri, 'skos:inScheme', $matches[1]);
        }

        try {
            $opts = array('http' => array('method'=>'HEAD',
                                          'user_agent' => 'Skosmos',
                                          'timeout' => $timeout));
            $context  = stream_context_create($opts);
            $fd = fopen($this->uri, 'rb', false, $context);
            $headers = stream_get_meta_data($fd)['wrapper_data'];
            foreach ($headers as $header) {
                if (strpos($header, 'X-PrefLabel:') === 0) {
                    $elems = explode(' ', $header, 2);
                    $prefLabel = $elems[1];
                    $graph->addLiteral($this->uri, 'skos:prefLabel', $prefLabel, 'en');
                }
            }
            fclose($fd);
            return $graph->resource($this->uri);
        } catch (Exception $e) {
            $this->model->getLogger()->info("LOC resolution failed for <{$this->uri}>: $e");
            return null;
        }
    }
}
