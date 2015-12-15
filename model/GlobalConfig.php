<?php

/**
 * GlobalConfig provides access to the Skosmos configuration in config.inc.
 */
class GlobalConfig {
    private $languages;

    public function __construct($config_name=null) 
    {
        try {
            $file_path = dirname(__FILE__);
            if ($config_name !== null) {
                $file_path .= $config_name;
            } else {
                $file_path .= '/../config.inc';
            }
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
        if (defined('VOCABULARIES_FILE')) {
            return VOCABULARIES_FILE;
        }
        return 'vocabularies.ttl';
    }
    
    /**
     * Returns the external HTTP request timeout in seconds or the default value
     * of 5 seconds if not specified in the configuration.
     * @return integer
     */
    public function getHttpTimeout() 
    {
        if (defined('HTTP_TIMEOUT')) {
            return HTTP_TIMEOUT;
        }
        return 5;
    }
    
    /**
     * Returns the sparql endpoint address defined in the configuration. If
     * not then defaulting to http://localhost:3030/ds/sparql 
     * @return string
     */
    public function getDefaultEndpoint() 
    {
        if (defined('DEFAULT_ENDPOINT')) {
            return DEFAULT_ENDPOINT;
        }
        return 'http://localhost:3030/ds/sparql';
    }
    
    /**
     * @return string
     */
    public function getSparqlGraphStore() 
    {
        if (defined('SPARQL_GRAPH_STORE')) {
            return SPARQL_GRAPH_STORE;
        }
        return null;
    }
    
    /**
     * Returns the maximum number of items to return in transitive queries if defined
     * in the configuration or the default value of 1000.
     * @return integer 
     */
    public function getDefaultTransitiveLimit() 
    {
        if (defined('DEFAULT_TRANSITIVE_LIMIT')) {
            return DEFAULT_TRANSITIVE_LIMIT;
        }
        return 1000;
    }
    
    /**
     * Returns the maximum number of items to return in search queries if defined
     * in the configuration or the default value of 100.
     * @return integer 
     */
    public function getDefaultSearchLimit() 
    {
        if (defined('DEFAULT_SEARCH_LIMIT')) {
            return DEFAULT_SEARCH_LIMIT;
        }
        return 100;
    }
    
    /**
     * Returns the configured location for the twig template cache and if not
     * defined defaults to "/tmp/skosmos-template-cache"
     * @return string
     */
    public function getTemplateCache() 
    {
        if (defined('TEMPLATE_CACHE')) {
            return TEMPLATE_CACHE;
        }
        return '/tmp/skosmos-template-cache';
    }
    
    /**
     * Returns the defined sparql-query extension eg. "JenaText" or 
     * if not defined falling back to SPARQL 1.1
     * @return string
     */
    public function getDefaultSparqlDialect() 
    {
        if (defined('DEFAULT_SPARQL_DIALECT')) {
            return DEFAULT_SPARQL_DIALECT;
        }
        return 'Generic';
    }

    /**
     * Returns the feedback address defined in the configuration.
     * @return string
     */
    public function getFeedbackAddress() 
    {
        if (defined('FEEDBACK_ADDRESS')) {
            return FEEDBACK_ADDRESS;
        }
        return null;
    }
    
    /**
     * Returns true if exception logging has been configured.
     * @return boolean 
     */
    public function getLogCaughtExceptions() 
    {
        if (defined('LOG_CAUGHT_EXCEPTIONS')) {
            return LOG_CAUGHT_EXCEPTIONS;
        }
        return FALSE;
    }
    
    /**
     * @return string
     */
    public function getServiceName() 
    {
        if (defined('SERVICE_NAME')) {
            return SERVICE_NAME;
        }
        return 'Skosmos';
    }
    
    /**
     * @return string
     */
    public function getServiceTagline() 
    {
        if (defined('SERVICE_TAGLINE')) {
            return SERVICE_TAGLINE;
        }
        return null;
    }
    
    /**
     * @return string
     */
    public function getServiceLogo() 
    {
        if (defined('SERVICE_LOGO')) {
            return SERVICE_LOGO;
        }
        return null;
    }
    
    /**
     * @return string
     */
    public function getCustomCss() 
    {
        if (defined('CUSTOM_CSS')) {
            return CUSTOM_CSS;
        }
        return null;
    }
    
    /**
     * @return boolean
     */
    public function getUiLanguageDropdown() 
    {
        if (defined('UI_LANGUAGE_DROPDOWN')) {
            return UI_LANGUAGE_DROPDOWN;
        }
        return FALSE;
    }
    
    /**
     * @return string
     */
    public function getBaseHref() 
    {
        if (defined('BASE_HREF')) {
            return BASE_HREF;
        }
        return null;
    }
}
