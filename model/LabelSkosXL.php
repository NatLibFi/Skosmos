<?php

class LabelSkosXL extends DataObject
{

    public function __construct($model, $resource)
    {
        parent::__construct($model, $resource);
    }

    public function getPrefLabel() {
        $label;
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
            if ($prop !== 'skosxl:prefLabel') {
                $ret[$prop] = $this->resource->get($prop);
            }
        }
        return $ret;
    }

    public function getLiteral() {
        return $this->resource->getLiteral('skosxl:literalForm')->getValue(); 
    }

    public function __toString() {
        return $this->resource->getLiteral('skosxl:literalForm')->getValue(); 
    }
}
