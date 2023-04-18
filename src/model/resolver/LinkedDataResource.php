<?php

class LinkedDataResource extends RemoteResource
{
    public function resolve(int $timeout): ?EasyRdf\Resource
    {
        // prevent parsing errors for sources which return invalid JSON (see #447)
        // 1. Unregister the legacy RDF/JSON parser, we don't want to use it
        EasyRdf\Format::unregister('json');
        // 2. Add "application/json" as a possible MIME type for the JSON-LD format
        $jsonld = EasyRdf\Format::getFormat('jsonld');
        $mimetypes = $jsonld->getMimeTypes();
        $mimetypes['application/json'] = 0.5;
        $jsonld->setMimeTypes($mimetypes);

        try {
            // change the timeout setting for external requests
            $httpclient = EasyRdf\Http::getDefaultHttpClient();
            $httpclient->setConfig(array('timeout' => $timeout));
            EasyRdf\Http::setDefaultHttpClient($httpclient);

            $graph = EasyRdf\Graph::newAndLoad(EasyRdf\Utils::removeFragmentFromUri($this->uri));
            return $graph->resource($this->uri);
        } catch (Exception $e) {
            $this->model->getLogger()->info("LD resolution failed for <{$this->uri}>: $e");
            return null;
        }

    }

}
