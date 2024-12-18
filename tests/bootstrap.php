<?php

error_reporting(E_ALL);

// make sure that a SPARQL endpoint is set by an environment variable
$endpoint = getenv('SKOSMOS_SPARQL_ENDPOINT');
if (!$endpoint) {
    // default to Fuseki running on localhost:9030 as provided by init_fuseki.sh
    putenv('SKOSMOS_SPARQL_ENDPOINT=http://localhost:9030/skosmos/sparql');
}

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../src/model/Model.php');
