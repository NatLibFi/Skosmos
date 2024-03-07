<?php

/**
 * Class for handling concept property values.
 */
class ConceptPropertyValueLiteral extends VocabularyDataObject
{
    /** the literal object for the property value */
    private $literal;
    /** property type */
    private $type;
    /** content language */
    private $clang;

    public function __construct($model, $vocab, $resource, $literal, $prop, $clang = '')
    {
        parent::__construct($model, $vocab, $resource);
        $this->literal = $literal;
        $this->type = $prop;
        $this->clang = $clang;
    }

    public function __toString()
    {
        if ($this->getLabel() === null) {
            return "";
        }

        return $this->getLabel();
    }

    public function getLang()
    {
        return $this->literal->getLang();
    }

    /**
     * A method for fetching a datatype.
     */
    public function getDatatype(): ?string
    {
        $datatype = $this->literal->getDatatype();
        if ($datatype === null) {
            return null;
        }
        $graph = $this->resource->getGraph();
        if ($graph->resource($datatype)->label($this->clang)) {
            $dtLabel = $graph->resource($datatype)->label($this->clang);
            return $dtLabel->getValue();
        }
        return null;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getContainsHtml()
    {
        return preg_match("/\/[a-z]*>/i", $this->getLabel()) != 0;
    }

    public function getLabel()
    {
        // if the property is a date object converting it to a human readable representation.
        if ($this->literal instanceof EasyRdf\Literal\Date) {
            try {
                $val = $this->literal->getValue();
                return Punic\Calendar::formatDate($val, 'short');
            } catch (Exception $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);
                return (string) $this->literal;
            }
        }
        return $this->literal->getValue();
    }

    public function getSortKey()
    {
        return strtolower($this->getLabel());
    }

    public function getUri()
    {
        return null;
    }

    public function getNotation()
    {
        return null;
    }

    public function hasXlProperties()
    {
        $xlLabel = $this->getXlLabel();
        return ($xlLabel !== null && !empty($xlLabel->getProperties()));
    }

    public function getXlLabel()
    {
        $graph = $this->resource->getGraph();
        $labelResources = $graph->resourcesMatching('skosxl:literalForm', $this->literal);
        foreach($labelResources as $labres) {
            return new LabelSkosXL($this->model, $labres);
        }
        return null;
    }

}
