<?php

/**
 * VocabularyConfig provides access to the vocabulary configuration defined in config.ttl.
 */
class VocabularyConfig extends BaseConfig
{
    private $pluginRegister;
    private $pluginParameters = array();
    private $languageOrderCache = array();
    private $labelOverrides = array();

    public const DEFAULT_PROPERTY_ORDER = array("rdf:type", "dc:isReplacedBy",
    "skos:definition", "skos:broader", "isothes:broaderGeneric",
    "isothes:broaderPartitive", "isothes:broaderInstantial",
    "skos:narrower", "isothes:narrowerGeneric", "isothes:narrowerPartitive",
    "isothes:narrowerInstantial", "skos:related", "skos:altLabel",
    "skos:note", "skos:scopeNote", "skos:historyNote", "rdfs:comment",
    "dc11:source", "dc:source", "skosmos:memberOf", "skosmos:memberOfArray");

    public const ISO25964_PROPERTY_ORDER = array("rdf:type", "dc:isReplacedBy",
    // ISO 25964 allows placing all text fields (inc. SN and DEF) together
    // so we will do that, except for HN, which is clearly administrative
    "skos:note", "skos:scopeNote", "skos:definition", "rdfs:comment",
    "dc11:source", "dc:source", "skos:altLabel", "skos:broader",
    "isothes:broaderGeneric", "isothes:broaderPartitive",
    "isothes:broaderInstantial", "skos:narrower", "isothes:narrowerGeneric",
    "isothes:narrowerPartitive", "isothes:narrowerInstantial",
    "skos:related", "skos:historyNote", "skosmos:memberOf",
    "skosmos:memberOfArray");

    public function __construct($resource, $globalPlugins=array())
    {
        $this->resource = $resource;
        $this->globalPlugins = $globalPlugins;
        $this->setPropertyLabelOverrides();
        $pluginArray = $this->getPluginArray();
        $this->pluginRegister = new PluginRegister($pluginArray);
    }

    /**
     * Get an ordered array of plugin names with order configured in skosmos:vocabularyPlugins
     * @return array of plugin names
     */
    public function getPluginArray(): array
    {
        $this->setParameterizedPlugins();
        $pluginArray = array();
        $vocabularyPlugins = $this->resource->getResource('skosmos:vocabularyPlugins');
        if (!$vocabularyPlugins instanceof EasyRdf\Collection) {
            $vocabularyPlugins = $this->resource->all('skosmos:vocabularyPlugins');
        }
        if ($vocabularyPlugins) {
            foreach ($vocabularyPlugins as $plugin) {
                if ($plugin instanceof EasyRdf\Literal) {
                    $pluginArray[] = $plugin->getValue();
                } else {
                    $pluginArray[] = $plugin->getLiteral('skosmos:usePlugin')->getValue();
                }
            }
        }
        $pluginArray = array_merge($pluginArray, $this->globalPlugins);

        $paramPlugins = $this->resource->allResources('skosmos:useParamPlugin');
        if ($paramPlugins) {
            foreach ($paramPlugins as $plugin) {
                $pluginArray[] = $plugin->getLiteral('skosmos:usePlugin')->getValue();
            }
        }
        $plugins = $this->resource->allLiterals('skosmos:usePlugin');
        if ($plugins) {
            foreach ($plugins as $pluginlit) {
                $pluginArray[] = $pluginlit->getValue();
            }
        }
        return array_values(array_unique($pluginArray));
    }

    /**
     * Sets array of parameterized plugins
     * @return void
     */
    private function setParameterizedPlugins(): void
    {
        $this->pluginParameters = array();

        $vocabularyPlugins = $this->resource->getResource('skosmos:vocabularyPlugins');
        if (!$vocabularyPlugins instanceof EasyRdf\Collection) {
            $vocabularyPlugins = $this->resource->all('skosmos:vocabularyPlugins');
        }
        if ($vocabularyPlugins) {
            foreach ($vocabularyPlugins as $plugin) {
                if ($plugin instanceof EasyRdf\Resource) {
                    $this->setPluginParameters($plugin);
                }
            }
        }
        $pluginResources = $this->resource->allResources('skosmos:useParamPlugin');
        if ($pluginResources) {
            foreach ($pluginResources as $pluginResource) {
                $this->setPluginParameters($pluginResource);
            }
        }
    }

