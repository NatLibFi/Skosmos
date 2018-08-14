<?php

/* Converts old config.inc and vocabulary.ttl configuration files, into new config.ttl */

// run only in cli command line mode
if (php_sapi_name() !== "cli") {
    throw new \Exception("This tool can run only in command line mode!");
}

/**
 * Parse the vocabularies file, and return it in two sections, the
 * prefixes, and the rest of the configuration.
 * @param string $vocabulariesFile vocabularies file location
 * @return array
 */
function parse_vocabularies_file($vocabulariesFile)
{
    if (!is_file($vocabulariesFile)) {
        throw new \Exception("Invalid vocabularies file: $vocabulariesFile");
    }
    $prefixes = "";
    $config = "";
    $handle = fopen($vocabulariesFile, "r");
    if (!$handle) {
        throw new \Exception("Failed to open vocabularies file: $vocabulariesFile");
    }
    $prefixPrefix = '@prefix';
    while (($line = fgets($handle)) !== false) {
        if ($prefixPrefix === substr(trim($line), 0, strlen($prefixPrefix))) {
            $prefixes .= "$line";
        } else {
            $config .= "$line";
        }
    }
    fclose($handle);
    return ["prefixes" => $prefixes, 'config' => $config];
}

// print usage if no args
if (!isset($argc) || $argc !== 3) {
    throw new \Exception("Usage: php migrate-config config.inc vocabularies.ttl > config.ttl");
}

$configFile = $argv[1];
$vocabulariesFile = $argv[2];

# parse the file into an array with the keys "prefixes" and "config"
$vocabs = parse_vocabularies_file($vocabulariesFile);

# read the old style config file and use the constants to set variables for use in the template
if (!is_file($configFile)) {
    throw new \Exception("Invalid configuration file: $configFile");
}
include($configFile);
$endpoint = defined('DEFAULT_ENDPOINT') ? DEFAULT_ENDPOINT : "?";
$dialect = defined('DEFAULT_SPARQL_DIALECT') ? DEFAULT_SPARQL_DIALECT : "?";
$collationEnabled = defined('SPARQL_COLLATION_ENABLED') ? (SPARQL_COLLATION_ENABLED ? "true" : "false") : "?";
$sparqlTimeout = defined('SPARQL_TIMEOUT') ? SPARQL_TIMEOUT : "?";
$httpTimeout = defined('HTTP_TIMEOUT') ? HTTP_TIMEOUT : "?";
$serviceName = defined('SERVICE_NAME') ? SERVICE_NAME : "?";
$baseHref = defined('BASE_HREF') ? BASE_HREF : "?";
$languages = "";
if (isset($LANGUAGES) && !is_null($LANGUAGES) && is_array($LANGUAGES) && !empty($LANGUAGES)) {
    foreach ($LANGUAGES as $code => $name) {
        $languages .= "        [ rdfs:label \"$code\" ; rdf:value \"$name\" ]\n";
    }
}
$searchResultsSize = defined('SEARCH_RESULTS_SIZE') ? SEARCH_RESULTS_SIZE : "?";
$transitiveLimit = defined('DEFAULT_TRANSITIVE_LIMIT') ? DEFAULT_TRANSITIVE_LIMIT : "?";
$logCaughtExceptions = defined('LOG_CAUGHT_EXCEPTIONS') ? (LOG_CAUGHT_EXCEPTIONS ? "true" : "false") : "?";
$logBrowserConsole = defined('LOG_BROWSER_CONSOLE') ? (LOG_BROWSER_CONSOLE ? "true" : "false") : "?";
$logFileName = defined('LOG_FILE_NAME') ? LOG_FILE_NAME : "?";
$templateCache = defined('TEMPLATE_CACHE') ? TEMPLATE_CACHE : "?";
$customCss = defined('CUSTOM_CSS') ? CUSTOM_CSS : "?";
$feedbackAddress = defined('FEEDBACK_ADDRESS') ? FEEDBACK_ADDRESS : "?";
$feedbackSender = defined('FEEDBACK_SENDER') ? FEEDBACK_SENDER : "?";
$feedbackEnvelopeSender = defined('FEEDBACK_ENVELOPE_SENDER') ? FEEDBACK_ENVELOPE_SENDER : "?";
$uiLanguageDropdown = defined('UI_LANGUAGE_DROPDOWN') ? (UI_LANGUAGE_DROPDOWN ? "true" : "false") : "?";
$uiHoneypotEnabled = defined('UI_HONEYPOT_ENABLED') ? (UI_HONEYPOT_ENABLED ? "true" : "false") : "?";
$uiHoneypotTime = defined('UI_HONEYPOT_TIME') ? UI_HONEYPOT_TIME : "?";
$globalPluginsArray = [];
$globalPlugins = "";
if (defined('GLOBAL_PLUGINS') && !is_null(GLOBAL_PLUGINS) && is_string(GLOBAL_PLUGINS) && !empty(trim(GLOBAL_PLUGINS))) {
    foreach (explode(' ', GLOBAL_PLUGINS) as $pluginName) {
        $globalPluginsArray[] = "\"$pluginName\"";
    }
    $globalPlugins = " " . implode(', ', $globalPluginsArray) . " ";
}

