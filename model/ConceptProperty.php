<?php

/**
 * Class for handling concept properties.
 */
class ConceptProperty
{
  /** stores the property type */
  private $prop;
  /** stores the property label */
  private $label;
  /** stores the property values */
  private $values;

  /**
   * Label parameter seems to be optional in this phase.
   * @param string $prop property type eg. 'rdf:type'.
   * @param string $label
   * @param array $values contains ConceptPropertyValues
   */
  public function __construct($prop, $label)
  {
    $this->prop = $prop;
    $this->label = $label;
    $this->values = array(); 
  }

  /**
   * Gets the gettext translation for a property or returns the identifier as a fallback.
   */
  public function getLabel()
  {
    // first see if we have a translation
    $label = gettext($this->prop);
    if ($label != $this->prop) return $label;
    // if not, see if there was a label for the property in the graph
    if ($this->label) return $this->label;
    // when no label is found, don't show the property at all
    return null;
  }

  /**
   * Returns a gettext translation for the property tooltip.
   * @return string
   */
  public function getDescription()
  {
    $helpprop = $this->prop . "_help";

    return gettext($helpprop); // can't use string constant, it'd be picked up by xgettext
  }

  /**
   * Returns an array of the property values.
   * @return array containing ConceptPropertyValue objects.
   */
  public function getValues()
  {
    return $this->values;
  }
  
  public function addValue($value, $lang='')
  {
    $label = $value->getLabel($lang) ? $value->getLabel($lang) : $value->getLabel();
    if (!is_string($label))
      $label = $label->getValue();
    $this->values[$label] = $value;
    $this->sortValues();
  }

  private function sortValues()
  {
    if (!empty($this->values))
      ksort($this->values);
  }

  /**
   * Returns property type as a string.
   * @return string eg. 'rdf:type'.
   */
  public function getType()
  {
    return $this->prop;
  }
}

