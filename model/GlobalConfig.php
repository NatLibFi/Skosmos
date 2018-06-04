<?php

/**
 * Setting some often needed namespace prefixes
 */
EasyRdf\RdfNamespace::set('skosmos', 'http://purl.org/net/skosmos#');
EasyRdf\RdfNamespace::set('skosext', 'http://purl.org/finnonto/schema/skosext#');
EasyRdf\RdfNamespace::set('isothes', 'http://purl.org/iso25964/skos-thes#');
EasyRdf\RdfNamespace::set('mads', 'http://www.loc.gov/mads/rdf/v1#');
EasyRdf\RdfNamespace::set('wd', 'http://www.wikidata.org/entity/');
EasyRdf\RdfNamespace::set('wdt', 'http://www.wikidata.org/prop/direct/');

/**
 * GlobalConfig provides access to the Skosmos configuration in config.ttl.
 */
class GlobalConfig extends BaseConfig {

    /** Cache reference */
    private $cache;
    /** Location of the configuration file. Used for caching. */
    private $filePath;
    /** Namespaces from vocabularies configuration file. */
    private $namespaces;
    /** EasyRdf\Graph graph */
    private $graph;

    public function __construct($config_name='/../config.ttl')
    {
        $this->cache = new Cache();
        try {
            $this->filePath = realpath( dirname(__FILE__) . $config_name );
            if (!file_exists($this->filePath)) {
                throw new Exception('config.ttl file is missing, please provide one.');
            }
            $this->initializeConfig();
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
            return;
        }
    }

    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Initialize configuration, reading the configuration file from the disk,
     * and creating the graph and resources objects. Uses a cache if available,
     * in order to avoid re-loading the complete configuration on each request.
     */
    private function initializeConfig()
    {
        try {
            // use APC user cache to store parsed config.ttl configuration
            if ($this->cache->isAvailable()) {
                // @codeCoverageIgnoreStart
                $key = realpath($this->filePath) . ", " . filemtime($this->filePath);
                $nskey = "namespaces of " . $key;
                $this->graph = $this->cache->fetch($key);
                $this->namespaces = $this->cache->fetch($nskey);
                if ($this->graph === false || $this->namespaces === false) { // was not found in cache
                    # EasyRDF appears to prepend the file location# to the nodes without a namespace
                    EasyRdf\RdfNamespace::set('emptyns', $this->filePath . '#');
                    $this->parseConfig($this->filePath);
                    $this->cache->store($key, $this->graph);
                    $this->cache->store($nskey, $this->namespaces);
                }
                // @codeCoverageIgnoreEnd
            } else { // APC not available, parse on every request
                # EasyRDF appears to prepend the file location# to the nodes without a namespace
                EasyRdf\RdfNamespace::set('emptyns', $this->filePath . '#');
                $this->parseConfig($this->filePath);
            }

            $configResources = $this->graph->allOfType("skosmos:Configuration");
            if (is_null($configResources) || !is_array($configResources) || count($configResources) !== 1) {
                throw new Exception("config.ttl must have exactly one skosmos:Configuration");
            }

            $this->resource = $configResources[0];
            $this->initializeNamespaces();
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
        }      
    }

    /**
     * Parses configuration from the config.ttl file
     * @param string $filename path to config.ttl file
     */
    private function parseConfig($filename)
    {
        $this->graph = new EasyRdf\Graph();
        $parser = new SkosmosTurtleParser();
        $parser->parse($this->graph, file_get_contents($filename), 'turtle', $filename);
        $this->namespaces = $parser->getNamespaces();
    }

    /**
     * Returns the graph created after parsing the configuration file.
     * @return Graph
     */
    public function getGraph()
    {
        return $this->graph;
    }

    /**
     * Registers RDF namespaces from the config.ttl file for use by EasyRdf (e.g. serializing)
     */
    private function initializeNamespaces() {
        foreach ($this->namespaces as $prefix => $fullUri) {
            if ($prefix != '' && EasyRdf\RdfNamespace::get($prefix) === null) // if not already defined
            {
                EasyRdf\RdfNamespace::set($prefix, $fullUri);
            }
        }
    }

    /**
     * Returns the UI languages specified in the configuration or defaults to
     * only show English
     * @return array
     */
    public function getLanguages()
    {
        $languageResources = $this->getResource()->getResource('skosmos:languages');
        if (!is_null($languageResources) && !empty($languageResources)) {
            $languages = array();
            foreach ($languageResources as $languageResource) {
                $languageName = $languageResource->getLiteral('emptyns:name');
                $languageValue = $languageResource->getLiteral('emptyns:value');
                if ($languageName && $languageValue) {
                    $languages[$languageName->getValue()] = $languageValue->getValue();
                }
            }
            return $languages;
        } else {
            return array('en' => 'en_GB.utf8');
        }
    }

