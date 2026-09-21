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

    public function getLabel($lang = '', $allowExternal = true)
    {
        $key = $lang . "/" . ($allowExternal ? "true" : "false");
        if (isset($this->labelcache[$key])) {
            return $this->labelcache[$key];
        }

        $label = $this->queryLabel($lang, $allowExternal);
        $this->labelcache[$key] = $label;
        return $label;
    }

    public function getSortKey()
    {
        return strtolower($this->getVocabName() . ": " . $this->getLabel());
    }

    private function queryLabel($lang = '', $allowExternal = true)
    {
        if ($this->clang) {
            $lang = $this->clang;
        }


        $label = $this->getResourceLabel($this->resource, $lang);
        if ($label) {
            return $label;
        }

        // if multiple vocabularies are found, the following method will return in priority the current vocabulary of the mapping
        $exvocab = $allowExternal ? $this->model->guessVocabularyFromURI($this->resource->getUri(), $this->vocab->getId()) : null;

        // if the resource is from another vocabulary known by the skosmos instance
        if ($exvocab) {
            $label = $this->getExternalLabel($exvocab, $this->getUri(), $lang) ? $this->getExternalLabel($exvocab, $this->getUri(), $lang) : $this->getExternalLabel($exvocab, $this->getUri(), $exvocab->getConfig()->getDefaultLanguage());
            if ($label) {
                return $label;
            }
        }

        // using URI as label if nothing else has been found.
        return $this->resource->shorten() ? $this->resource->shorten() : $this->resource->getUri();
    }

    private function getResourceLabel($res, $lang = '')
    {

        if ($this->clang) {
            $lang = $this->clang;
        }

        // try the requested language, the languages configured for the
        // vocabulary (including fallback and default languages) and the
        // UI languages configured for the instance, in that priority order
        $languages = $this->getLabelLanguages($lang);

        foreach ($languages as $l) {
            $label = $res->label($l); // configured language
            if ($label !== null) {
                return $label;
            }
        }
        foreach ($languages as $l) {
            $literal = $res->getLiteral('rdf:value', $l); // configured language
            if ($literal !== null) {
                return $literal;
            }
        }

        // prefer language-neutral (no language tag) literals over labels in any language
        foreach (self::LABEL_PROPERTIES as $prop) {
            foreach ($res->allLiterals($prop) ?: array() as $literal) {
                if ($literal->getLang() === null) {
                    return $literal;
                }
            }
        }
        foreach ($res->allLiterals('rdf:value') ?: array() as $literal) {
            if ($literal->getLang() === null) {
                return $literal;
            }
        }

        // as a last resort use a label in any language
        if ($res->label() !== null) {
            return $res->label();
        } elseif ($res->getLiteral('rdf:value') !== null) {
            return $res->getLiteral('rdf:value');
        }
        return null;
    }

    /**
     * Label properties checked by EasyRdf's label() method, in the same order.
     */
    private const LABEL_PROPERTIES = array(
        'skos:prefLabel',
        'rdfs:label',
        'foaf:name',
        'rss:title',
        'dc:title',
        'dc11:title',
    );

    /**
     * Returns the languages in which a label should be looked up, in priority order:
     * the requested language, the languages configured for the mapping's vocabulary
     * (including skosmos:fallbackLanguages and the default language), and finally
     * the UI languages configured for the instance (both the BCP47 locale and its
     * base language).
     * @param string $lang requested language
     * @return array of language tag strings
     */
    private function getLabelLanguages($lang = '')
    {
        $languages = array();
        $add = function ($l) use (&$languages) {
            if (!empty($l) && !in_array($l, $languages, true)) {
                $languages[] = (string) $l;
            }
        };

        $add($lang);
        foreach ($this->vocab->getConfig()->getLanguageOrder($lang) as $l) {
            $add($l);
        }
        foreach ($this->model->getConfig()->getLanguages() as $uiLang) {
            $add($uiLang);
            $add(explode('-', $uiLang)[0]); // base language of the UI locale
        }
        return $languages;
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
                $schemaName = $this->getResourceLabel($schemeResource, $lang);
                if ($schemaName) {
                    return $schemaName;
                }
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

    /**
     * Return the mapping as a JSKOS-compatible array.
     * @return array
     */
    public function asJskos($allowExternal = true, $lang = null, $hrefLink = null)
    {
        $propertyLabel = $this->getLabel($lang);
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
            'hrefLink' => $hrefLink, // link to resource as displayed in the UI
            'lang' => $propertyLang, // TBD: could it be part of the prefLabel?
            'vocabName' => (string) $this->getVocabName($lang), // vocabulary as displayed in the UI
            'typeLabel' => $this->model->getText($this->type), // a text used in the UI instead of, for example, skos:closeMatch
        ];

        $helpprop = $this->type . "_help";
        // see if we have a translation for the property help text
        $help = $this->model->getText($helpprop);
        if ($help != $helpprop) {
            $ret['description'] = $help;
        }

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

        $label = $this->getLabel($lang, $allowExternal);
        if (isset($label)) {
            if (is_string($label)) {
                list($labelLang, $labelValue) = ['', $label];
            } else {
                list($labelLang, $labelValue) = [$label->getLang(), $label->getValue()];
            }
            // set the language of the preferred label to be whatever returned
            $ret['lang'] = $labelLang;

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
