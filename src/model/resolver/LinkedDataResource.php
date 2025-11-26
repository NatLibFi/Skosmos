<?php

class LinkedDataResource extends RemoteResource
{
    public function resolve(int $timeout): ?EasyRdf\Resource
    {
        try {
            // change the timeout setting for external requests
            $httpclient = EasyRdf\Http::getDefaultHttpClient();
            $httpclient->setConfig(array('timeout' => $timeout, 'useragent' => 'Skosmos'));
            EasyRdf\Http::setDefaultHttpClient($httpclient);

            $graph = EasyRdf\Graph::newAndLoad(EasyRdf\Utils::removeFragmentFromUri($this->uri));
            return $graph->resource($this->uri);
        } catch (Exception $e) {
            $this->model->getLogger()->info("LD resolution failed for <{$this->uri}>: $e");
            return null;
        }

    }

}
