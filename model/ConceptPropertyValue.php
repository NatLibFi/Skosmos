<?php

/**
 * Class for handling concept property values.
 */
class ConceptPropertyValue extends VocabularyDataObject
{
    /** submembers */
    private $submembers;
    /** property type */
    private $type;
    /** content language */
    private $clang;
    /** whether the property value is external w.r.t. to the subject resource */
    private $external;

    public function __construct($model, $vocab, $resource, $prop, $clang = '', $external = false)
    {
        parent::__construct($model, $vocab, $resource);
        $this->submembers = array();
        $this->type = $prop;
        $this->clang = $clang;
        $this->external = $external;
    }

    public function __toString()
    {
        $label = is_string($this->getLabel()) ? $this->getLabel() : $this->getLabel()->getValue();
        if ($this->vocab->getConfig()->sortByNotation()) {
            $label = $this->getNotation() . $label;
        }

        return $label;
    }

    public function getLang()
    {
        return $this->getEnvLang();
    }

    public function getLabel($lang = '', $fallbackToUri = 'uri')
    {
        if ($this->clang) {
            $lang = $this->clang;
        }
        if ($this->vocab->getConfig()->getLanguageOrder($lang)) {
            foreach ($this->vocab->getConfig()->getLanguageOrder($lang) as $fallback) {
                if ($this->resource->label($fallback) !== null) {
                    return $this->resource->label($fallback);
                }
                // We need to check all the labels in case one of them matches a subtag of the current language
                if ($this->resource->allLiterals('skos:prefLabel')) {
                    foreach($this->resource->allLiterals('skos:prefLabel') as $label) {
                        // the label lang code is a subtag of the UI lang eg. en-GB - create a new literal with the main language
                        if ($label !== null && strpos($label->getLang(), $fallback . '-') === 0) {
                            return EasyRdf\Literal::create($label, $fallback);
                        }
                    }
                }
            }
        }

        if ($this->resource->label($lang) !== null) { // current language
            return $this->resource->label($lang);
        } elseif ($this->resource->label($this->vocab->getConfig()->getDefaultLanguage()) !== null) { // vocab default language
            return $this->resource->label($this->vocab->getConfig()->getDefaultLanguage());
        } elseif ($this->resource->label() !== null) { // any language
            return $this->resource->label();
        } elseif ($this->resource->getLiteral('rdf:value', $lang) !== null) { // current language
            return $this->resource->getLiteral('rdf:value', $lang);
        } elseif ($this->resource->getLiteral('rdf:value') !== null) { // any language
            return $this->resource->getLiteral('rdf:value');
        }

        // see if we can find a label in another vocabulary known by the skosmos instance
        $label = $this->getExternalLabel($this->vocab, $this->getUri(), $lang);
        if ($label) {
            return $label;
        }

        if ($fallbackToUri == 'uri') {
            // return uri if no label is found
            $label = $this->resource->shorten() ? $this->resource->shorten() : $this->getUri();
            return $label;
        }
        return null;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getUri()
    {
        return $this->resource->getUri();
    }

    public function getExVocab()
    {
        if ($this->isExternal()) {
            return $this->vocab;
        } else {
            return null;
        }
    }

    public function getVocab()
    {
        return $this->vocab;
    }

    public function getVocabName()
    {
        return $this->vocab->getTitle();
    }

    public function addSubMember($member, $lang = '')
    {
        $label = $member->getLabel($lang) ? $member->getLabel($lang) : $member->getLabel();
        $this->submembers[$label->getValue()] = $member;
        $this->sortSubMembers();
    }

    public function getSubMembers()
    {
        if (empty($this->submembers)) {
            return null;
        }

        return $this->submembers;
    }

    private function sortSubMembers()
    {
        if (!empty($this->submembers)) {
            ksort($this->submembers);
        }

    }

    public function isExternal() {
        return $this->external;
    }

    public function getNotation()
    {
        if ($this->vocab->getConfig()->showNotation() && $this->resource->get('skos:notation')) {
            return $this->resource->get('skos:notation')->getValue();
        }

    }

    public function isReified() {
        return (!$this->resource->label() && $this->resource->getLiteral('rdf:value'));
    }

    public function getReifiedPropertyValues() {
        $ret = array();
        $props = $this->resource->propertyUris();
        foreach($props as $prop) {
            $prop = (EasyRdf\RdfNamespace::shorten($prop) !== null) ? EasyRdf\RdfNamespace::shorten($prop) : $prop;
            foreach ($this->resource->allLiterals($prop) as $val) {
                if ($prop !== 'rdf:value') { // shown elsewhere
                    $ret[gettext($prop)] = new ConceptPropertyValueLiteral($this->model, $this->vocab, $this->resource, $val, $prop);
                }
            }
            foreach ($this->resource->allResources($prop) as $val) {
                $ret[gettext($prop)] = new ConceptPropertyValue($this->model, $this->vocab, $val, $prop, $this->clang);
            }
        }
        return $ret;
    }

}
