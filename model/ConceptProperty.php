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
    /** stores the property tooltip */
    private $tooltip;
    /** stores the property values */
    private $values;
    /** flag whether the values are sorted, as we do lazy sorting */
    private $is_sorted;
    private $sort_by_notation;

    /**
     * Label parameter seems to be optional in this phase.
     * @param string $prop property type eg. 'rdf:type'.
     * @param string $label property label
     * @param string $tooltip property tooltip/description
     * @param string $super URI of superproperty
     * @param boolean $sort_by_notation whether to sort the property values by their notation code
     */
    public function __construct($prop, $label, $tooltip=null, $super=null, $sort_by_notation=false)
    {
        $this->prop = $prop;
        $this->label = $label;
        $this->tooltip = $tooltip;
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
        if ($this->label !== null) {
            return $this->label;
        }

        // when no label is found, don't show the property at all
        return null;
    }

    /**
     * Returns an alphanumeric ID for the property, suitable for use as a CSS identifier.
     */
    public function getID()
    {
        return preg_replace('/[^A-Za-z0-9-]/', '_', $this->prop);
    }

    /**
     * Returns text for the property tooltip.
     * @return string
     */
    public function getDescription()
    {
        $helpprop = $this->prop . "_help";

        // see if we have a translation with the help text
        $help = gettext($helpprop);
        if ($help != $helpprop) {
            return $help;
        }

       // if not, see if there was a comment/definition for the property in the graph
        if ($this->tooltip !== null) {
            return $this->tooltip;
        }

        // when nothing is found, don't show the tooltip at all
        return null;
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
        # TODO: sort by URI as last resort
        # Note that getLabel() returns URIs in case of no label and may return a prefixed value which affects sorting
        if (!empty($this->values)) {
            if ($this->sort_by_notation) {
                uasort($this->values, function($a, $b) {
                    $anot = $a->getNotation();
                    $bnot = $b->getNotation();
                    if ($anot == null) {
                        if ($bnot == null) {
                            // assume that labels are unique
                            return strcoll(strtolower($a->getLabel()), strtolower($b->getLabel()));
                        }
                        return 1;
                    }
                    else if ($bnot == null) {
                        return -1;
                    }
                    else {
                        // assume that notations are unique
                        return strnatcasecmp($anot, $bnot);
                    }
                });
            }
            else {
                uasort($this->values, function($a, $b) {
                    // assume that labels are unique
                    return strcoll(strtolower($a->getLabel()), strtolower($b->getLabel()));
                });
            }
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
