<?php

/**
 * ConceptSearchParameters is used for search parameters.
 */
class ConceptSearchParameters
{

    private $request;
    private $vocab;

    public function __construct($request) 
    {
        $this->request = $request;
    }

    public function getLang() 
    {
       return $this->request->getLang();
    } 

    public function getVocab() 
    {
       return $this->request->getVocab();
    } 

    public function getVocabIds()
    {
        return array($this->getVocab()->getId());
    }

    /*
        if (sizeof($vocids) == 1) { // search within vocabulary
            $voc = $vocabs[0];
            $sparql = $voc->getSparql();
            $arrayClass = $voc->getConfig()->getArrayClassURI();
        } else { // multi-vocabulary or global search
            $voc = null;
            $arrayClass = null;
            $sparql = $this->getDefaultSparql();
        }
    */
    public function getArrayClass() 
    {
        if (sizeof($this->getVocabIds()) == 1) { // search within vocabulary
            return $this->getVocab()->getConfig()->getArrayClassURI();
        }
        return null;
    }
    
    public function getSearchTerm() 
    {
        $term = $this->request->getQueryParam('q');
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
        return null;
    }
}
