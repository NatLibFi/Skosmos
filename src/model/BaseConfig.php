<?php

/**
 * Base class for configurations. Contains methods for accessing RDF
 * Resources, handling literals and booleans.
 */
abstract class BaseConfig extends DataObject
{
    /**
     * Returns a boolean value based on a literal value from the config.ttl configuration.
     * @param string $property the property to query
     * @param boolean $default the default value if the value is not set in configuration
     * @return boolean the boolean value for the given property, or the default value if not found
     */
    protected function getBoolean($property, $default = false)
    {
        $val = $this->getResource()->getLiteral($property);
        if ($val) {
            return filter_var($val->getValue(), FILTER_VALIDATE_BOOLEAN);
        }
        return $default;
    }

    /**
     * Returns an array of URIs based on a property from the config.ttl configuration.
     * @param string $property the property to query
     * @return string[] List of URIs
     */
    protected function getResources($property)
    {
        $resources = $this->getResource()->allResources($property);
        $ret = array();
        foreach ($resources as $res) {
            $ret[] = $res->getURI();
        }
        return $ret;
    }

    /**
     * Returns a string value based on a literal value from the config.ttl configuration.
     * @param string $property the property to query
     * @param string $default default value
     * @param string $lang preferred language for the literal
     * @return string string value for the given property, or the default value if not found
     */
    protected function getLiteral($property, $default=null, $lang=null)
    {
        if (!isset($lang)) {
            $lang = $this->getEnvLang();
        }

        $literal = $this->getResource()->getLiteral($property, $lang);
        if ($literal) {
            return $literal->getValue();
        }

        // not found with selected language, try any language
        $literal = $this->getResource()->getLiteral($property);
        if ($literal) {
            return $literal->getValue();
        }

        return $default;
    }

}
