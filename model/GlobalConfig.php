<?php

/**
 * GlobalConfig provides access to the Skosmos configuration in config.inc.
 */
class GlobalConfig {
    private $languages;

    public function __construct($config_name='/../config.inc') 
    {
        try {
            $file_path = dirname(__FILE__) . $config_name;
            if (!file_exists($file_path)) {
                throw new Exception('config.inc file is missing, please provide one.');
            }
            require_once($file_path);
            if (isset($LANGUAGES)) {
                $this->languages = $LANGUAGES;
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage();
            return;
        }
    }
    
    private function getConstant($name, $default)
    {
        if (defined($name) && constant($name)) {
            return constant($name);
        }
        return $default;
    }

    /**
     * Returns the UI languages specified in the configuration or defaults to
     * only show English
     * @return array 
     */
    public function getLanguages() 
    {
        if ($this->languages) {
            return $this->languages;
        }
        return array('en' => 'en_GB.utf8');
    }
    
    /**
     * Returns the vocabulary configuration file specified the configuration
     * or vocabularies.ttl if not found.
     * @return string
     */
    public function getVocabularyConfigFile() 
    {
        return $this->getConstant('VOCABULARIES_FILE', 'vocabularies.ttl');
    }
    
    /**
     * Returns the external HTTP request timeout in seconds or the default value
     * of 5 seconds if not specified in the configuration.
     * @return integer
     */
    public function getHttpTimeout() 
    {
        return $this->getConstant('HTTP_TIMEOUT', 5);
    }
    
    /**
     * Returns the SPARQL HTTP request timeout in seconds or the default value
     * of 20 seconds if not specified in the configuration.
     * @return integer
     */
    public function getSparqlTimeout() 
    {
        return $this->getConstant('SPARQL_TIMEOUT', 20);
    }
    
    /**
     * Returns the sparql endpoint address defined in the configuration. If
     * not then defaulting to http://localhost:3030/ds/sparql 
     * @return string
     */
    public function getDefaultEndpoint() 
    {
        return $this->getConstant('DEFAULT_ENDPOINT', 'http://localhost:3030/ds/sparql');
    }
    
    /**
     * @return string
     */
    public function getSparqlGraphStore() 
    {
        return $this->getConstant('SPARQL_GRAPH_STORE', null);
    }
    
    /**
     * Returns the maximum number of items to return in transitive queries if defined
     * in the configuration or the default value of 1000.
     * @return integer 
     */
    public function getDefaultTransitiveLimit() 
    {
        return $this->getConstant('DEFAULT_TRANSITIVE_LIMIT', 1000);
    }
    
    /**
     * Returns the maximum number of items to return in search queries if defined
     * in the configuration or the default value of 100.
     * @return integer 
     */
    public function getDefaultSearchLimit() 
    {
        return $this->getConstant('DEFAULT_SEARCH_LIMIT', 100);
    }
    
    /**
     * Returns the configured location for the twig template cache and if not
     * defined defaults to "/tmp/skosmos-template-cache"
     * @return string
     */
    public function getTemplateCache() 
    {
        return $this->getConstant('TEMPLATE_CACHE', '/tmp/skosmos-template-cache');
    }
    
    /**
     * Returns the defined sparql-query extension eg. "JenaText" or 
     * if not defined falling back to SPARQL 1.1
     * @return string
     */
    public function getDefaultSparqlDialect() 
    {
        return $this->getConstant('DEFAULT_SPARQL_DIALECT', 'Generic');
    }

    /**
     * Returns the feedback address defined in the configuration.
     * @return string
     */
    public function getFeedbackAddress() 
    {
        return $this->getConstant('FEEDBACK_ADDRESS', null);
    }
    
    /**
     * Returns true if exception logging has been configured.
     * @return boolean 
     */
    public function getLogCaughtExceptions() 
    {
        return $this->getConstant('LOG_CAUGHT_EXCEPTIONS', FALSE);
    }
    
    /**
     * @return string
     */
    public function getServiceName() 
    {
        return $this->getConstant('SERVICE_NAME', 'Skosmos');
    }
    
    /**
     * @return string
     */
    public function getServiceTagline() 
    {
        return $this->getConstant('SERVICE_TAGLINE', null);
    }
    
    /**
     * @return string
     */
    public function getServiceLogo() 
    {
        return $this->getConstant('SERVICE_LOGO', null);
    }
    
    /**
     * @return string
     */
    public function getCustomCss() 
    {
        return $this->getConstant('CUSTOM_CSS', null);
    }
    
    /**
     * @return boolean
     */
    public function getUiLanguageDropdown() 
    {
        return $this->getConstant('UI_LANGUAGE_DROPDOWN', FALSE);
    }
    
    /**
     * @return string
     */
    public function getBaseHref() 
    {
        return $this->getConstant('BASE_HREF', null);
    }
    
    /**
     * @return string
     */
    public function getGlobalPlugins() 
    {
        return explode(' ', $this->getConstant('GLOBAL_PLUGINS', null));
    }
}
