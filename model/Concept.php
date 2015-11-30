<?php
/**
 * Copyright (c) 2012-2013 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/**
 * Dataobject for a single concept.
 */

class Concept extends VocabularyDataObject
{
    /**
     * Stores a label string if the concept has been found through
     * a altLabel/label in a another language than the ui.
     */
    private $foundby;
    /** Type of foundby match: 'alt', 'hidden' or 'lang' */
    private $foundbytype;
    /** the EasyRdf_Graph object of the concept */
    private $graph;
    private $clang;

    /** concept properties that should not be shown to users */
    private $DELETED_PROPERTIES = array(
        'skosext:broaderGeneric', # these are remnants of bad modeling
        'skosext:broaderPartitive', #

        'skos:hiddenLabel', # because it's supposed to be hidden
        'skos:prefLabel', # handled separately by getLabel
        'rdfs:label', # handled separately by getLabel

        'skos:topConceptOf', # because it's too technical, not relevant for users
        'skos:inScheme', # should be evident in any case
        'skos:member', # this is shouldn't be shown on the group page
        'dc:created', # handled separately
        'dc:modified', # handled separately
    );

    /** related concepts that should be shown to users in the appendix */
    private $MAPPING_PROPERTIES = array(
        'skos:exactMatch',
        'skos:narrowMatch',
        'skos:broadMatch',
        'skos:closeMatch',
        'skos:relatedMatch',
        'rdfs:seeAlso',
        'owl:sameAs',
    );

    /**
     * Initializing the concept object requires the following parameters.
     * @param Model $model
     * @param Vocabulary $vocab
     * @param EasyRdf_Resource $resource
     * @param EasyRdf_Graph $graph
     */
    public function __construct($model, $vocab, $resource, $graph, $clang)
    {
        parent::__construct($model, $vocab, $resource);
        $this->order = array("rdf:type", "dc:isReplacedBy", "skos:definition", "skos:broader", "skos:narrower", "skos:related", "skos:altLabel", "skosmos:memberOf", "skos:note", "skos:scopeNote", "skos:historyNote", "rdfs:comment", "dc11:source", "dc:source", "skos:prefLabel");
        $this->graph = $graph;
        $this->clang = $clang;
        // setting the Punic plugins locale for localized datetime conversions
        if ($this->clang && $this->clang !== '') {
            Punic\Data::setDefaultLocale($clang);
        }

    }

    /**
     * Returns the concept uri.
     * @return string
     */
    public function getUri()
    {
        return $this->resource->getUri();
    }

    public function getType()
    {
        return $this->resource->types();
    }

