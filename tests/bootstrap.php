<?php

error_reporting(E_ALL);

// make sure that a SPARQL endpoint is set by an environment variable
$endpoint = getenv('SKOSMOS_SPARQL_ENDPOINT');
if (!$endpoint) {
    // default to Fuseki running on localhost:13030 as provided by init_fuseki.sh
    putenv('SKOSMOS_SPARQL_ENDPOINT=http://localhost:13030/skosmos-test/sparql');
}

# Allow running git commands in the php-actions/phpunit container
# (prevent "dubious ownership" error; /app is owned by another user, not root)
if (getenv('GITHUB_ACTIONS') === 'true') {
    mkdir('/home/runner');
    exec('git config --global --add safe.directory /app');
}

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../src/model/Model.php');
