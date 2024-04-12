<?php

/**
 * Includes the side wide settings.
 */
require_once 'vendor/autoload.php';

header("Access-Control-Allow-Origin: *"); // enable CORS for the whole REST API

try {
    $config = new GlobalConfig();
    $model = new Model($config);
    $controller = new RestController($model);
    $request = new Request($model);
    $path = $request->getServerConstant('PATH_INFO') ? $request->getServerConstant('PATH_INFO') : ''; // eg. "/search"
    $parts = explode('/', $path);
    $request->setUri($request->getQueryParam('uri'));
    $request->setLang($request->getQueryParam('lang'));
    if ($request->getQueryParam('vocab')) {
        $request->setVocab($request->getQueryParam('vocab'));
    }
    if ($request->getQueryParam('clang')) {
        $request->setContentLang($request->getQueryParam('clang'));
    } elseif ($request->getQueryParam('lang')) {
        $request->setContentLang($request->getQueryParam('lang'));
    }

    if (sizeof($parts) < 2 || $parts[1] == "") {
        header("HTTP/1.0 404 Not Found");
        echo ("404 Not Found");
    } elseif ($parts[1] == 'vocabularies') {
        $controller->vocabularies($request);
    } elseif ($parts[1] == 'search') {
        $controller->search($request);
    } elseif ($parts[1] == 'label') {
        $controller->label($request);
    } elseif ($parts[1] == 'types') {
        $controller->types($request);
    } elseif ($parts[1] == 'data') {
        $controller->data($request);
    } elseif (sizeof($parts) == 2) {
        header("Location: " . $parts[1] . "/");
    } else {
        $vocab = $parts[1];
        try {
            $request->setVocab($parts[1]);
        } catch (Exception | ValueError $e) {
            header("HTTP/1.0 404 Not Found");
            header("Content-type: text/plain; charset=utf-8");
            echo ("404 Not Found : Vocabulary id '$parts[1]' not found.");
            return;
        }
        $lang = $request->getQueryParam('lang') ? $request->getQueryParam('lang') : $request->getVocab()->getConfig()->getDefaultLanguage();
        $request->setLang($lang);
        if ($parts[2] == '') {
            $controller->vocabularyInformation($request);
        } elseif ($parts[2] == 'types') {
            $controller->types($request);
        } elseif ($parts[2] == 'topConcepts') {
            $controller->topConcepts($request);
        } elseif ($parts[2] == 'data') {
            $controller->data($request);
        } elseif ($parts[2] == 'mappings') {
            $controller->mappings($request);
        } elseif ($parts[2] == 'search') {
            $controller->search($request);
        } elseif ($parts[2] == 'label') {
            $controller->label($request);
        } elseif ($parts[2] == 'lookup') {
            $controller->lookup($request);
        } elseif ($parts[2] == 'index' && sizeof($parts) == 4) {
            $letter = $parts[3];
            if ($letter == "") {
                $controller->indexLetters($request);
            } else {
                $controller->indexConcepts($letter, $request);
            }
        } elseif ($parts[2] == 'broader') {
            $controller->broader($request);
        } elseif ($parts[2] == 'broaderTransitive') {
            $controller->broaderTransitive($request);
        } elseif ($parts[2] == 'narrower') {
            $controller->narrower($request);
        } elseif ($parts[2] == 'narrowerTransitive') {
            $controller->narrowerTransitive($request);
        } elseif ($parts[2] == 'hierarchy') {
            $controller->hierarchy($request);
        } elseif ($parts[2] == 'children') {
            $controller->children($request);
        } elseif ($parts[2] == 'related') {
            $controller->related($request);
        } elseif ($parts[2] == 'vocabularyStatistics') {
            $controller->vocabularyStatistics($request);
        } elseif ($parts[2] == 'labelStatistics') {
            $controller->labelStatistics($request);
        } elseif ($parts[2] == 'groups') {
            $controller->groups($request);
        } elseif ($parts[2] == 'groupMembers') {
            $controller->groupMembers($request);
        } elseif ($parts[2] == 'new') {
            $controller->newConcepts($request);
        } elseif ($parts[2] == 'modified') {
            $controller->modifiedConcepts($request);
        } else {
            header("HTTP/1.0 404 Not Found");
            echo ("404 Not Found");
        }
    }
} catch (Exception $e) {
    header("HTTP/1.0 500 Internal Server Error");
    echo('ERROR: ' . $e->getMessage());
}