# print the prefixes
echo $vocabs['prefixes'];

# print the global config using a string template
$globalConfig = <<<EOT

# Skosmos main configuration

:config a skosmos:Configuration ;
    # SPARQL endpoint
    # a local Fuseki server is usually on localhost:3030
    skosmos:sparqlEndpoint "$endpoint" ;
    # sparql-query extension, or "Generic" for plain SPARQL 1.1
    # set to "JenaText" instead if you use Fuseki with jena-text index
    skosmos:sparqlDialect "$dialect" ;
    # whether to enable collation in sparql queries
    skosmos:sparqlCollationEnabled $collationEnabled ;
    # HTTP client configuration
    skosmos:sparqlTimeout $sparqlTimeout ;
    skosmos:httpTimeout $httpTimeout ;
    # customize the service name
    skosmos:serviceName "$serviceName" ;
    # customize the base element. Set this if the automatic base url detection doesn't work. For example setups behind a proxy.
    skosmos:baseHref "$baseHref" ;
    # interface languages available, and the corresponding system locales
    skosmos:languages (
$languages    ) ;
    # how many results (maximum) to load at a time on the search results page
    skosmos:searchResultsSize $searchResultsSize ;
    # how many items (maximum) to retrieve in transitive property queries
    skosmos:transitiveLimit $transitiveLimit ;
    # whether or not to log caught exceptions
    skosmos:logCaughtExceptions $logCaughtExceptions ;
    # set to TRUE to enable logging into browser console
    skosmos:logBrowserConsole $logBrowserConsole ;
    # set to a logfile path to enable logging into log file
    skosmos:logFileName "$logFileName" ;
    # a default location for Twig template rendering
    skosmos:templateCache "$templateCache" ;
    # customize the css by adding your own stylesheet
    skosmos:customCss "$customCss" ;
    # default email address where to send the feedback
    skosmos:feedbackAddress "$feedbackAddress" ;
    # email address to set as the sender for feedback messages
    skosmos:feedbackSender "$feedbackSender" ;
    # email address to set as the envelope sender for feedback messages
    skosmos:feedbackEnvelopeSender "$feedbackEnvelopeSender" ;
    # whether to display the ui language selection as a dropdown (useful for cases where there are more than 3 languages) 
    skosmos:uiLanguageDropdown $uiLanguageDropdown ;
    # whether to enable the spam honey pot or not, enabled by default
    skosmos:uiHoneypotEnabled $uiHoneypotEnabled ;
    # default time a user must wait before submitting a form
    skosmos:uiHoneypotTime $uiHoneypotTime ;
    # plugins to activate for the whole installation (including all vocabularies)
    skosmos:globalPlugins ($globalPlugins) .

EOT;

echo preg_replace('/(\\s*)(.*\\?[\\"]?[\s]*;.*)/', "\\1# \\2", $globalConfig);

echo "\n# Skosmos vocabularies\n";

# print the vocabulary-specific configuration
echo $vocabs['config'];
