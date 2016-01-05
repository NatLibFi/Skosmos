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

    public function __construct($request, $config, $rest = false) 
    {
        $this->request = $request;
        $this->config = $config;
        $this->rest = $rest;
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
        return array($this->request->getVocab());
    } 

    public function getVocabIds()
    {
        if ($this->rest) {
            $vocabs = $this->request->getQueryParam('vocab');
            return ($vocabs !== null && $vocabs !== '') ? explode(' ', $vocabs) : null;
        }
        if ($this->request->getQueryParam('vocabs')) {
            $vocabs = $this->request->getQueryParam('vocabs'); 
            return ($vocabs !== null && $vocabs !== '') ? explode(' ', $vocabs) : null;
        }
        return array(reset($this->getVocabs())->getId());
    }

    public function setVocabularies($vocabs) 
    {
        $this->vocabs = $vocabs;
    }
    
    public function getArrayClass() 
    {
        if (sizeof($this->getVocabIds()) == 1) { // search within vocabulary
            return reset($this->getVocabs())->getConfig()->getArrayClassURI();
        }
        return null;
    }
    
    public function getSearchTerm() 
    {
        $term = $this->request->getQueryParam('q') ? $this->request->getQueryParam('q') : $this->request->getQueryParam('query');
        if (!$term && $this->rest)
            $term = $this->request->getQueryParam('label');
        $term = trim($term); // surrounding whitespace is not considered significant
        return strpos($term, "*") === false ? $term . "*" : $term; // default to prefix search
    }
    
    public function getContentLang() 
    {
        return $this->request->getContentLang();
    }
        
    public function getConceptGroups() 
    {
        return $this->vocab->listConceptGroups($content_lang);
    }
    
    public function getSearchLang() 
    {
        if ($this->rest) {
            return $this->request->getQueryParam('lang');
        }
        return $this->request->getQueryParam('anylang') ? '' : $this->getContentLang();
    }

    public function getTypeLimit() 
    {
        $type = $this->request->getQueryParam('type') !== '' ? $this->request->getQueryParam('type') : null;
        if ($type && strpos($type, '+')) {
            $type = explode('+', $type);
        } else if ($type && !is_array($type)) {
            // if only one type param given place it into an array regardless
            $type = array($type);
        }
        if ($type === null) {
            $type = array('skos:Concept');
        }
        return $type;
    }

    public function getGroupLimit() 
    {
        return $this->request->getQueryParam('group') !== '' ? $this->request->getQueryParam('group') : null;
    }
    
    public function getParentLimit() 
    {
        return $this->request->getQueryParam('parent') !== '' ? $this->request->getQueryParam('parent') : null;
    }

    public function getOffset() 
    {
        return ($this->request->getQueryParam('offset') && is_numeric($this->request->getQueryParam('offset')) && $this->request->getQueryParam('offset') >= 0) ? $this->request->getQueryParam('offset') : 0;
    }

    public function getSearchLimit()
    {
        return $this->config->getDefaultSearchLimit();
    }
}
