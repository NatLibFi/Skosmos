<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// SPARQL endpoint de Fuseki
$fuseki_endpoint = 'http://10.208.58.26:3030/ias/query';

// Obtener parámetros
$report = $_GET['report'] ?? null;
$subchapter = $_GET['subchapter'] ?? null;

// Ejecutar consulta SPARQL
function sparql_query($endpoint, $query) {
    $url = $endpoint . '?query=' . urlencode($query);
    $response = file_get_contents($url, false, stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Accept: application/sparql-results+json'
        ]
    ]));
    return json_decode($response, true);
}

function printLink($label, $params) {
    $url = $_SERVER['PHP_SELF'] . '?' . http_build_query($params);
    echo "<li><a href='$url'>$label</a></li>";
}

function showResourceDetails($endpoint, $resourceUri) {
    $query = "PREFIX ipbes: <http://ontology.ipbes.net/>\nPREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>\nSELECT ?p ?o ?label WHERE { <$resourceUri> ?p ?o . OPTIONAL { ?o rdfs:label ?label } }";
    $results = sparql_query($endpoint, $query);
    echo "<ul>";
    foreach ($results['results']['bindings'] as $row) {
        $prop = basename($row['p']['value']);
        $value = $row['o']['value'];
        $label = $row['label']['value'] ?? '';
        $displayValue = $label ? "$label ($value)" : $value;
        echo "<li><strong>$prop:</strong> $displayValue</li>";
    }
    echo "</ul>";
}

function showReferencePersons($endpoint, $refUri) {
    $query = "PREFIX ipbes: <http://ontology.ipbes.net/>\nPREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>\nSELECT ?person ?label WHERE { <$refUri> ipbes:hasPerson ?person . OPTIONAL { ?person rdfs:label ?label } }";
    $results = sparql_query($endpoint, $query);
    if (!empty($results['results']['bindings'])) {
        echo "<ul>";
        foreach ($results['results']['bindings'] as $row) {
            $personLabel = $row['label']['value'] ?? basename($row['person']['value']);
            echo "<li>$personLabel</li>";
        }
        echo "</ul>";
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IPBES Report Navigator</title>
    <style>
        body { font-family: Arial, sans-serif; }
        ul { list-style-type: none; padding: 0; }
        li { margin-bottom: 8px; }
        a { text-decoration: none; color: #337ab7; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<h1>IPBES Report Navigator</h1>
<?php
if (!$report && !$subchapter) {
    $query = "PREFIX ipbes: <http://ontology.ipbes.net/>\nPREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>\nSELECT DISTINCT ?report ?label WHERE { ?report a ipbes:Report . OPTIONAL { ?report rdfs:label ?label } } ORDER BY ?label";
    $results = sparql_query($fuseki_endpoint, $query);
    echo "<h2>Available Reports</h2><ul>";
    foreach ($results['results']['bindings'] as $row) {
        $label = $row['label']['value'] ?? basename($row['report']['value']);
        printLink($label, ['report' => $row['report']['value']]);
    }
    echo "</ul>";
} elseif ($report && !$subchapter) {
    echo "<h2>Report Details</h2>";
    showResourceDetails($fuseki_endpoint, $report);

    $query = "PREFIX ipbes: <http://ontology.ipbes.net/>\nPREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>\nSELECT ?subchapter ?label WHERE { ?subchapter a ipbes:SubChapter ; ipbes:hasReport <{$report}> . OPTIONAL { ?subchapter rdfs:label ?label } } ORDER BY ?label";
    $results = sparql_query($fuseki_endpoint, $query);
    echo "<h3>Subchapters</h3><ul>";
    foreach ($results['results']['bindings'] as $row) {
        $label = $row['label']['value'] ?? basename($row['subchapter']['value']);
        printLink($label, ['report' => $report, 'subchapter' => $row['subchapter']['value']]);
    }
    echo "</ul><p><a href='?'>Back to Reports</a></p>";
} elseif ($subchapter) {
    echo "<h2>Subchapter Details</h2>";
    showResourceDetails($fuseki_endpoint, $subchapter);

    $query = "PREFIX ipbes: <http://ontology.ipbes.net/>\nPREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>\nSELECT ?ref ?doi ?label WHERE { ?ref a ipbes:Reference ; ipbes:hasReport <{$subchapter}> . OPTIONAL { ?ref ipbes:hasDoi ?doi . ?ref rdfs:label ?label } } ORDER BY ?label";
    $results = sparql_query($fuseki_endpoint, $query);
    echo "<h3>References</h3><ul>";
    foreach ($results['results']['bindings'] as $row) {
        $refLabel = $row['label']['value'] ?? basename($row['ref']['value']);
        $doiUrl = isset($row['doi']) ? "https://doi.org/" . ltrim($row['doi']['value'], 'doi:') : '';
        $doiLink = $doiUrl ? " (<a href='$doiUrl'>DOI</a>)" : '';
        echo "<li>$refLabel$doiLink";
        showReferencePersons($fuseki_endpoint, $row['ref']['value']);
        echo "</li>";
    }
    echo "</ul><p><a href='?report=" . urlencode($report) . "'>Back to Subchapters</a></p>";
}
?>
</body>
</html>