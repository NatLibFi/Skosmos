<?php

use EasyRdf\Resource;

/**
 * Class for handling concept property values.
 */
class ConceptMappingPropertyValue extends VocabularyDataObject
{
    /** property type */
    private $type;
    private $source;
    private $clang;
    private $labelcache;

    /**
     * ConceptMappingPropertyValue constructor.
     *
     * @param Model $model
     * @param Vocabulary $vocab  Target vocabulary
     * @param Resource $target   Target concept resource
     * @param Resource $source   Source concept resource
     * @param string $prop       Mapping property
     * @param ?string $clang     Preferred label language (nullable)
     */
    public function __construct(Model $model, Vocabulary $vocab, Resource $target, Resource $source, string $prop, $clang = '')
    {
        parent::__construct($model, $vocab, $target);
        $this->source = $source;
        $this->type = $prop;
        $this->clang = $clang;
        $this->labelcache = array();
    }

    public function __toString()
    {
        $label = $this->getLabel();
        $notation = $this->getNotation();
        return ltrim($notation . ' ') . $label;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getLabel($lang = '', $queryExVocabs = true)
    {
        if (isset($this->labelcache[$lang])) {
            return $this->labelcache[$lang];
        }

        $label = $this->queryLabel($lang);
        $this->labelcache[$lang] = $label;
        return $label;
    }

    private function queryLabel($lang = '', $queryExVocabs = true)
    {
        if ($this->clang) {
            $lang = $this->clang;
        }


        $label = $this->getResourceLabel($this->resource, $lang);
        if ($label) {
            return $label;
        }

        // if multiple vocabularies are found, the following method will return in priority the current vocabulary of the mapping
        $exvocab = $queryExVocabs ? $this->model->guessVocabularyFromURI($this->resource->getUri(), $this->vocab->getId()) : null;

        // if the resource is from another vocabulary known by the skosmos instance
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

    private function getResourceLabel($res, $lang = '') {

        if ($this->clang) {
            $lang = $this->clang;
        }

        if ($res->label($lang) !== null) { // current language
            return $res->label($lang);
        } elseif ($res->label() !== null) { // any language
            return $res->label();
        } elseif ($res->getLiteral('rdf:value', $lang) !== null) { // current language
            return $res->getLiteral('rdf:value', $lang);
        } elseif ($res->getLiteral('rdf:value') !== null) { // any language
            return $res->getLiteral('rdf:value');
        }
        return null;
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

    public function getVocabName($lang = '')
    {

        if ($this->clang) {
            $lang = $this->clang;
        }

        // if multiple vocabularies are found, the following method will return in priority the current vocabulary of the mapping
        $exvocab = $this->model->guessVocabularyFromURI($this->resource->getUri(), $this->vocab->getId());
        if ($exvocab) {
            return $exvocab->getTitle($lang);
        }

        // @codeCoverageIgnoreStart
        $scheme = $this->resource->get('skos:inScheme');
        if ($scheme) {
            $schemeResource = $this->model->getResourceFromUri($scheme->getUri());
            if ($schemeResource) {
                $schemaName = $this->getResourceLabel($schemeResource);
                if ($schemaName) {
                    return $schemaName;
                }
            }
        }
        // got a label for the concept, but not the scheme - use the host name as scheme label
        return parse_url($this->resource->getUri(), PHP_URL_HOST);
        // @codeCoverageIgnoreEnd
    }

    public function isExternal() {
        // if we don't know enough of this resource
        return $this->resource->label() == null && $this->resource->get('rdf:value') == null;
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

    /**
     * Return the mapping as a JSKOS-compatible array.
     * @return array
     */
    public function asJskos($queryExVocabs = true, $lang = null, $hrefLink = null)
    {
        $propertyLabel = $this->getLabel($lang, $queryExVocabs);
        $propertyLang = $lang;
        if (!is_string($propertyLabel)) {
            $propertyLang = $propertyLabel->getLang();
            $propertyLabel = $propertyLabel->getValue();
        }
        $ret = [
            // JSKOS
            'uri' => $this->source->getUri(),
            'notation' => $this->getNotation(),
            'type' => [$this->type],
            'prefLabel' => $propertyLabel,
            'from' => [
                'memberSet' => [
                    [
                        'uri' => (string) $this->source->getUri(),
                    ]
                ]
            ],
            'to' => [
                'memberSet' => [
                    [
                        'uri' => (string) $this->getUri()
                    ]
                ]
            ],
            // EXTRA
            'description' => gettext($this->type . "_help"), // pop-up text
            'hrefLink' => $hrefLink, // link to resource as displayed in the UI
            'lang' => $propertyLang, // TBD: could it be part of the prefLabel?
            'vocabName' => (string) $this->getVocabName(), // vocabulary as displayed in the UI
            'typeLabel' => gettext($this->type), // a text used in the UI instead of, for example, skos:closeMatch
        ];

        $fromScheme = $this->vocab->getDefaultConceptScheme();
        if (isset($fromScheme)) {
            $ret['fromScheme'] = [
                'uri' => (string) $fromScheme,
            ];
        }

        $exvocab = $this->getExvocab();
        if (isset($exvocab)) {
            $ret['toScheme'] = [
                'uri' => (string) $exvocab->getDefaultConceptScheme(),
            ];
        }

        $notation = $this->getNotation();
        if (isset($notation)) {
            $ret['to']['memberSet'][0]['notation'] = (string) $notation;
        }

        $label = $this->getLabel($lang, $queryExVocabs);
        if (isset($label)) {
            if (is_string($label)) {
                list($labelLang, $labelValue) = ['-', $label];
            } else {
                list($labelLang, $labelValue) = [$label->getLang(), $label->getValue()];
            }
            if ($labelValue != $this->getUri()) {
                // The `queryLabel()` method above will fallback to returning the URI
                // if no label was found. We don't want that here.
                $ret['to']['memberSet'][0]['prefLabel'] = [
                    $labelLang => $labelValue,
                ];
            }
        }

        return $ret;
    }

}

