<?php

/**
 * VocabularyConfig provides access to the vocabulary configuration defined in vocabularies.ttl.
 */
class VocabularyConfig extends DataObject
{
    private $plugins;

    public function __construct($resource, $globalPlugins=array()) 
    {
        $this->resource = $resource;
        $plugins = $this->resource->allLiterals('skosmos:usePlugin');
        $pluginArray = array();
        if ($plugins) {
            foreach ($plugins as $pluginlit) {
                $pluginArray[] = $pluginlit->getValue();
            }
        }
        $this->plugins = new PluginRegister(array_merge($globalPlugins, $pluginArray));
    }

    /**
     * Returns a boolean value based on a literal value from the vocabularies.ttl configuration.
     * @param string $property the property to query
     * @param boolean $default the default value if the value is not set in configuration
     */
    private function getBoolean($property, $default = false)
    {
        $val = $this->resource->getLiteral($property);
        if ($val) {
            return filter_var($val->getValue(), FILTER_VALIDATE_BOOLEAN);
        }

        return $default;
    }
    
    /**
     * Returns a boolean value based on a literal value from the vocabularies.ttl configuration.
     * @param string $property the property to query
     * @param string $lang preferred language for the literal,
     */
    private function getLiteral($property, $lang=null)
    {
        if (!isset($lang)) {;
            $lang = $this->getEnvLang();
        }

        $literal = $this->resource->getLiteral($property, $lang);
        if ($literal) {
            return $literal->getValue();
        }

        // not found with selected language, try any language
        $literal = $this->resource->getLiteral($property);
        if ($literal)
          return $literal->getValue();
    }

    /**
     * Get the default language of this vocabulary
     * @return string default language, e.g. 'en'
     */

    public function getDefaultLanguage()
    {
        $deflang = $this->resource->getLiteral('skosmos:defaultLanguage');
        if ($deflang) {
            return $deflang->getValue();
        }

        $langs = $this->getLanguages();
        $deflang = reset($langs); // picking the first one from the list with reset since the keys are not numeric
        if (sizeof($langs) > 1) {
            trigger_error("Default language for vocabulary '" . $this->getShortName() . "' unknown, choosing '$deflang'.", E_USER_WARNING);
        }

        return $deflang;
    }

    /**
     * Wether the alphabetical index is small enough to be shown all at once.
     * @return boolean true if all concepts can be shown at once.
     */
    public function getAlphabeticalFull()
    {
        return $this->getBoolean('skosmos:fullAlphabeticalIndex');
    }

    /**
     * Returns a short name for a vocabulary if configured. If that has not been set
     * using vocabId as a fallback.
     * @return string
     */
    public function getShortName()
    {
        $shortname = $this->getLiteral('skosmos:shortName');
        if ($shortname) 
          return $shortname;

        // if no shortname exists fall back to the id
        return $this->getId();
    }

    /**
     * Get the vocabulary feedback e-mail address and return it.
     *
     * @return string e-mail address or null if not defined.
     */
    public function getFeedbackRecipient()
    {
        $email = $this->resource->get('skosmos:feedbackRecipient');
        return isset($email) ? $email->getValue() : null;
    }

    /**
     * Returns the human readable vocabulary title.
     * @return string the title of the vocabulary
     */
    public function getTitle($lang = null)
    {
        return $this->getLiteral('dc:title', $lang);
    }

    /**
     * Returns a boolean value set in the vocabularies.ttl config.
     * @return boolean
     */
    public function sortByNotation()
    {
        return $this->getBoolean('skosmos:sortByNotation');
    }
    
    /**
     * Returns a boolean value set in the vocabularies.ttl config.
     * @return boolean
     */
    public function showChangeList()
    {
        return $this->getBoolean('skosmos:showChangeList');
    }

    /**
     * get the URLs from which the vocabulary data can be downloaded
     * @return array Array with MIME type as key, URL as value
     */
    public function getDataURLs()
    {
        $ret = array();
        $urls = $this->resource->allResources("void:dataDump");
        foreach ($urls as $url) {
            // first try dc:format and dc11:format
            $mimetypelit = $url->getLiteral('dc:format');
            if ($mimetypelit === null) {
                $mimetypelit = $url->getLiteral('dc11:format');
            }

            // if still not found, guess MIME type using file extension
            if ($mimetypelit !== null) {
                $mimetype = $mimetypelit->getValue();
            } else {
                $format = EasyRdf\Format::guessFormat(null, $url->getURI());
                if ($format === null) {
                    trigger_error("Could not guess format for <$url>.", E_USER_WARNING);
                    continue;
                }
                $mimetypes = array_keys($format->getMimeTypes());
                $mimetype = $mimetypes[0];
            }
            $ret[$mimetype] = $url->getURI();
        }
        return $ret;
    }

    /**
     * Returns the class URI used for concept groups in this vocabulary,
     * or null if not set.
     * @return string group class URI or null
     */

    public function getGroupClassURI()
    {
        $val = $this->resource->getResource("skosmos:groupClass");
        if ($val) {
            return $val->getURI();
        }

        return null;
    }

    /**
     * Returns the class URI used for thesaurus arrays in this vocabulary,
     * or null if not set.
     * @return string array class URI or null
     */

    public function getArrayClassURI()
    {
        $val = $this->resource->getResource("skosmos:arrayClass");
        if ($val) {
            return $val->getURI();
        }

        return null;
    }

    /**
     * Returns custom properties displayed on the search page if configured.
     * @return string array class URI or null
     */

