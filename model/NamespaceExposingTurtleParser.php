<?php
/**
 * Copyright (c) 2016 University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

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
