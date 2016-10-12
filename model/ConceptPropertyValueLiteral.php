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

    public function __construct($literal, $prop)
    {
        $this->literal = $literal;
        $this->type = $prop;
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

    public function getType()
    {
        return $this->type;
    }

    public function getContainsHtml() {
        return preg_match("/\/[a-z]*>/i", $this->literal->getValue()) != 0;
    }

    public function getLabel()
    {
        // if the property is a date object converting it to a human readable representation.
        if ($this->literal instanceof EasyRdf_Literal_Date) {
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

    public function getUri()
    {
        return null;
    }

    public function getNotation()
    {
        return null;
    }

}
