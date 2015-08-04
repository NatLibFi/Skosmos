<?php
/**
 * Copyright (c) 2012 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/**
 * Includes the side wide settings.
 */
require_once 'config.inc';

$path = $_SERVER['PATH_INFO']; // esim. "/search"
$parts = explode('/', $path);

header("Access-Control-Allow-Origin: *"); // enable CORS for the whole REST API

try {
  require_once 'controller/RestController.php';
  require_once 'model/Model.php';

  $model = new Model();
  $controller = new RestController($model);
  $request = new Request($model);
  $request->setUri($request->getQueryParam('uri'));
  $request->setLang($request->getQueryParam('lang'));
  if ($request->getQueryParam('vocab'))
    $request->setVocab($request->getQueryParam('vocab'));

  if (sizeof($parts) < 2 || $parts[1] == "") {
    header("HTTP/1.0 404 Not Found");
    echo("404 Not Found");
  } elseif ($parts[1] == 'vocabularies') {
    $controller->vocabularies($request);
  } elseif ($parts[1] == 'search') {
    $controller->search($request);
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
      $lang = $request->getQueryParam('lang') ? $request->getQueryParam('lang') : $request->getVocab()->getDefaultLanguage();
      $request->setLang($lang);
    } catch (Exception $e) {
      header("HTTP/1.0 404 Not Found");
      header("Content-type: text/plain; charset=utf-8");
      echo("404 Not Found : Vocabulary id '$parts[1]' not found.");
      exit();
    }
    if ($parts[2] == '') {
      $controller->vocabularyInformation($request);
    } elseif ($parts[2] == 'types') {
      $controller->types($request);
    } elseif ($parts[2] == 'topConcepts') {
      $controller->topConcepts($request);
    } elseif ($parts[2] == 'data') {
      $controller->data($request);
    } elseif ($parts[2] == 'search') {
      $controller->search($request);
    } elseif ($parts[2] == 'label') {
      $controller->label($request);
    } elseif ($parts[2] == 'lookup') {
      $controller->lookup($request);
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
    } else {
      header("HTTP/1.0 404 Not Found");
      echo("404 Not Found");
    }
  }
} catch (Exception $e) {
  header("HTTP/1.0 500 Internal Server Error");
  die ('ERROR: ' . $e->getMessage());
}
