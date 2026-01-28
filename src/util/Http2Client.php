<?php

namespace EasyRdf\Http;

use EasyRdf\Exception;

/**
 * HTTP/2 compatible client using cURL
 * 
 * This client extends EasyRdf\Http\Client but uses cURL with HTTP/2
 * (E.g.Wikidata WDQS blocks HTTP/1.1 requests)
 * 
 * @see https://wikitech.wikimedia.org/wiki/Robot_policy
 */
class Http2Client extends Client
{
    /**
     * Send the HTTP request using cURL with HTTP/2 support
     *
     * @param string|null $method
     *
     * @return Response
     *
     * @throws Exception
     */
    public function request($method = null)
    {
        $uri = $this->getUri();
        if (!$uri) {
            throw new Exception('Set URI before calling Http2Client->request()');
        }

        if ($method) {
            $this->setMethod($method);
        }

        $this->redirectCounter = 0;
        $maxRedirects = $this->getConfigValue('maxredirects') ?? 5;
        
        // Build the full URL with GET parameters
        $url = $uri;
        $params = $this->getParametersGet();
        if (!empty($params)) {
            $query = http_build_query($params, '', '&');
            $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
        }

        // Initialize cURL
        $ch = curl_init($url);
        
        // Force HTTP/2
        if (defined('CURL_HTTP_VERSION_2_0')) {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
        } elseif (defined('CURL_HTTP_VERSION_2')) {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2);
        }
        
        // Basic settings
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $maxRedirects);
        
        $timeout = $this->getConfigValue('timeout') ?? 10;
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        
        // Set user agent
        $useragent = $this->getConfigValue('useragent') ?? 'Skosmos HTTP/2 Client';
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        
        // Set HTTP method
        $httpMethod = strtoupper($this->getMethod());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
        
        // Set headers using reflection to access private property
        $headers = [];
        $reflection = new \ReflectionClass(parent::class);
        $property = $reflection->getProperty('headers');
        $property->setAccessible(true);
        $headersList = $property->getValue($this);
        
        foreach ($headersList as $header) {
            if (is_array($header) && count($header) >= 2) {
                list($name, $value) = $header;
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                $headers[] = "$name: $value";
            }
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        // Set POST data if present
        $rawData = $this->getRawData();
        if ($rawData !== null && in_array($httpMethod, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $rawData);
        }
        
        // Execute request
        $response = curl_exec($ch);
        
        if ($response === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new Exception("cURL request failed: $error (errno: $errno)");
        }
        
        // Get redirect count
        $this->redirectCounter = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
        
        curl_close($ch);
        
        // Parse the response
        return Response::fromString($response);
    }
    
    /**
     * Get configuration value using reflection
     *
     * @param string $key
     * @return mixed
     */
    private function getConfigValue($key)
    {
        // Access private config using reflection since parent doesn't have public getter
        $reflection = new \ReflectionClass(parent::class);
        $property = $reflection->getProperty('config');
        $property->setAccessible(true);
        $config = $property->getValue($this);
        return $config[$key] ?? null;
    }
}