    /**
     * Updates array of parameterized plugins adding parameter values
     * @param Easyrdf\Resource $pluginResource
     * @return void
     */
    private function setPluginParameters(Easyrdf\Resource $pluginResource): void
    {
        $pluginName = $pluginResource->getLiteral('skosmos:usePlugin')->getValue();
        $this->pluginParameters[$pluginName] = array();

        $pluginParams = $pluginResource->allResources('skosmos:parameters');
        foreach ($pluginParams as $parameter) {

            $paramLiterals = $parameter->allLiterals('schema:value');
            foreach ($paramLiterals as $paramLiteral) {
                $paramName = $parameter->getLiteral('schema:propertyID')->getValue();
                $paramValue = $paramLiteral->getValue();
                $paramLang = $paramLiteral->getLang();
                if ($paramLang) {
                    $paramName .= '_' . $paramLang;
                }
                $this->pluginParameters[$pluginName][$paramName] = $paramValue;
            }
        }
    }

    /**
     * Sets array of configured property label overrides
     *  @return void
     */
    private function setPropertyLabelOverrides(): void
    {
        $this->labelOverrides = array();
        $overrides = $this->resource->allResources('skosmos:propertyLabelOverride');
        if (!empty($overrides)) {
            foreach ($overrides as $override) {
                $this->setLabelOverride($override);
            }
        }
    }

    /**
     * Updates array of label overrides by adding a new override from the configuration file
     * @param Easyrdf\Resource $labelOverride
     * @return void
     */
    private function setLabelOverride(Easyrdf\Resource $override): void
    {
        $labelProperty = $override->getResource('skosmos:property');
        $labelPropUri = $labelProperty->shorten();
        if (empty($this->labelOverrides[$labelPropUri])) {
            $this->labelOverrides[$labelPropUri]  = array();
        }
        $newOverrides = array();

        $labels = $override->allLiterals('rdfs:label'); //property label overrides
        foreach ($labels as $label) {
            $newOverrides['label'][$label->getLang()] = $label->getValue();
        }
        $descriptions = $override->allLiterals('rdfs:comment'); //optionally override property label tooltips
        foreach ($descriptions as $description) {
            $newOverrides['description'][$description->getLang()] = $description->getValue();
        }
        $this->labelOverrides[$labelPropUri] = array_merge($newOverrides, $this->labelOverrides[$labelPropUri]);
    }

    /**
     * Get the SPARQL endpoint URL for this vocabulary
     *
     * @return string|null endpoint URL, or null if not set
     */
    public function getSparqlEndpoint()
    {
        $endpoint = $this->resource->get('void:sparqlEndpoint');
        if ($endpoint) {
            return $endpoint->getUri();
        }
        return null;
    }

    /**
     * Get the SPARQL graph URI for this vocabulary
     *
     * @return string|null graph URI, or null if not set
     */
    public function getSparqlGraph()
    {
        $graph = $this->resource->get('skosmos:sparqlGraph');
        if ($graph) {
            $graph = $graph->getUri();
        }

        return $graph;
    }