    /**
     * Returns the external HTTP request timeout in seconds or the default value
     * of 5 seconds if not specified in the configuration.
     * @return integer
     */
    public function getHttpTimeout()
    {
        return $this->getLiteral('skosmos:httpTimeout', 5);
    }

    /**
     * Returns the SPARQL HTTP request timeout in seconds or the default value
     * of 20 seconds if not specified in the configuration.
     * @return integer
     */
    public function getSparqlTimeout()
    {
        return $this->getLiteral('skosmos:sparqlTimeout', 20);
    }

    /**
     * Returns the sparql endpoint address defined in the configuration. If
     * not then defaulting to http://localhost:3030/ds/sparql
     * @return string
     */
    public function getDefaultEndpoint()
    {
        return $this->getLiteral('skosmos:sparqlEndpoint', 'http://localhost:3030/ds/sparql');
    }

    /**
     * Returns the maximum number of items to return in transitive queries if defined
     * in the configuration or the default value of 1000.
     * @return integer
     */
    public function getDefaultTransitiveLimit()
    {
        return $this->getLiteral('skosmos:transitiveLimit', 1000);
    }

    /**
     * Returns the maximum number of items to load at a time if defined
     * in the configuration or the default value of 20.
     * @return integer
     */
    public function getSearchResultsSize()
    {
        return $this->getLiteral('skosmos:searchResultsSize', 20);
    }

    /**
     * Returns the configured location for the twig template cache and if not
     * defined defaults to "/tmp/skosmos-template-cache"
     * @return string
     */
    public function getTemplateCache()
    {
        return $this->getLiteral('TEMPLATE_CACHE', '/tmp/skosmos-template-cache');
    }

    /**
     * Returns the defined sparql-query extension eg. "JenaText" or
     * if not defined falling back to SPARQL 1.1
     * @return string
     */
    public function getDefaultSparqlDialect()
    {
        return $this->getLiteral('DEFAULT_SPARQL_DIALECT', 'Generic');
    }

    /**
     * Returns the feedback address defined in the configuration.
     * @return string
     */
    public function getFeedbackAddress()
    {
        return $this->getLiteral('FEEDBACK_ADDRESS', null);
    }

    /**
     * Returns the feedback sender address defined in the configuration.
     * @return string
     */
    public function getFeedbackSender()
    {
        return $this->getLiteral('FEEDBACK_SENDER', null);
    }

    /**
     * Returns the feedback envelope sender address defined in the configuration.
     * @return string
     */
    public function getFeedbackEnvelopeSender()
    {
        return $this->getLiteral('FEEDBACK_ENVELOPE_SENDER', null);
    }

    /**
     * Returns true if exception logging has been configured.
     * @return boolean
     */
    public function getLogCaughtExceptions()
    {
        return $this->getBoolean('skosmos:logCaughtExceptions', FALSE);
    }

    /**
     * Returns true if browser console logging has been enabled,
     * @return boolean
     */
    public function getLoggingBrowserConsole()
    {
        return $this->getBoolean('skosmos:logBrowserConsole', FALSE);
    }

    /**
     * Returns the name of a log file if configured, or NULL otherwise.
     * @return string
     */
    public function getLoggingFilename()
    {
        return $this->getLiteral('skosmos:logFileName', null);
    }

    /**
     * @return string
     */
    public function getServiceName()
    {
        return $this->getLiteral('skosmos:serviceName', 'Skosmos');
    }

    /**
     * @return string
     */
    public function getCustomCss()
    {
        return $this->getLiteral('skosmos:customCss', null);
    }

    /**
     * @return boolean
     */
    public function getUiLanguageDropdown()
    {
        return $this->getBoolean('skosmos:uiLanguageDropdown', FALSE);
    }

    /**
     * @return string
     */
    public function getBaseHref()
    {
        return $this->getLiteral('skosmos:baseHref', null);
    }

    /**
     * @return string
     */
    public function getGlobalPlugins()
    {
        return explode(' ', $this->getLiteral('skosmos:globalPlugins', null));
    }

    /**
     * @return boolean
     */
    public function getHoneypotEnabled()
    {
        return $this->getBoolean('skosmos:uiHoneypotEnabled', TRUE);
    }

    /**
     * @return integer
     */
    public function getHoneypotTime()
    {
        return $this->getLiteral('skosmos:uiHoneypotTime', 5);
    }

    /**
     * @return boolean
     */
    public function getCollationEnabled()
    {
        return $this->getBoolean('skosmos:sparqlCollationEnabled', FALSE);
    }
}