    /**
     * Returns a boolean value indicating if the concept has been deprecated.
     * @return boolean
     */
    public function getDeprecated()
    {
        foreach ($this->resource->all('rdf:type') as $type) {
            if (strpos($type->getUri(), 'DeprecatedConcept')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a label for the concept in the ui language or if not possible in any language.
     * @return string
     */
    public function getLabel()
    {
        $lang = $this->clang;
        // 1. label in current language
        if ($this->resource->label($lang) !== null) {
            return $this->resource->label($lang);
        }

        // 2. label in the vocabulary default language
        if ($this->resource->label($this->vocab->getConfig()->getDefaultLanguage()) !== null) {
            return $this->resource->label($this->vocab->getConfig()->getDefaultLanguage());
        }

        // 3. label in any language
        $label = $this->resource->label();
        // if the label lang code is a subset of the ui lang eg. en-GB
        if ($label !== null && strpos($label->getLang(), $this->getEnvLang() . '-') === 0) {
            return $label->getValue();
        }

        if ($label !== null) {
            return $label->getValue() . " (" . $label->getLang() . ")";
        }

        // empty
        return "";
    }

    /**
     * Returns a notation for the concept or null if it has not been defined.
     * @return string eg. '999'
     */
    public function getNotation()
    {
        $notation = $this->resource->get('skos:notation');
        if ($notation !== null) {
            return $notation->getValue();
        }

        return null;
    }

    /**
     * Returns the Vocabulary object or undefined if that is not available.
     * @return Vocabulary
     */
    public function getVocab()
    {
        return $this->vocab;
    }

    /**
     * Returns the vocabulary shortname string or id if that is not available.
     * @return string
     */
    public function getShortName()
    {
        return $this->vocab ? $this->vocab->getShortName() : null;
    }

    /**
     * Returns the vocabulary shortname string or id if that is not available.
     * @return string
     */
    public function getVocabTitle()
    {
        return $this->vocab ? $this->vocab->getTitle() : null;
    }

    /**
     * Setter for the $clang property.
     * @param string $clang language code eg. 'en'
     */
    public function setContentLang($clang)
    {
        $this->clang = $clang;
    }

    public function getContentLang()
    {
        return $this->clang;
    }

    /**
     * Setter for the $foundby property.
     * @param string $label label that was matched
     * @param string $type type of match: 'alt', 'hidden', or 'lang'
     */
    public function setFoundBy($label, $type)
    {
        $this->foundby = $label;
        $this->foundbytype = $type;
    }

    /**
     * Getter for the $foundby property.
     * @return string
     */
    public function getFoundBy()
    {
        return $this->foundby;
    }

    /**
     * Getter for the $foundbytype property.
     * @return string
     */
    public function getFoundByType()
    {
        return $this->foundbytype;
    }

    public function getMappingProperties()
    {
        $ret = array();

        $long_uris = $this->resource->propertyUris();
        foreach ($long_uris as &$prop) {
            if (EasyRdf_Namespace::shorten($prop) !== null) {
                // shortening property labels if possible
                $prop = $sprop = EasyRdf_Namespace::shorten($prop);
            } else {
                $sprop = "<$prop>";
            }
            // EasyRdf requires full URIs to be in angle brackets

            if (in_array($prop, $this->MAPPING_PROPERTIES) && !in_array($prop, $this->DELETED_PROPERTIES)) {
                $propres = new EasyRdf_Resource($prop, $this->graph);
                $proplabel = $propres->label($this->getEnvLang()) ? $propres->label($this->getEnvLang()) : $propres->label(); // current language
                $propobj = new ConceptProperty($prop, $proplabel);
                if ($propobj->getLabel() !== null) {
                    // only display properties for which we have a label
                    $ret[$prop] = $propobj;
                }

                // Iterating through every resource and adding these to the data object.
                foreach ($this->resource->allResources($sprop) as $val) {
                    if (isset($ret[$prop])) {
                        // checking if the target vocabulary can be found at the skosmos endpoint
                        $exuri = $val->getUri();
                        $exvoc = $this->model->guessVocabularyFromURI($exuri);
                        // if not querying the uri itself
                        if (!$exvoc) {
                            $response = null;
                            // if told to do so in the vocabulary configuration
                            if ($this->vocab->getConfig()->getExternalResourcesLoading()) {
                                $response = $this->model->getResourceFromUri($exuri);
                            }

                            if ($response) {
                                $ret[$prop]->addValue(new ConceptMappingPropertyValue($this->model, $this->vocab, $response, $prop), $this->clang);
                                continue;
                            }
                        }
                        $ret[$prop]->addValue(new ConceptMappingPropertyValue($this->model, $this->vocab, $val, $prop, $this->clang), $this->clang);
                    }
                }
            }
        }

        // sorting the properties to a order preferred in the Skosmos concept page.
        $ret = $this->arbitrarySort($ret);

        return $ret;
    }

    /**
     * Iterates over all the properties of the concept and returns those in an array.
     * @return array
     */
    public function getProperties()
    {
        $properties = array();
        $narrowers_by_uri = array();
        $in_a_collection = array();
        $members_array = array();
        $long_uris = $this->resource->propertyUris();
        $duplicates = array();
        $ret = array();

        // looking for collections and linking those with their narrower concepts
        if ($this->vocab->getConfig()->getArrayClassURI() !== null) {
            $collections = $this->graph->allOfType($this->vocab->getConfig()->getArrayClassURI());
            if (sizeof($collections) > 0) {
                // indexing the narrowers once to avoid iterating all of them with every collection
                foreach ($this->resource->allResources('skos:narrower') as $narrower) {
                    $narrowers_by_uri[$narrower->getUri()] = $narrower;
                }

                foreach ($collections as $coll) {
                    $curr_coll_members = $this->getCollectionMembers($coll, $narrowers_by_uri);
                    foreach ($curr_coll_members as $collection) {
                        if ($collection->getSubMembers()) {
                            $submembers = $collection->getSubMembers();
                            foreach ($submembers as $member) {
                                $in_a_collection[$member->getUri()] = true;
                            }

                        }
                    }

                    if (isset($collection) && $collection->getSubMembers()) {
                        $members_array = array_merge($curr_coll_members, $members_array);
                    }

                }
                $properties['skos:narrower'] = $members_array;
            }
        }

        foreach ($long_uris as &$prop) {
            if (EasyRdf_Namespace::shorten($prop) !== null) {
                // shortening property labels if possible
                $prop = $sprop = EasyRdf_Namespace::shorten($prop);
            } else {
                $sprop = "<$prop>";
            }
            // EasyRdf requires full URIs to be in angle brackets

            if (!in_array($prop, $this->DELETED_PROPERTIES)) {
                $propres = new EasyRdf_Resource($prop, $this->graph);
                $proplabel = $propres->label($this->getEnvLang()) ? $propres->label($this->getEnvLang()) : $propres->label();
                $propobj = new ConceptProperty($prop, $proplabel);

                if ($propobj->getLabel() !== null) {
                    // only display properties for which we have a label
                    $ret[$prop] = $propobj;
                }

                // searching for subproperties of literals too
                foreach ($this->graph->allResources($prop, 'rdfs:subPropertyOf') as $subi) {
                    $suburi = EasyRdf_Namespace::shorten($subi->getUri()) ? EasyRdf_Namespace::shorten($subi->getUri()) : $subi->getUri();
                    $duplicates[$suburi] = $prop;
                }

                // Iterating through every literal and adding these to the data object.
                foreach ($this->resource->allLiterals($sprop) as $val) {
                    $literal = new ConceptPropertyValueLiteral($val, $prop);
                    // only add literals when they match the content/hit language or have no language defined
                    if (isset($ret[$prop]) && ($literal->getLang() === $this->clang || $literal->getLang() === null)) {
                        $ret[$prop]->addValue($literal);
                    }

                }

                // Iterating through every resource and adding these to the data object.
                foreach ($this->resource->allResources($sprop) as $val) {
                    // skipping narrower concepts which are already shown in a collection
                    if ($sprop === 'skos:narrower' && array_key_exists($val->getUri(), $in_a_collection)) {
                        continue;
                    }

                    // hiding rdf:type property if it's just skos:Concept
                    if ($sprop === 'rdf:type' && $val->shorten() === 'skos:Concept') {
                        continue;
                    }

                    // handled by getMappingProperties()
                    if (in_array($sprop, $this->MAPPING_PROPERTIES)) {
                        continue;
                    }

                    if (isset($ret[$prop])) {
                        $ret[$prop]->addValue(new ConceptPropertyValue($this->model, $this->vocab, $val, $prop, $this->clang), $this->clang);
                    }

                }
            }
        }
        // adding narrowers part of a collection
        foreach ($properties as $prop => $values) {
            foreach ($values as $value) {
                $ret[$prop]->addValue($value, $this->clang);
            }
        }

        foreach ($ret as $key => $prop) {
            if (sizeof($prop->getValues()) === 0) {
                unset($ret[$key]);
            }
        }

        $ret = $this->removeDuplicatePropertyValues($ret, $duplicates);
        // sorting the properties to the order preferred in the Skosmos concept page.
        $ret = $this->arbitrarySort($ret);
        return $ret;
    }

    /**
     * Removes properties that have duplicate values.
     * @param $ret the array of properties generated by getProperties
     * @param $duplicates array of properties found are a subProperty of a another property
     * @return array of ConceptProperties
     */
    public function removeDuplicatePropertyValues($ret, $duplicates)
    {
        $propertyValues = array();

        foreach ($ret as $prop) {
            foreach ($prop->getValues() as $value) {
                $label = $value->getLabel();
                $propertyValues[(method_exists($label, 'getValue')) ? $label->getValue() : $label][] = $value->getType();
            }
        }

        foreach ($propertyValues as $value => $propnames) {
            // if there are multiple properties with the same string value.
            if (count($propnames) > 1) {
                foreach ($propnames as $property) {
                    // if there is a more accurate property delete the more generic one.
                    if (isset($duplicates[$property])) {
                        unset($ret[$property]);
                    }
                }

            }
        }
        return $ret;
    }

    /**
     * Gets the creation date and modification date if available.
     * @return String containing the date information in a human readable format.
     */
    public function getDate()
    {
        $ret = '';
        $created = '';
        $modified = '';
        try {
            // finding the created properties
            if ($this->resource->get('dc11:created')) {
                $created = $this->resource->get('dc11:created')->getValue();
            } else if ($this->resource->get('dc:created')) {
                $created = $this->resource->get('dc:created')->getValue();
            }

            // finding the modified properties
            if ($this->resource->get('dc11:modified')) {
                $modified = $this->resource->get('dc11:modified')->getValue();
            } else if ($this->resource->get('dc:modified')) {
                $modified = $this->resource->get('dc:modified')->getValue();
            }

            // making a human readable string from the timestamps
            if ($created != '') {
                $ret = gettext('skosmos:created') . ' ' . (Punic\Calendar::formatDate($created, 'short'));
            }

            if ($modified != '') {
                if ($created != '') {
                    $ret .= ', ' . gettext('skosmos:modified') . ' ' . (Punic\Calendar::formatDate($modified, 'short'));
                } else {
                    $ret .= ' ' . ucfirst(gettext('skosmos:modified')) . ' ' . (Punic\Calendar::formatDate($modified, 'short'));
                }

            }
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            return gettext('skosmos:modified') . ' ' . (string) $this->resource->get('dc:modified') . ' ' . gettext('skosmos:created') . ' ' . (string) $this->resource->get('dc:created');
        }
        return $ret;
    }

    /**
     * Gets the members of a specific collection.
     * @param $coll
     * @param array containing all narrowers as EasyRdf_Resource
     * @return array containing ConceptPropertyValue objects
     */
    private function getCollectionMembers($coll, $narrowers)
    {
        $members_array = array();
        $coll_label = $coll->label()->getValue($this->clang) ? $coll->label($this->clang) : $coll->label();
        if ($coll_label) {
            $coll_label = $coll_label->getValue();
        }

        $members_array[$coll_label] = new ConceptPropertyValue($this->model, $this->vocab, $coll, 'skos:narrower', $this->clang);
        foreach ($coll->allResources('skos:member') as $member) {
            if (array_key_exists($member->getUri(), $narrowers)) {
                $narrower = $narrowers[$member->getUri()];
                if (isset($narrower)) {
                    $members_array[$coll_label]->addSubMember(new ConceptPropertyValue($this->model, $this->vocab, $narrower, 'skos:member', $this->clang), $this->clang);
                }

            }
        }

        return $members_array;
    }

    /**
     * Gets the groups the concept belongs to.
     */
    public function getGroupProperties()
    {
        // finding out if the concept is a member of some group
        $groups = array();
        $reverseResources = $this->graph->resourcesMatching('skos:member', $this->resource);
        if (isset($reverseResources)) {
            $arrayClassURI = $this->vocab !== null ? $this->vocab->getConfig()->getArrayClassURI() : null;
            $arrayClass = $arrayClassURI !== null ? EasyRdf_Namespace::shorten($arrayClassURI) : null;
            foreach ($reverseResources as $reverseResource) {
                $property = in_array($arrayClass, $reverseResource->types()) ? "skosmos:memberOfArray" : "skosmos:memberOf";
                $coll_label = $reverseResource->label($this->clang) ? $reverseResource->label($this->clang) : $reverseResource->label();
                if ($coll_label) {
                    $coll_label = $coll_label->getValue();
                }

                $groups[$coll_label] = new ConceptPropertyValue($this->model, $this->vocab, $reverseResource, $property, $this->clang);
                ksort($groups);
                $super = $this->graph->resourcesMatching('skos:member', $reverseResource);
                while (isset($super) && !empty($super)) {
                    foreach ($super as $res) {
                        $superprop = new ConceptPropertyValue($this->model, $this->vocab, $res, 'skosmos:memberOfSuper', $this->clang);
                        array_unshift($groups, $superprop);
                        $super = $this->graph->resourcesMatching('skos:member', $res);
                    }
                }
            }
        }
        return $groups;
    }

    /**
     * Gets the values for the property in question in all other languages than the ui language.
     */
    public function getForeignLabels()
    {
        global $LANGUAGES;
        $labels = array();
        foreach ($this->resource->allLiterals('skos:prefLabel') as $lit) {
            // filtering away subsets of the current language eg. en vs en-GB
            if ($lit->getLang() != $this->clang && strpos($lit->getLang(), $this->getEnvLang() . '-') !== 0) {
                $labels[Punic\Language::getName($lit->getLang(), $this->getEnvLang())][] = new ConceptPropertyValueLiteral($lit, 'skos:prefLabel');
            }

        }
        foreach ($this->resource->allLiterals('skos:altLabel') as $lit) {
            // filtering away subsets of the current language eg. en vs en-GB
            if ($lit->getLang() != $this->clang && strpos($lit->getLang(), $this->getEnvLang() . '-') !== 0) {
                $labels[Punic\Language::getName($lit->getLang(), $this->getEnvLang())][] = new ConceptPropertyValueLiteral($lit, 'skos:altLabel');
            }

        }
        ksort($labels);
        return $labels;
    }

    /**
     * Gets the values for the property in question in all other languages than the ui language.
     * @param string $property
     */
    public function getAllLabels($property)
    {
        global $LANGUAGES;
        $labels = array();
        if (EasyRdf_Namespace::shorten($property) !== null) {
            // shortening property labels if possible
            $property = EasyRdf_Namespace::shorten($property);
        } else {
            $property = "<$property>";
        }
        // EasyRdf requires full URIs to be in angle brackets
        foreach ($this->resource->allLiterals($property) as $lit) {
            $labels[Punic\Language::getName($lit->getLang(), $this->getEnvLang())][] = new ConceptPropertyValueLiteral($lit, $property);
        }
        ksort($labels);
        return $labels;
    }
}
