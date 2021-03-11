<?php

class SkosmosTurtleParser extends EasyRdf\Parser\Turtle
{
    /**
     * Returns the namespace prefixes as an array of prefix => URI
     * @return array $namespaces
     */
    public function getNamespaces()
    {
        return $this->namespaces;
    }

}