    /**
     * Get the SPARQL dialect for this vocabulary
     *
     * @return string|null dialect name
     */
    public function getSparqlDialect()
    {
        $dialect = $this->resource->get('skosmos:sparqlDialect');
        if ($dialect) {
            $dialect = $dialect->getValue();
        }

        return $dialect;
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
     * Returns a short name for a vocabulary if configured. If that has not been set
     * using vocabId as a fallback.
     * @return string
     */
    public function getShortName()
    {
        $shortname = $this->getLiteral('skosmos:shortName');
        if ($shortname) {
            return $shortname;
        }

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
        return $this->getLiteral('dc:title', false, $lang);
    }

    /**
     * Returns the sorting strategy for notation codes set in the config.ttl
     * config: either "lexical", "natural", or null if sorting by notations is
     * disabled. A "true" value in the configuration file is interpreted as
     * "lexical".
     * @return string|bool
     */
    public function getSortByNotation(): ?string
    {
        $value = $this->getLiteral('skosmos:sortByNotation');
        if ($value == "lexical" || $value == "natural") {
            return $value;
        }
        // not a special value - interpret as boolean instead
        $bvalue = $this->getBoolean('skosmos:sortByNotation');
        // "true" is interpreted as "lexical"
        return $bvalue ? "lexical" : null;
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

            $langLit = $url->getLiteral('dc:language');

            if ($langLit != null) {
                //when the mimetype has language variants
                $dataUrlLang = $langLit->getValue();

                if (!isset($ret[$mimetype])) {
                    $arr = array();
                } else {
                    $arr = $ret[$mimetype];
                }
                $arr[$dataUrlLang] = $url->getURI();
                $ret[$mimetype] = $arr;
            } else {
                $ret[$mimetype] = $url->getURI();
            }
        }
        return $ret;
    }

    /**
     * Returns the main Concept Scheme URI of that Vocabulary,
     * or null if not set.
     * @return string concept scheme URI or null
     */

