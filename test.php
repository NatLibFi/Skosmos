<?php
require_once 'vendor/autoload.php';
$graph = new EasyRdf_Graph();
$graph->parse(file_get_contents('vocabularies.ttl'));
