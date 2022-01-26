<?php

/**
 * ConceptSearchParameters is used for search parameters.
 */
class ConceptSearchParameters
{

    private $config;
    private $request;
    private $vocabs;
    private $rest;
    private $hidden;
    private $unique;

    public function __construct($request, $config, $rest = false)
    {
        $this->request = $request;
        $this->config = $config;
        $this->rest = $rest;
        $this->hidden = true;
        $this->unique = $request->getQueryParamBoolean('unique', false);
    }

    public function getLang()
    {
        if ($this->rest && $this->request->getQueryParam('labellang')) {
            return $this->request->getQueryParam('labellang');
        }
        return $this->request->getLang();
    }

    public function getVocabs()
    {
        if ($this->vocabs) {
            return $this->vocabs;
        }
        if ($this->request->getVocab()) {
            return array($this->request->getVocab());
        }
        return array();
    }

    public function getVocabIds()
    {
        if ($this->rest || $this->request->getQueryParam('vocabs')) {
            $vocabs = $this->rest ? $this->request->getQueryParam('vocab') : $this->request->getQueryParam('vocabs');
            return ($vocabs !== null && $vocabs !== '') ? explode(' ', $vocabs) : null;
        }
        $vocabs = $this->getVocabs();
        return isset($vocabs[0]) ? array($vocabs[0]->getId()) : null;
    }

    public function setVocabularies($vocabs)
    {
        $this->vocabs = $vocabs;
    }

    public function getArrayClass()
    {
        if (sizeof($this->getVocabs()) == 1) { // search within vocabulary
            $vocabs = $this->getVocabs();
            return $vocabs[0]->getConfig()->getArrayClassURI();
        }
        return null;
    }

    public function getSearchTerm()
    {
        $term = $this->request->getQueryParamRaw('q') ? $this->request->getQueryParamRaw('q') : $this->request->getQueryParamRaw('query');
        $term = ($term == 0) ? '0' : $term;
        if (!$term && $this->rest)
            $term = $this->request->getQueryParamRaw('label');
        $term = trim($term); // surrounding whitespace is not considered significant
        $term = Normalizer::normalize( $term, Normalizer::FORM_C ); //Normalize decomposed unicode characters #1184
        if ($this->rest) {
            return $term;
        }
        return strpos($term, "*") === false ? $term . "*" : $term; // default to prefix search
    }

    public function getContentLang()
    {
        return $this->request->getContentLang();
    }

    public function getSearchLang()
    {
        if ($this->rest) {
            return $this->request->getQueryParam('lang');
        }
        return $this->request->getQueryParam('anylang') ? '' : $this->getContentLang();
    }

    private function getDefaultTypeLimit()
    {
        $type = array('skos:Concept');
        if ($this->request->getVocab()) {
            $conf = $this->request->getVocab()->getConfig();
            $type[] = $conf->getArrayClassURI();
            $type[] = $conf->getGroupClassURI();
        }
        return array_filter($type, 'strlen');
    }

    public function getTypeLimit()
    {
        $type = $this->request->getQueryParam('type') !== '' ? $this->request->getQueryParam('type') : null;
        if ($type && strpos($type, ' ')) {
            $type = explode(' ', $type);
        } else if ($type && strpos($type, '+')) {
            $type = explode('+', $type);
        } else if ($type && !is_array($type)) {
            // if only one type param given place it into an array regardless
            $type = array($type);
        }
        if ($type === null) {
            return $this->getDefaultTypeLimit();
        }
        return $type;
    }

    private function getQueryParam($name) {
        return $this->request->getQueryParam($name) !== '' ? $this->request->getQueryParam($name) : null;
    }

    private function getQueryParamArray($name) {
        return $this->request->getQueryParam($name) ? explode(' ', urldecode($this->request->getQueryParam($name))) : [];
    }

    public function getGroupLimit()
    {
        return $this->getQueryParam('group');
    }

    public function getParentLimit()
    {
        return $this->getQueryParam('parent');
    }

    public function getSchemeLimit()
    {
        return $this->getQueryParamArray('scheme');
    }

    public function getOffset()
    {
        return ($this->request->getQueryParam('offset') && is_numeric($this->request->getQueryParam('offset')) && $this->request->getQueryParam('offset') >= 0) ? $this->request->getQueryParam('offset') : 0;
    }

    public function getSearchLimit()
    {
        if ($this->rest) {
            return ($this->request->getQueryParam('maxhits')) ? $this->request->getQueryParam('maxhits') : 0;
        }
        return $this->config->getSearchResultsSize();
    }

    public function getUnique() {
        return $this->unique;
    }

    public function setUnique($unique) {
        $this->unique = $unique;
    }

    public function getAdditionalFields() {
        return $this->getQueryParamArray('fields');
    }

    public function getHidden() {
        return $this->hidden;
    }

    public function setHidden($hidden) {
        $this->hidden = $hidden;
    }

    public function getRest() {
        return $this->rest;
    }
}