    public function getMainConceptSchemeURI()
    {
        $val = $this->resource->getResource("skosmos:mainConceptScheme");
        if ($val) {
            return $val->getURI();
        }

        return null;
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
     * @return array array class URI or null
     */

    public function getAdditionalSearchProperties()
    {
        $resources = $this->resource->allResources("skosmos:showPropertyInSearch");
        $ret = array();
        foreach ($resources as $res) {
            $prop = $res->getURI();
            if (EasyRdf\RdfNamespace::shorten($prop) !== null) { // shortening property labels if possible
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
            if (EasyRdf\RdfNamespace::shorten($prop) !== null) { // shortening property labels if possible
                $prop = EasyRdf\RdfNamespace::shorten($prop);
            }

            if ($prop === $property) {
                return true;
            }

        }
        return false;
    }

    /**
     * Returns a boolean value set in the config.ttl config.
     * @return boolean
     */
    public function getShowTopConcepts()
    {
        return $this->getBoolean('skosmos:showTopConcepts');
    }

    /**
     * Returns a boolean value set in the config.ttl config.
     * @return boolean
     */
    public function showConceptSchemesInHierarchy()
    {
        return $this->getBoolean('skosmos:conceptSchemesInHierarchy');
    }

    /**
     * Returns a boolean value set in the config.ttl config.
     * @return boolean defaults to true if fetching hasn't been explicitly denied.
     */
    public function getExternalResourcesLoading()
    {
        return $this->getBoolean('skosmos:loadExternalResources', true);
    }

    /**
     * Returns a boolean value set in the config.ttl config.
     * @return boolean
     */
    public function getShowLangCodes()
    {
        return $this->getBoolean('skosmos:explicitLanguageTags');
    }

    /**
     * Returns a boolean value set in the config.ttl config.
     * @return boolean
     */
    public function searchByNotation()
    {
        return $this->getBoolean('skosmos:searchByNotation');
    }

    /**
     * Returns skosmos:marcSourcecode value set in config.ttl.
     * @return string marcsource name
     */
    public function getMarcSourceCode($lang = null)
    {
        return $this->getLiteral('skosmos:marcSourceCode', false, $lang);
    }

    /**
     * Returns the boolean value of the skosmos:showNotationAsProperty setting.
     * @return boolean
     */
    public function getShowNotationAsProperty()
    {
        return $this->getBoolean('skosmos:showNotationAsProperty', true);
    }

    /**
     * Returns a boolean value set in the config.ttl config.
     * @return array array of concept class URIs (can be empty)
     */
    public function getIndexClasses()
    {
        return $this->getResources("skosmos:indexShowClass");
    }

    /**
     * Returns skosmos:externalProperty values set in the config.ttl config.
     * @return array array of external property URIs (can be empty)
     */
    public function getExtProperties()
    {
        return $this->getResources("skosmos:externalProperty");
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
            $langlit = Punic\Language::getName($lang->getValue(), $this->getLang());
            $ret[$langlit] = $lang->getValue();
        }
        ksort($ret);

        return $ret;
    }

    /**
     * Returns the parameters of parameterized plugins
     * @return array of plugin parameters
     */
    public function getPluginParameters()
    {
        return $this->pluginParameters;
    }

    /**
     * Get the list of property label overrides
     * @return array of custom property labels
     */
    public function getPropertyLabelOverrides()
    {
        return $this->labelOverrides;
    }

    /**
     * Returns the vocabulary default sidebar view.
     * @return string name of the view
     */
    public function getDefaultSidebarView()
    {
        $defview = $this->resource->getLiteral('skosmos:defaultSidebarView');
        $sidebarViews = $this->getSidebarViews();
        if ($defview) {
            $value = $defview->getValue();
            if (in_array($value, $sidebarViews)) {
                return $value;
            } else {
                return $sidebarViews[0]; // if not in sidebarViews, displaying first provided view
            }
        }

        if (in_array('alphabetical', $sidebarViews)) {
            return 'alphabetical'; // if not defined, displaying alphabetical index
        } else {
            return $sidebarViews[0]; // if no alphabetical index, displaying first provided view
        }
    }

    /**
     * Returns the concept page default sidebar view.
     * @return string name of the view
     */
    public function getDefaultConceptSidebarView()
    {
        $defview = $this->resource->getLiteral('skosmos:defaultConceptSidebarView');
        $sidebarViews = $this->getSidebarViews();
        if ($defview) {
            $value = $defview->getValue();
            if (in_array($value, $sidebarViews)) {
                return $value;
            } else {
                return $sidebarViews[0]; // if not in sidebarViews, displaying first provided view
            }
        }
        
        if (in_array('hierarchy', $sidebarViews)) {
            return 'hierarchy'; // if not defined, displaying hierarchy
        } else {
            return $sidebarViews[0]; // if no hierarchy, displaying first provided view
        }
    }

    /**
     * Returns sidebar views used in this vocabulary
     * @return array of available views
     */
    public function getSidebarViews()
    {
        $views = $this->resource->getResource('skosmos:sidebarViews');
        if ($views) {
            $viewsArray = array();
            foreach ($views as $view) {
                if (in_array($view, array('hierarchy', 'alphabetical', 'fullalphabetical', 'changes', 'groups'))) {
                    $viewsArray[] = $view->getValue();
                }
            }
            return $viewsArray;
        }
        return array('alphabetical', 'hierarchy', 'groups', 'changes'); // if not defined, using all views in default order

    }

    /**
     * Extracts the vocabulary id string from the baseuri of the vocabulary.
     * @return string identifier eg. 'mesh'.
     */
    public function getId()
    {
        $uriparts = explode("#", $this->resource->getURI());
        if (count($uriparts) != 1) {
            // hash namespace
            return $uriparts[1];
        }

        // slash namespace
        $uriparts = explode("/", $this->resource->getURI());

        return $uriparts[count($uriparts) - 1];
    }

    public function getShowStatistics()
    {
        return $this->getBoolean('skosmos:showStatistics', true);
    }

    public function getPluginRegister()
    {
        return $this->pluginRegister;
    }

    /**
     * Returns the property/properties used for visualizing concept hierarchies.
     * @return array array class URI or null
     */

    public function getHierarchyProperty()
    {
        $resources = $this->resource->allResources("skosmos:hierarchyProperty");
        $ret = array();
        foreach ($resources as $res) {
            $prop = $res->getURI();
            if (EasyRdf\RdfNamespace::shorten($prop) !== null) { // prefixing if possible
                $prop = EasyRdf\RdfNamespace::shorten($prop);
            }

            $ret[] = $prop;
        }
        return empty($ret) ? array('skos:broader') : $ret;
    }

    /**
     * Returns a boolean value set in the config.ttl config.
     * @return boolean
     */
    public function showNotation()
    {
        return $this->getBoolean('skosmos:showNotation', true);
    }

    /**
     * Returns the alphabetical list qualifier in this vocabulary,
     * or null if not set.
     * @return EasyRdf\Resource|null alphabetical list qualifier resource or null
     */
    public function getAlphabeticalListQualifier()
    {
        return $this->resource->getResource('skosmos:alphabeticalListQualifier');
    }

    /**
     * Returns a boolean value set in the config.ttl config.
     * @return boolean
     */
    public function getShowDeprecated()
    {
        return $this->getBoolean('skosmos:showDeprecated', false);
    }

    /**
     * Returns a boolean value set in the config.ttl config.
     * @return boolean
     */
    public function getShowDeprecatedChanges()
    {
        return $this->getBoolean('skosmos:showDeprecatedChanges', false);
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

    /**
     * Returns an array of fallback languages that is ordered by priority and
     * defined in the vocabulary configuration as a collection.
     * Additionally, the chosen content language is inserted with the highest priority
     * and the vocab default language is inserted with the lowest priority.
     * @param string $clang
     * @return array of language code strings
     */
    public function getLanguageOrder($clang)
    {
        if (array_key_exists($clang, $this->languageOrderCache)) {
            return $this->languageOrderCache[$clang];
        }
        $ret = array($clang);
        $fallbacks = !empty($this->resource->get('skosmos:fallbackLanguages')) ? $this->resource->get('skosmos:fallbackLanguages') : array();
        foreach ($fallbacks as $lang) {
            if (!in_array($lang, $ret)) {
                $ret[] = (string)$lang; // Literal to string conversion
            }
        }
        if (!in_array($this->getDefaultLanguage(), $ret)) {
            $ret[] = (string)$this->getDefaultLanguage();
        }
        foreach ($this->getLanguages() as $lang) {
            if (!in_array($lang, $ret)) {
                $ret[] = $lang;
            }
        }
        // store in cache so this doesn't have to be computed again
        $this->languageOrderCache[$clang] = $ret;
        return $ret;
    }

    /**
     * @return boolean
     */
    public function isUseModifiedDate()
    {
        return $this->getBoolean('skosmos:useModifiedDate', false);
    }

    /**
     * @return array
     */
    public function getPropertyOrder()
    {
        $order = $this->getResource()->getResource('skosmos:propertyOrder');
        if ($order === null) {
            return self::DEFAULT_PROPERTY_ORDER;
        }

        $short = EasyRdf\RdfNamespace::shorten($order);
        if ($short == 'skosmos:iso25964PropertyOrder') {
            return self::ISO25964_PROPERTY_ORDER;
        } elseif ($short == 'skosmos:defaultPropertyOrder') {
            return self::DEFAULT_PROPERTY_ORDER;
        }

        // check for custom order definition
        $orderList = $order->getResource('rdf:value');
        if ($orderList !== null && $orderList instanceof EasyRdf\Collection) {
            $ret = array();
            foreach ($orderList as $prop) {
                $short = $prop->shorten();
                $ret[] = ($short !== null) ? $short : $prop->getURI();
            }
            return $ret;
        }

        trigger_error("Property order for vocabulary '{$this->getShortName()}' unknown, using default order", E_USER_WARNING);
        return self::DEFAULT_PROPERTY_ORDER;
    }
}