    public function getAdditionalSearchProperties()
    {
        $resources = $this->resource->allResources("skosmos:showPropertyInSearch");
        $ret = array();
        foreach ($resources as $res) {
            $prop = $res->getURI();
            if (EasyRdf\RdfNamespace::shorten($prop) !== null) // shortening property labels if possible
            {
                $prop = EasyRdf\RdfNamespace::shorten($prop);
            }

            $ret[] = $prop;
        }
        return $ret;
    }

    /**
     * Queries whether the property should be shown with all the label language variations.
     * @param string $property
     * @return boolean
     */
    public function hasMultiLingualProperty($property)
    {
        $resources = $this->resource->allResources("skosmos:hasMultiLingualProperty");
        foreach ($resources as $res) {
            $prop = $res->getURI();
            if (EasyRdf\RdfNamespace::shorten($prop) !== null) // shortening property labels if possible
            {
                $prop = EasyRdf\RdfNamespace::shorten($prop);
            }

            if ($prop === $property) {
                return true;
            }

        }
        return false;
    }

    /**
     * Returns a boolean value set in the vocabularies.ttl config.
     * @return boolean
     */
    public function getShowHierarchy()
    {
        return $this->getBoolean('skosmos:showTopConcepts');
    }

    /**
     * Returns a boolean value set in the vocabularies.ttl config.
     * @return boolean
     */
    public function showConceptSchemesInHierarchy()
    {
        return $this->getBoolean('skosmos:conceptSchemesInHierarchy');
    }

    /**
     * Returns a boolean value set in the vocabularies.ttl config.
     * @return boolean defaults to true if fetching hasn't been explicitly denied.
     */
    public function getExternalResourcesLoading()
    {
        return $this->getBoolean('skosmos:loadExternalResources', true);
    }

    /**
     * Returns a boolean value set in the vocabularies.ttl config.
     * @return boolean
     */
    public function getShowLangCodes()
    {
        return $this->getBoolean('skosmos:explicitLanguageTags');
    }

    /**
     * Returns a boolean value set in the vocabularies.ttl config.
     * @return array array of concept class URIs (can be empty)
     */
    public function getIndexClasses()
    {
        $resources = $this->resource->allResources("skosmos:indexShowClass");
        $ret = array();
        foreach ($resources as $res) {
            $ret[] = $res->getURI();
        }
        return $ret;
    }

    /**
     * Get the languages supported by this vocabulary
     * @return array languages supported by this vocabulary (as language tag strings)
     */
    public function getLanguages()
    {
        $langs = $this->resource->allLiterals('skosmos:language');
        $ret = array();
        foreach ($langs as $lang) {
            $langlit = Punic\Language::getName($lang->getValue(), $this->getEnvLang());
            $ret[$langlit] = $lang->getValue();
        }
        ksort($ret);

        return $ret;
    }

    /**
     * Returns the vocabulary default sidebar view.
     * @return string name of the view
     */
    public function getDefaultSidebarView()
    {
        $defview = $this->resource->getLiteral('skosmos:defaultSidebarView');
        if ($defview) {
            $value = $defview->getValue();
            if ($value === 'groups' || $value === 'hierarchy') {
                return $value;
            }

        }
        if ($this->showAlphabeticalIndex() === false) { 
            if ($this->getShowHierarchy()) {
                return 'hierarchy';
            } else if ($this->getGroupClassURI()) {
                return 'groups';
            }
        }
        return 'alphabetical'; // if not defined displaying the alphabetical index
    }

    /**
     * Extracts the vocabulary id string from the baseuri of the vocabulary.
     * @return string identifier eg. 'mesh'.
     */
    public function getId()
    {
        $uriparts = explode("#", $this->resource->getURI());
        if (count($uriparts) != 1)
        // hash namespace
        {
            return $uriparts[1];
        }

        // slash namespace
        $uriparts = explode("/", $this->resource->getURI());

        return $uriparts[count($uriparts) - 1];
    }

    public function getShowStatistics() {
        return $this->getBoolean('skosmos:showStatistics', true);
    }

    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * Returns the property/properties used for visualizing concept hierarchies.
     * @return string array class URI or null
     */

    public function getHierarchyProperty()
    {
        $resources = $this->resource->allResources("skosmos:hierarchyProperty");
        $ret = array();
        foreach ($resources as $res) {
            $prop = $res->getURI();
            if (EasyRdf\RdfNamespace::shorten($prop) !== null) // prefixing if possible 
            {
                $prop = EasyRdf\RdfNamespace::shorten($prop);
            }

            $ret[] = $prop;
        }
        return empty($ret) ? array('skos:broader') : $ret;
    }

    /**
     * Returns a boolean value set in the vocabularies.ttl config.
     * @return boolean
     */
    public function showNotation()
    {
        return $this->getBoolean('skosmos:showNotation', true);
    }

    /**
     * Returns a boolean value set in the vocabularies.ttl config.
     * @return boolean
     */
    public function showAlphabeticalIndex()
    {
        return $this->getBoolean('skosmos:showAlphabeticalIndex', true);
    }

    /**
     * Returns the vocabulary dc:type value(s) with their labels and uris, if set in the vocabulary configuration.
     * @return array of objects or an empty array
     */
    public function getTypes($lang = null)
    {
        $resources = $this->resource->allResources("dc:type");
        $ret = array();
        foreach ($resources as $res) {
            $prop = $res->getURI();
            $label = $res->label($lang) ? $res->label($lang) : $res->label($this->getDefaultLanguage());
            $ret[] = array('uri' => $prop, 'prefLabel' =>  $label->getValue());
        }
        return $ret;
    }
}
