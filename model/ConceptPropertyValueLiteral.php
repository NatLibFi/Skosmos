<?php

/**
 * Class for handling concept property values.
 */
class ConceptPropertyValueLiteral
{
  /** the literal object for the property value */
  private $literal;
  /** property type */
  private $type;

  //public function __construct($prop, $uri, $vocab, $lang, $label, $exvocab = null, $parent = null, $vocabname = null)
  public function __construct($literal, $prop)
  {
    $this->literal = $literal;
    $this->type = $prop;
  }

  public function __toString()
  {
    if ($this->getLabel() === null)
      return "";
    return $this->getLabel();
  }

  public function getLang()
  {
    return $this->literal->getLang();
  }

  public function getExVocab()
  {
    return $this->exvocab;
  }

  public function getType()
  {
    return $this->type;
  }

  public function getLabel()
  {
    // if the property is a date object a string representation is passed as the value.
    if ($this->literal->getDataType() === 'xsd:date' || $this->literal->getDataType() === 'xsd:dateTime')
      return $this->literal->__toString();
    return $this->literal->getValue();
  }

  public function getUri()
  {
    return null;
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
    return $this->vocab->getShortName();
  }

}
