<?php
/**
 * Copyright (c) 2012-2013 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/**
 * include site wide settings for vocabularies and languages
 */
try {
  if (!file_exists('./config.inc'))
    throw new Exception( 'config.inc file is missing, please provide one.');
  require_once 'config.inc';
} catch (Exception $e) {
  echo "Error: " . $e->getMessage();
  exit();
}

// PATH_INFO, for example "/ysa/fi"
$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
$parts = explode('/', $path);
$path_fix = (sizeof($parts) > 1) ? str_repeat("../", sizeof($parts) - 2) : "";
if (isset($_GET['base_path'])) {
  $path_fix = str_repeat('../', intval($_GET['base_path']));
}

require_once 'controller/WebController.php';
require_once 'model/Request.php';

$controller = new WebController($path_fix);
$request = new Request();
if (sizeof($parts) <= 2) {
  // if language code missing, redirect to guessed language
  // in any case, redirect to <lang>/
  $lang = sizeof($parts) == 2 && $parts[1] != '' ? $parts[1] : $controller->guessLanguage();
  header("Location: " . $lang . "/");
} else {
  if (array_key_exists($parts[1], $LANGUAGES)) { // global pages
    $request->setLang($parts[1]);
    if ($parts[2] == 'about' || $parts[2] == 'feedback' || $parts[2] == 'search')
      $request->setPage($parts[2]);
    else
      $request->setPage('');
    if ($request->getPage() == '') {
      $controller->invokeVocabularies($request);
    } elseif ($request->getPage() == 'about') {
      $controller->invokeAboutPage($request);
    } elseif ($request->getPage() == 'feedback') {
      $controller->invokeFeedbackForm($request);
    } elseif ($request->getPage() == 'search') {
      $controller->invokeGlobalSearch($request);
    } else {
      $controller->invokeGenericErrorPage($request);
    }
  } else { // vocabulary-specific pages
    $vocab = $parts[1];
    $request->setVocabid($parts[1]);
    if (sizeof($parts) == 3) { // language code missing
      $lang = $controller->guessLanguage();
      header("Location: " . $lang . "/");
    } else {
      if (array_key_exists($parts[2], $LANGUAGES)) {
        $lang = $parts[2];
        $request->setLang($parts[2]);
        if ($parts[3] == '') {
          $controller->invokeVocabularyHome($request);
        } elseif ($parts[3] == 'feedback') {
          $controller->invokeFeedbackForm($request);
        } elseif ($parts[3] == 'search') {
          $controller->invokeVocabularySearch($vocab, $lang);
        } elseif ($parts[3] == 'index') {
          if (sizeof($parts) == 4 || (sizeof($parts) == 5) && $parts[4] === '') { // no letter
            $controller->invokeAlphabeticalIndex($vocab, $lang);
          } else { // letter given
            $controller->invokeAlphabeticalIndex($vocab, $lang, $parts[4]);
          }
        } elseif ($parts[3] == 'page') {
          if (isset($_GET['uri'])) {
            $controller->invokeVocabularyConcept($vocab, $lang, $_GET['uri']);
          } elseif (sizeof($parts) == 5) {
            $controller->invokeVocabularyConcept($vocab, $lang, $parts[4]);
          } else {
            $controller->invokeGenericErrorPage();
          }
        } elseif ($parts[3] == 'groups') {
          if (sizeof($parts) == 4) {
            if (isset($_GET['uri'])) {
              $controller->invokeGroupContents($vocab, $lang, $_GET['uri']);
            } else {
              $controller->invokeGroupIndex($vocab, $lang);
            }
          } else {
            if (isset($_GET['uri']))
              $controller->invokeGroupContents($vocab, $lang, $_GET['uri']);
            elseif ($parts[4] !== '')
              $controller->invokeGroupContents($vocab, $lang, $parts[4]);
            else
              $controller->invokeGroupIndex($vocab, $lang);
          }
        } else {
          $controller->invokeGenericErrorPage();
        }
      } else { // language code missing, redirect to some language version
        $lang = $controller->guessLanguage($vocab);
        $pattern = '|' . preg_quote("/$vocab/") . '|';
        $location = preg_replace($pattern, "/$vocab/$lang/", $_SERVER['REQUEST_URI'], 1);
        header("Location: $location");
      }
    }
  }
}
