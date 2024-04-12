<?php

class LabelSkosXL extends DataObject
{

    public function __construct($model, $resource)
    {
        parent::__construct($model, $resource);
    }

    public function getPrefLabel() {
        $label = null;
        $labels = $this->resource->allResources('skosxl:prefLabel');
        foreach($labels as $labres) {
            $label = $labres->getLiteral('skosxl:literalForm');
            if ($label->getLang() == $this->clang) {
                return $label;
            }
        }
        return $label;
    }

    public function getProperties() {
        $ret = array();
        $props = $this->resource->properties();
        foreach($props as $prop) {
            if ($prop !== 'rdf:type' && $prop !== 'skosxl:literalForm') {
                // make sure to use the correct gettext keys for DC namespace
                $propkey = str_starts_with($prop, 'dc11:') ?
                    str_replace('dc11:', 'dc:', $prop) : $prop;
                $ret[$propkey] = $this->resource->get($prop);
            }
        }
        return $ret;
    }

    public function getLang() {
      return $this->resource->getLiteral('skosxl:literalForm')->getLang();
    }

    public function getLiteral() {
        return $this->resource->getLiteral('skosxl:literalForm')->getValue();
    }

    public function __toString() {
        return $this->resource->getLiteral('skosxl:literalForm')->getValue();
    }
}
