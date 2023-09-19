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

    public function __construct($model, $vocab, $resource, $prop, $clang = '')
    {
        parent::__construct($model, $vocab, $resource);
        $this->submembers = array();
        $this->type = $prop;
        $this->clang = $clang;
        // check if the resource is external to the current vocabulary
        $this->external = ($this->getLabel('', 'null') === null);
        if ($this->external) {
            // if we find the resource in another vocabulary, use it instead
            $exvocab = $this->getExVocab();
            if ($exvocab !== null) {
                $this->vocab = $exvocab;
            }
        }
    }

    public function __toString()
    {
        return is_string($this->getLabel()) ? $this->getLabel() : $this->getLabel()->getValue();
    }

    public function getLang()
    {
        return $this->model->getLocale();
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
            return $this->resource->shorten() ? $this->resource->shorten() : $this->getUri();
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
        return $this->model->guessVocabularyFromURI($this->getUri(), $this->vocab->getId());
    }

    public function getVocab()
    {
        return $this->vocab;
    }

    public function getVocabName()
    {
        return $this->vocab->getShortName();
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

    public function isExternal()
    {
        return $this->external;
    }

    public function getNotation()
    {
        if ($this->vocab->getConfig()->showNotation() && $this->resource->get('skos:notation')) {
            return $this->resource->get('skos:notation')->getValue();
        }

    }

    public function isReified()
    {
        return (!$this->resource->label() && $this->resource->getLiteral('rdf:value'));
    }

    public function getReifiedPropertyValues()
    {
        $ret = array();
        $props = $this->resource->propertyUris();
        foreach($props as $prop) {
            $prop = (EasyRdf\RdfNamespace::shorten($prop) !== null) ? EasyRdf\RdfNamespace::shorten($prop) : $prop;
            $propkey = str_starts_with($prop, 'dc11:') ?
                str_replace('dc11:', 'dc:', $prop) : $prop;
            foreach ($this->resource->allLiterals($prop) as $val) {
                if ($prop !== 'rdf:value') { // shown elsewhere
                    $ret[$this->model->getText($propkey)] = new ConceptPropertyValueLiteral($this->model, $this->vocab, $this->resource, $val, $prop);
                }
            }
            foreach ($this->resource->allResources($prop) as $val) {
                $ret[$this->model->getText($propkey)] = new ConceptPropertyValue($this->model, $this->vocab, $val, $prop, $this->clang);
            }
        }
        return $ret;
    }

}
