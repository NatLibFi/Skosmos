<?php

/**
 * Class for handling concept property values.
 */
class ConceptMappingPropertyValue extends VocabularyDataObject
{
  /** property type */
  private $type;
  private $clang;

  public function __construct($model, $vocab, $resource, $prop, $clang='')
  {
    parent::__construct($model, $vocab, $resource);
    $this->submembers = array();
    $this->type = $prop;
    $this->clang = $clang;
  }

  public function getType()
  {
    return $this->type;
  }

  public function getLabel($lang='')
  {
    if ($this->clang)
      $lang = $this->clang;
    $exvocab = $this->model->guessVocabularyFromURI($this->resource->getUri());
    if (isset($exvocab))
      $exvocid = $exvocab->getId();

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
      $label = $this->getExternalLabel($exvocab, $this->getUri(), $exvocab->getDefaultLanguage());
      if ($label) return $label;
    }

    // using URI as label if nothing else has been found.
    $label = $this->resource->shorten() ? $this->resource->shorten() : $this->resource->getUri();
    return $label;
  }

  public function getUri()
  {
    return $this->resource->getUri();
  }

  public function getExVocab() {
    $exvocab = $this->model->guessVocabularyFromURI($this->getUri());
    if (isset($exvocab))
      return $exvocab->getShortName();
  }

  public function getVocab()
  {
    return $this->vocab;
  }
  
  public function getVocabName()
  {
    $exvocab = $this->model->guessVocabularyFromURI($this->resource->getUri());
    if ($exvocab)
      return $exvocab->getTitle();
    else {
      $scheme = $this->resource->get('skos:inScheme');
      $schemeLabel = null;
      if($scheme) {
        $schemeResource = $this->model->getResourceFromUri($scheme->getUri());
        if ($schemeResource)
          return $schemeResource->label()->getValue();
      }
      if ($schemeLabel == null) {
        // got a label for the concept, but not the scheme - use the host name as scheme label
        $schemeLabel = parse_url($this->resource->getUri(), PHP_URL_HOST);
      }
    }
    return null;
  }

}

