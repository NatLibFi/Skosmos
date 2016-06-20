<?php

class NamespaceExposingTurtleParser extends EasyRdf_Parser_Turtle
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
