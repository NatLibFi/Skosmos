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

    /**
     * Parse Turtle into a new Graph and return it.
     * @return EasyRdf\Graph
     */
    public function parseGraph($data, $baseUri)
    {
        $graph = new EasyRdf\Graph();
        $this->parse($graph, $data, 'turtle', $baseUri);
        return $graph;
    }
}
