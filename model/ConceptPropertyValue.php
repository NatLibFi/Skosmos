<?php

/**
 * Class for handling concept property values.
 */
class ConceptPropertyValue extends VocabularyDataObject
{
  /** language code of the value literal */
  private $lang;
  /** if the concept is inherited from a another vocabulary store that identifier here */
  private $exvocab;
  /** property type */
  private $type;
  /** literal value of the property */
  private $label;
  /** uri of the concept the property value belongs to */
  private $uri;
  /** id of the vocabulary the concept belongs to */
  protected $vocab;
  /** vocabulary label */
  private $vocabName;
  /** if the property is a subProperty of a another property */
  private $parentProperty;
  private $submembers;

  //public function __construct($prop, $uri, $vocab, $lang, $label, $exvocab = null, $parent = null, $vocabname = null)
  public function __construct($model, $vocab, $resource, $prop)
  {
    parent::__construct($model, $vocab, $resource);
    $this->submembers = array();
    //$this->lang = $lang;
    //$this->exvocab = $exvocab;
    $this->type = $prop;
    //$this->label = $label;
    //$this->uri = $uri;
    //$this->vocab = $vocab;
    //$this->vocabName = $vocabname;
    //$this->parentProperty = $parent;
  }

  public function __toString()
  {
    if ($this->label === null)
      return "";
    return $this->label;
  }

  public function getLang()
  {
    return $this->lang;
  }

  public function getExVocab()
  {
    return $this->exvocab;
  }

  public function getType()
  {
    return $this->type;
  }

  public function getLabel($lang='')
  {
    if ($this->resource->label($lang) !== null) { // current language
      return $this->resource->label($lang);
    } elseif ($this->resource->label() !== null) { // any language
      return $this->resource->label();
    } elseif ($this->resource->getLiteral('rdf:value', $lang) !== null) { // current language
      return $this->resource->getLiteral('rdf:value', $lang); 
    } elseif ($this->resource->getLiteral('rdf:value') !== null) { // any language
      return $this->resource->getLiteral('rdf:value');
    } 
    return null;
  }

  public function getUri()
  {
    return $this->resource->getUri();
  }

  public function getParent()
  {
    return $this->parentProperty;
  }

  public function getVocab()
  {
    return $this->vocab;
  }
  
  public function getVocabName()
  {
    return $this->vocabName;
  }

  public function addSubMember($type, $label, $uri, $vocab, $lang, $exvocab = null)
  {
    $this->submembers[$label] = new ConceptPropertyValue($type, $uri, $vocab, $lang, $label, $exvocab = null);
    $this->sortSubMembers();
  }

  public function getSubMembers()
  {
    if (empty($this->submembers))
      return null;
    return $this->submembers;
  }

  public function sortSubMembers()
  {
    if (!empty($this->submembers))
      ksort($this->submembers);
  }

}
