<?php

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
    /** the EasyRdf\Graph object of the concept */
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
        'skos:member', # this shouldn't be shown on the group page
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

    /** default external properties we are interested in saving/displaying from mapped external objects */
    private $DEFAULT_EXT_PROPERTIES = array(
        "http://purl.org/dc/elements/1.1/title",
        "http://purl.org/dc/terms/title",
        "http://www.w3.org/2004/02/skos/core#prefLabel",
        "http://www.w3.org/2004/02/skos/core#exactMatch",
        "http://www.w3.org/2004/02/skos/core#closeMatch",
        "http://www.w3.org/2004/02/skos/core#inScheme",
        "http://www.w3.org/2000/01/rdf-schema#label",
        "http://www.w3.org/2000/01/rdf-schema#isDefinedBy",
        "http://www.w3.org/2002/07/owl#sameAs",
        "http://www.w3.org/1999/02/22-rdf-syntax-ns#type",
        "http://rdfs.org/ns/void#inDataset",
        "http://rdfs.org/ns/void#sparqlEndpoint",
        "http://rdfs.org/ns/void#uriLookupEndpoint",
        "http://schema.org/about",
        "http://schema.org/description",
        "http://schema.org/inLanguage",
        "http://schema.org/name",
        "http://schema.org/isPartOf",
        "http://www.wikidata.org/prop/direct/P31",
        "http://www.wikidata.org/prop/direct/P625",
        "http://wikiba.se/ontology-beta#wikiGroup"
    );

    /**
     * Initializing the concept object requires the following parameters.
     * @param Model $model
     * @param Vocabulary $vocab
     * @param EasyRdf\Resource $resource
     * @param EasyRdf\Graph $graph
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
     * Returns a boolean value indicating whether the resource is a group defined in the vocab config as skosmos:groupClass.
     * @return boolean
     */
    public function isGroup() {
        $groupClass = $this->getVocab()->getConfig()->getGroupClassURI();
        if ($groupClass) {
            $groupClass = EasyRdf\RdfNamespace::shorten($groupClass) !== null ? EasyRdf\RdfNamespace::shorten($groupClass) : $groupClass;
            return in_array($groupClass, $this->getType());
        }
        return false;
    }

    /**
     * Returns a boolean value indicating if the concept has been deprecated.
     * @return boolean
     */
    public function getDeprecated()
    {
        $deprecatedValue = $this->resource->getLiteral('owl:deprecated');
        return ($deprecatedValue !== null && filter_var($deprecatedValue->getValue(), FILTER_VALIDATE_BOOLEAN));
    }

    /**
     * Returns a label for the concept in the content language or if not possible in any language.
     * @return string
     */
    public function getLabel()
    {
        foreach ($this->vocab->getConfig()->getLanguageOrder($this->clang) as $fallback) {
            if ($this->resource->label($fallback) !== null) {
                return $this->resource->label($fallback);
            }
            // We need to check all the labels in case one of them matches a subtag of the current language
            foreach($this->resource->allLiterals('skos:prefLabel') as $label) {
                // the label lang code is a subtag of the UI lang eg. en-GB - create a new literal with the main language
                if ($label !== null && strpos($label->getLang(), $fallback . '-') === 0) {
                    return EasyRdf\Literal::create($label, $fallback);
                }
            }
        }

        // Last resort: label in any language, including literal with empty language tag
        $label = $this->resource->label();
        if ($label !== null) {
            if (!$label->getLang()) {
                return $label->getValue();
            }
            return EasyRdf\Literal::create($label->getValue(), $label->getLang());
        }

        // empty
        return "";
    }

    public function hasXlLabel($prop = 'prefLabel')
    {
        if ($this->resource->hasProperty('skosxl:' . $prop)) {
            return true;
        }
        return false;
    }

    public function getXlLabel()
    {
        $labels = $this->resource->allResources('skosxl:prefLabel');
        foreach($labels as $labres) {
            $label = $labres->getLiteral('skosxl:literalForm');
            if ($label !== null && $label->getLang() == $this->clang) {
                return new LabelSkosXL($this->model, $labres);
            }
        }
        return null;
    }

    /**
     * Returns a notation for the concept or null if it has not been defined.
     * @return string eg. '999'
     */
    public function getNotation()
    {
        $notation = $this->resource->get('skos:notation');
        if ($this->vocab->getConfig()->showNotation() && $notation !== null) {
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

    /**
     * Processes a single external resource i.e., adds the properties from
     * 1) $this->$DEFAULT_EXT_PROPERTIES
     * 2) VocabConfig external properties
     * 3) Possible plugin defined external properties
     * to $this->graph
     * @param EasyRdf\Resource $res
     */
    public function processExternalResource($res)
    {
        $exGraph = $res->getGraph();
        // catch external subjects that have $res as object
        $extSubjects = $exGraph->resourcesMatching("http://schema.org/about", $res);

        $propList =  array_unique(array_merge(
            $this->DEFAULT_EXT_PROPERTIES,
            $this->getVocab()->getConfig()->getExtProperties(),
            $this->getVocab()->getConfig()->getPlugins()->getExtProperties()
        ));

        $seen = array();
        $this->addExternalTriplesToGraph($res, $seen, $propList);

        foreach ($extSubjects as $extSubject) {
           $this->addExternalTriplesToGraph($extSubject, $seen, $propList);
        }

    }

    /**
     * Adds resource properties to $this->graph
     * @param EasyRdf\Resource $res
     * @param string[] $seen Processed resources so far
     * @param string[] $props (optional) limit to these property URIs
     */
    private function addExternalTriplesToGraph($res, &$seen, $props=null)
    {
        if (array_key_exists($res->getUri(), $seen)) {
            return;
        }
        $seen[$res->getUri()] = True;

        if ($res->isBNode() || is_null($props)) {
            foreach ($res->propertyUris() as $prop) {
                $this->addPropertyValues($res, $prop, $seen);
            }
        }
        else {
            foreach ($props as $prop) {
                if ($res->hasProperty($prop)) {
                    $this->addPropertyValues($res, $prop, $seen);
                }
            }
        }
    }

    /**
     * Adds values of a single single property of a resource to $this->graph
     * implements Concise Bounded Description definition
     * @param EasyRdf\Resource $res
     * @param string $prop
     * @param string[] $seen Processed resources so far
     */
    private function addPropertyValues($res, $prop, &$seen)
    {
        $resList = $res->allResources('<' . $prop . '>');

        foreach ($resList as $res2) {
            if ($res2->isBNode()) {
                $this->addExternalTriplesToGraph($res2, $seen);
            }
            $this->graph->addResource($res, $prop, $res2);
            $this->addResourceReifications($res, $prop, $res2, $seen);
        }

        $litList = $res->allLiterals('<' . $prop . '>');

        foreach ($litList as $lit) {
            $this->graph->addLiteral($res, $prop, $lit);
            $this->addLiteralReifications($res, $prop, $lit, $seen);
        }
    }

    /**
     * Adds reifications of a triple having a literal object to $this->graph
     * @param EasyRdf\Resource $sub
     * @param string $pred
     * @param EasyRdf\Literal $obj
     * @param string[] $seen Processed resources so far
     */
    private function addLiteralReifications($sub, $pred, $obj, &$seen)
    {
        $pos_reifs = $sub->getGraph()->resourcesMatching("rdf:subject", $sub);
        foreach ($pos_reifs as $pos_reif) {
            $lit = $pos_reif->getLiteral("rdf:object", $obj->getLang());

            if (!is_null($lit) && $lit->getValue() === $obj->getValue() &&
                $pos_reif->isA("rdf:Statement") &&
                $pos_reif->hasProperty("rdf:predicate", new EasyRdf\Resource($pred, $sub->getGraph())))
            {
                $this->addExternalTriplesToGraph($pos_reif, $seen);
            }
        }
    }

    /**
     * Adds reifications of a triple having a resource object to $this->graph
     * @param EasyRdf\Resource $sub
     * @param string $pred
     * @param EasyRdf\Resource $obj
     * @param string[] $seen Processed resources so far
     */
    private function addResourceReifications($sub, $pred, $obj, &$seen)
    {
        $pos_reifs = $sub->getGraph()->resourcesMatching("rdf:subject", $sub);
        foreach ($pos_reifs as $pos_reif) {
            if ($pos_reif->isA("rdf:Statement") &&
                $pos_reif->hasProperty("rdf:object", $obj) &&
                $pos_reif->hasProperty("rdf:predicate", new EasyRdf\Resource($pred, $sub->getGraph())))
            {
                $this->addExternalTriplesToGraph($pos_reif, $seen);
            }
        }
    }

    public function getMappingProperties()
    {
        $ret = array();

        $longUris = $this->resource->propertyUris();
        foreach ($longUris as &$prop) {
            if (EasyRdf\RdfNamespace::shorten($prop) !== null) {
                // shortening property labels if possible
                $prop = $sprop = EasyRdf\RdfNamespace::shorten($prop);
            } else {
                $sprop = "<$prop>";
            }
            // EasyRdf requires full URIs to be in angle brackets

            if (in_array($prop, $this->MAPPING_PROPERTIES) && !in_array($prop, $this->DELETED_PROPERTIES)) {
                $propres = new EasyRdf\Resource($prop, $this->graph);
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
                        // if multiple vocabularies are found, the following method will return in priority the current vocabulary of the concept
                        $exvoc = $this->model->guessVocabularyFromURI($exuri, $this->vocab->getId());
                        // if not querying the uri itself
                        if (!$exvoc) {
                            $response = null;
                            // if told to do so in the vocabulary configuration
                            if ($this->vocab->getConfig()->getExternalResourcesLoading()) {
                                $response = $this->model->getResourceFromUri($exuri);
                            }

                            if ($response) {
                                $ret[$prop]->addValue(new ConceptMappingPropertyValue($this->model, $this->vocab, $response, $prop), $this->clang);

                                $this->processExternalResource($response);

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
        $narrowersByUri = array();
        $inCollection = array();
        $membersArray = array();
        $longUris = $this->resource->propertyUris();
        $duplicates = array();
        $ret = array();

        // looking for collections and linking those with their narrower concepts
        if ($this->vocab->getConfig()->getArrayClassURI() !== null) {
            $collections = $this->graph->allOfType($this->vocab->getConfig()->getArrayClassURI());
            if (sizeof($collections) > 0) {
                // indexing the narrowers once to avoid iterating all of them with every collection
                foreach ($this->resource->allResources('skos:narrower') as $narrower) {
                    $narrowersByUri[$narrower->getUri()] = $narrower;
                }

                foreach ($collections as $coll) {
                    $currCollMembers = $this->getCollectionMembers($coll, $narrowersByUri);
                    foreach ($currCollMembers as $collection) {
                        if ($collection->getSubMembers()) {
                            $submembers = $collection->getSubMembers();
                            foreach ($submembers as $member) {
                                $inCollection[$member->getUri()] = true;
                            }

                        }
                    }

                    if (isset($collection) && $collection->getSubMembers()) {
                        $membersArray = array_merge($currCollMembers, $membersArray);
                    }

                }
                $properties['skos:narrower'] = $membersArray;
            }
        }

        foreach ($longUris as &$prop) {
            // storing full URI without brackets in a separate variable
            $longUri = $prop;
            if (EasyRdf\RdfNamespace::shorten($prop) !== null) {
                // shortening property labels if possible
                $prop = $sprop = EasyRdf\RdfNamespace::shorten($prop);
            } else {
                $sprop = "<$prop>";
            }
            // EasyRdf requires full URIs to be in angle brackets

            if (!in_array($prop, $this->DELETED_PROPERTIES) || ($this->isGroup() === false && $prop === 'skos:member')) {
                // retrieve property label and super properties from the current vocabulary first
                $propres = new EasyRdf\Resource($prop, $this->graph);
                $proplabel = $propres->label($this->getEnvLang()) ? $propres->label($this->getEnvLang()) : $propres->label();

                // if not found in current vocabulary, look up in the default graph to be able
                // to read an ontology loaded in a separate graph
                // note that this imply that the property has an rdf:type declared for the query to work
                if(!$proplabel) {
                    $envLangLabels = $this->model->getDefaultSparql()->queryLabel($longUri, $this->getEnvLang());
                    $proplabel = ($envLangLabels)?$envLangLabels[$this->getEnvLang()]:$this->model->getDefaultSparql()->queryLabel($longUri, '')[''];
                }

                // look for superproperties in the current graph
                $superprops = array();
                foreach ($this->graph->allResources($prop, 'rdfs:subPropertyOf') as $subi) {
                    $superprops[] = $subi->getUri();
                }

                // also look up superprops in the default graph if not found in current vocabulary
                if(!$superprops || empty($superprops)) {
                    $superprops = $this->model->getDefaultSparql()->querySuperProperties($longUri);
                }

                // we're reading only one super property, even if there are multiple ones
                $superprop = ($superprops)?$superprops[0]:null;
                if ($superprop) {
                    $superprop = EasyRdf\RdfNamespace::shorten($superprop) ? EasyRdf\RdfNamespace::shorten($superprop) : $superprop;
                }
                $propobj = new ConceptProperty($prop, $proplabel, $superprop);

                if ($propobj->getLabel() !== null) {
                    // only display properties for which we have a label
                    $ret[$prop] = $propobj;
                }

                // searching for subproperties of literals too
                if($superprops) {
                    foreach ($superprops as $subi) {
                        $suburi = EasyRdf\RdfNamespace::shorten($subi) ? EasyRdf\RdfNamespace::shorten($subi) : $subi;
                        $duplicates[$suburi] = $prop;
                    }
                }

                // Iterating through every literal and adding these to the data object.
                foreach ($this->resource->allLiterals($sprop) as $val) {
                    $literal = new ConceptPropertyValueLiteral($this->model, $this->vocab, $this->resource, $val, $prop);
                    // only add literals when they match the content/hit language or have no language defined
                    if (isset($ret[$prop]) && ($literal->getLang() === $this->clang || $literal->getLang() === null)) {
                        $ret[$prop]->addValue($literal);
                    }

                }

                // Iterating through every resource and adding these to the data object.
                foreach ($this->resource->allResources($sprop) as $val) {
                    // skipping narrower concepts which are already shown in a collection
                    if ($sprop === 'skos:narrower' && array_key_exists($val->getUri(), $inCollection)) {
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
                        // checking if the property value is not in the current vocabulary
                        $exvoc = $this->model->guessVocabularyFromURI($val->getUri());
                        if ($exvoc && $exvoc->getId() !== $this->vocab->getId()) {
                            $ret[$prop]->addValue(new ConceptMappingPropertyValue($this->model, $this->vocab, $val, $prop, $this->clang), $this->clang);
                            continue;
                        }
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
            if ($this->resource->get('dc:created')) {
                $created = $this->resource->get('dc:created')->getValue();
            }

            // finding the modified properties
            if ($this->resource->get('dc:modified')) {
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
            $ret = '';
            if ($this->resource->get('dc:modified')) {
                $modified = (string) $this->resource->get('dc:modified');
                $ret = gettext('skosmos:modified') . ' ' . $modified;
            }
            if ($this->resource->get('dc:created')) {
                $created .= (string) $this->resource->get('dc:created');
                $ret .= ' ' . gettext('skosmos:created') . ' ' . $created;
            }
        }
        return $ret;
    }

    /**
     * Gets the members of a specific collection.
     * @param $coll
     * @param array containing all narrowers as EasyRdf\Resource
     * @return array containing ConceptPropertyValue objects
     */
    private function getCollectionMembers($coll, $narrowers)
    {
        $membersArray = array();
        $collLabel = $coll->label()->getValue($this->clang) ? $coll->label($this->clang) : $coll->label();
        if ($collLabel) {
            $collLabel = $collLabel->getValue();
        }

        $membersArray[$collLabel] = new ConceptPropertyValue($this->model, $this->vocab, $coll, 'skos:narrower', $this->clang);
        foreach ($coll->allResources('skos:member') as $member) {
            if (array_key_exists($member->getUri(), $narrowers)) {
                $narrower = $narrowers[$member->getUri()];
                if (isset($narrower)) {
                    $membersArray[$collLabel]->addSubMember(new ConceptPropertyValue($this->model, $this->vocab, $narrower, 'skos:member', $this->clang), $this->clang);
                }

            }
        }

        return $membersArray;
    }

    /**
     * Gets the groups the concept belongs to.
     */
    public function getGroupProperties()
    {
        return $this->getReverseResources(false);
    }

    /**
     * Gets the groups/arrays the concept belongs to.
     */
    public function getReverseResources($includeArrays) {
        $groups = array();
        $reverseResources = $this->graph->resourcesMatching('skos:member', $this->resource);
        if (isset($reverseResources)) {
            $arrayClassURI = $this->vocab !== null ? $this->vocab->getConfig()->getArrayClassURI() : null;
            $arrayClass = $arrayClassURI !== null ? EasyRdf\RdfNamespace::shorten($arrayClassURI) : null;
            $superGroups = $this->resource->all('isothes:superGroup');
            $superGroupUris = array_map(function($obj) { return $obj->getUri(); }, $superGroups);
            foreach ($reverseResources as $reverseResource) {
                if (in_array($arrayClass, $reverseResource->types()) === $includeArrays) {
                    // not adding the memberOf if the reverse resource is already covered by isothes:superGroup see issue #433
                    if (in_array($reverseResource->getUri(), $superGroupUris)) {
                        continue;
                    }
                    $property = in_array($arrayClass, $reverseResource->types()) ? "skosmos:memberOfArray" : "skosmos:memberOf";
                    $collLabel = $reverseResource->label($this->clang) ? $reverseResource->label($this->clang) : $reverseResource->label();
                    if ($collLabel) {
                        $collLabel = $collLabel->getValue();
                    }

                    $groups[$collLabel] = new ConceptPropertyValue($this->model, $this->vocab, $reverseResource, $property, $this->clang);
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
        }
        return $groups;
    }

    public function getArrayProperties() {
        return $this->getReverseResources(true);
    }

    /**
     * Reads the literal language code and gets a name for it from Punic or alternatively
     * tries to search for a gettext translation.
     * @param EasyRdf\Literal $lit
     * @return string e.g. 'English'
     */
    private function literalLanguageToString($lit) {
        // using empty string as the language literal when there is no langcode set
        $langName = '';
        if ($lit->getLang() !== null) {
            $langName = Punic\Language::getName($lit->getLang(), $this->getEnvLang()) !== $lit->getLang() ? Punic\Language::getName($lit->getLang(), $this->getEnvLang()) : gettext($lit->getLang());
        }
        return $langName;
    }

    /**
     * Gets the values for the property in question in all other languages than the ui language.
     */
    public function getForeignLabels()
    {
        $prefLabels = $this->resource->allLiterals('skos:prefLabel');
        $labels = array_merge($prefLabels, $this->resource->allLiterals('skos:altLabel'));
        $ret = array();
        foreach ($labels as $lit) {
            // filtering away subsets of the current language eg. en vs en-GB
            if ($lit->getLang() != $this->clang && strpos($lit->getLang(), $this->getEnvLang() . '-') !== 0) {
                $prop = in_array($lit, $prefLabels) ? 'skos:prefLabel' : 'skos:altLabel';
                $ret[$this->literalLanguageToString($lit)][] = new ConceptPropertyValueLiteral($this->model, $this->vocab, $this->resource, $lit, $prop);
            }
        }
        ksort($ret);
        return $ret;
    }

    /**
     * Gets the values for the property in question in all other languages than the ui language.
     * @param string $property
     */
    public function getAllLabels($property)
    {
        $labels = array();
        // shortening property labels if possible, EasyRdf requires full URIs to be in angle brackets
        $property = (EasyRdf\RdfNamespace::shorten($property) !== null) ? EasyRdf\RdfNamespace::shorten($property) : "<$property>";
        foreach ($this->resource->allLiterals($property) as $lit) {
            $labels[Punic\Language::getName($lit->getLang(), $this->getEnvLang())][] = new ConceptPropertyValueLiteral($this->model, $this->vocab, $this->resource, $lit, $property);
        }
        ksort($labels);
        return $labels;
    }

    /**
     * Dump concept graph as JSON-LD.
     */
    public function dumpJsonLd() {

        $context = array(
            'skos' => 'http://www.w3.org/2004/02/skos/core#',
            'isothes' => 'http://purl.org/iso25964/skos-thes#',
            'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
            'owl' => 'http://www.w3.org/2002/07/owl#',
            'dct' => 'http://purl.org/dc/terms/',
            'dc11' => 'http://purl.org/dc/elements/1.1/',
            'uri' => '@id',
            'type' => '@type',
            'lang' => '@language',
            'value' => '@value',
            'graph' => '@graph',
            'label' => 'rdfs:label',
            'prefLabel' => 'skos:prefLabel',
            'altLabel' => 'skos:altLabel',
            'hiddenLabel' => 'skos:hiddenLabel',
            'broader' => 'skos:broader',
            'narrower' => 'skos:narrower',
            'related' => 'skos:related',
            'inScheme' => 'skos:inScheme',
        );
        $compactJsonLD = \ML\JsonLD\JsonLD::compact($this->graph->serialise('jsonld'), json_encode($context));
        $results = \ML\JsonLD\JsonLD::toString($compactJsonLD);

        return $results;
    }
}
