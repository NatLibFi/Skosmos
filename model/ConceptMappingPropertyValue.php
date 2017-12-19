<?php

/**
 * Class for handling concept property values.
 */
class ConceptMappingPropertyValue extends VocabularyDataObject
{
    /** property type */
    private $type;
    private $clang;
    private $labelcache;

    public function __construct($model, $vocab, $resource, $prop, $clang = '')
    {
        parent::__construct($model, $vocab, $resource);
        $this->type = $prop;
        $this->clang = $clang;
        $this->labelcache = array();
    }

    public function __toString()
    {
        $label = $this->getLabel();
        $notation = $this->getNotation();
        return $notation . $label;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getLabel($lang = '')
    {
        if (isset($this->labelcache[$lang])) {
            return $this->labelcache[$lang];
        }

        $label = $this->queryLabel($lang);
        $this->labelcache[$lang] = $label;
        return $label;
    }

    private function queryLabel($lang)
    {
        if ($this->clang) {
            $lang = $this->clang;
        }

        // if multiple vocabularies are found, the following method will return in priority the current vocabulary of the mapping
        $exvocab = $this->model->guessVocabularyFromURI($this->resource->getUri(), $this->vocab->getId());

        if ($this->resource->label($lang) !== null) { // current language
            return $this->resource->label($lang);
        } elseif ($this->resource->label() !== null) { // any language
            return $this->resource->label();
        } elseif ($this->resource->getLiteral('rdf:value', $lang) !== null) { // current language
            return $this->resource->getLiteral('rdf:value', $lang);
        } elseif ($this->resource->getLiteral('rdf:value') !== null) { // any language
            return $this->resource->getLiteral('rdf:value');
        }

        // if the resource is from a another vocabulary known by the skosmos instance
        if ($exvocab) {
            $label = $this->getExternalLabel($exvocab, $this->getUri(), $lang) ? $this->getExternalLabel($exvocab, $this->getUri(), $lang) : $this->getExternalLabel($exvocab, $this->getUri(), $exvocab->getConfig()->getDefaultLanguage());
            if ($label) {
                return $label;
            }
        }

        // using URI as label if nothing else has been found.
        $label = $this->resource->shorten() ? $this->resource->shorten() : $this->resource->getUri();
        return $label;
    }

    public function getUri()
    {
        return $this->resource->getUri();
    }

    public function getExVocab()
    {
        $exvocab = $this->model->guessVocabularyFromURI($this->getUri(), $this->vocab->getId());
        return $exvocab;
    }

    public function getVocab()
    {
        return $this->vocab;
    }

    public function getVocabName()
    {
        // if multiple vocabularies are found, the following method will return in priority the current vocabulary of the mapping
        $exvocab = $this->model->guessVocabularyFromURI($this->resource->getUri(), $this->vocab->getId());
        if ($exvocab) {
            return $exvocab->getTitle();
        }
        // @codeCoverageIgnoreStart
        $scheme = $this->resource->get('skos:inScheme');
        if ($scheme) {
            $schemeResource = $this->model->getResourceFromUri($scheme->getUri());
            if ($schemeResource && $schemeResource->label()) {
                return $schemeResource->label()->getValue();
            }
        }
        // got a label for the concept, but not the scheme - use the host name as scheme label
        return parse_url($this->resource->getUri(), PHP_URL_HOST);
        // @codeCoverageIgnoreEnd
    }

    public function getNotation()
    {
        if ($this->resource->get('skos:notation')) {
            return $this->resource->get('skos:notation')->getValue();
        }

        $exvocab = $this->getExvocab();

        // if the resource is from a another vocabulary known by the skosmos instance
        if ($exvocab) {
            return $this->getExternalNotation($exvocab, $this->getUri());
        }
        return null;
    }

}
