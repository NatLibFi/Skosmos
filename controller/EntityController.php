<?php
/**
 * Copyright (c) 2016 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/**
 * EntityController is responsible for redirecting requests to the /entity address.
 */
class EntityController extends Controller
{
    private function redirect303($url)
    {
        header("HTTP/1.1 303 See Other");
        header("Location: $url");
    }

    private function redirectREST($vocab, $uri, $targetFormat)
    {
        $baseurl = $this->getBaseHref();
        $vocid = $vocab->getId();
        $query = http_build_query(array('uri'=>$uri, 'format'=>$targetFormat));
        $url = $baseurl . "rest/v1/$vocid/data?$query";
        $this->redirect303($url);
    }
    
    private function redirectWeb($vocab, $uri)
    {
        $baseurl = $this->getBaseHref();
        $vocid = $vocab->getId();
        $localname = $vocab->getLocalName($uri);
        if ($localname !== $uri && $localname === urlencode($localname)) {
            // the URI can be shortened
            $url = $baseurl . "$vocid/page/$localname";
        } else {
            // must use full URI
            $query = http_build_query(array('uri'=>$uri));
            $url = $baseurl . "$vocid/page/?" . $query;
        }
        $this->redirect303($url);
    }

    /**
     * Perform a HTTP 303 redirect to the content-negotiated URL, either the
     * web page for a resource or a REST API URL for retrieving its data.
     * @param Request $request
     */
    public function redirect($request) {
        /* determine parameters: URI and (optional) vocabulary */
        $request->setUri($request->getQueryParam('uri'));
        if ($request->getQueryParam('vocab')) {
            // vocabulary explicitly set
            $request->setVocab($request->getQueryParam('vocab'));
        } else {
            // guess vocabulary based on URI
            $vocab = $this->model->guessVocabularyFromURI($request->getUri());
            $request->setVocab($vocab->getId());
        }

        // negotiate suitable response format
        $restFormats = explode(' ', RestController::SUPPORTED_FORMATS);
        $supportedFormats = $restFormats;
        // add HTML as supported format (make it the first element so it becomes default)
        array_unshift($supportedFormats, 'text/html');
        // optional query parameter for forcing a specific format
        $requestedFormat = $request->getQueryParam('format');

        $targetFormat = $this->negotiateFormat($supportedFormats, $request->getServerConstant('HTTP_ACCEPT'), $requestedFormat);
        
        if (in_array($targetFormat, $restFormats)) {
            $this->redirectREST($request->getVocab(), $request->getUri(), $targetFormat);
        } else {
            $this->redirectWeb($request->getVocab(), $request->getUri());
        }
    }
}
