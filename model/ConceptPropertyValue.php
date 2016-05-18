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

    public function __construct($model, $vocab, $resource, $prop, $clang = '')
    {
        parent::__construct($model, $vocab, $resource);
        $this->submembers = array();
        $this->type = $prop;
        $this->clang = $clang;
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

    public function getLabel($lang = '')
    {
        if ($this->clang) {
            $lang = $this->clang;
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
        $label = $this->resource->shorten() ? $this->resource->shorten() : $this->getUri();
        return $label;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getUri()
    {
        return $this->resource->getUri();
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
        $propertyUris = $this->resource->propertyUris();
        return empty($propertyUris);
    }

    public function getNotation()
    {
        if ($this->vocab->getConfig()->showNotation() && $this->resource->get('skos:notation')) {
            return $this->resource->get('skos:notation')->getValue();
        }

    }

}
