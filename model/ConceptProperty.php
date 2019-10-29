<?php

/**
 * Class for handling concept properties.
 */
class ConceptProperty
{
    /** stores the property type */
    private $prop;
    /** stores the property supertype */
    private $super;
    /** stores the property label */
    private $label;
    /** stores the property values */
    private $values;
    /** flag whether the values are sorted, as we do lazy sorting */
    private $is_sorted;
    private $sort_by_notation;

    /**
     * Label parameter seems to be optional in this phase.
     * @param string $prop property type eg. 'rdf:type'.
     * @param string $label
     */
    public function __construct($prop, $label, $super=null, $sort_by_notation=false)
    {
        $this->prop = $prop;
        $this->label = $label;
        $this->values = array();
        $this->is_sorted = true;
        $this->super = $super;
        $this->sort_by_notation = $sort_by_notation;
    }

    /**
     * Gets the gettext translation for a property or returns the identifier as a fallback.
     */
    public function getLabel()
    {
        // first see if we have a translation
        // we don't maintain DC 1.1 translations separate from DC Terms
        $prop = (substr($this->prop, 0, 5) == 'dc11:') ?
            str_replace('dc11:', 'dc:', $this->prop) : $this->prop;
        $label = gettext($prop);
        if ($label != $prop) {
            return $label;
        }

        // if not, see if there was a label for the property in the graph
        if ($this->label) {
            return $this->label;
        }

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
     * @return ConceptMappingPropertyValue[]
     */
    public function getValues()
    {
        if (!$this->is_sorted) {
            $this->sortValues();
        }
        return $this->values;
    }

    public function addValue($value)
    {
        $this->values[ltrim($value->getNotation() . ' ') . $value->getLabel() . rtrim(' ' . $value->getUri())] = $value;
        $this->is_sorted = false;
    }

    private function sortValues()
    {
        if (!empty($this->values)) {
            uksort($this->values, function($a, $b) {
                return $this->sort_by_notation ? strnatcasecmp($a, $b) : strcoll(strtolower($a),strtolower($b));
            });
        }
        $this->is_sorted = true;
    }

    /**
     * Returns property type as a string.
     * @return string eg. 'rdf:type'.
     */
    public function getType()
    {
        return $this->prop;
    }

    /**
     * Returns property supertype (?property skos:subPropertyOf ?super) as a string.
     * @return string eg. 'skos:hiddenLabel'.
     */
    public function getSubPropertyOf()
    {
        return $this->super;
    }
}
