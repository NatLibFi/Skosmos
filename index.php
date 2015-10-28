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
    if (!file_exists('./config.inc')) {
        throw new Exception('config.inc file is missing, please provide one.');
    }

    require_once 'config.inc';
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

require_once 'controller/WebController.php';
require_once 'model/Model.php';

$model = new Model();
$controller = new WebController($model);
$request = new Request($model);

// PATH_INFO, for example "/ysa/fi"
$path = $request->getServerConstant('PATH_INFO') ? $request->getServerConstant('PATH_INFO') : '';
$parts = explode('/', $path);

// used for making proper hrefs for the language selection
$request->setRequestUri($request->getServerConstant('HTTP_HOST') . $request->getServerConstant('REQUEST_URI'));

if (sizeof($parts) <= 2) {
    // if language code missing, redirect to guessed language
    // in any case, redirect to <lang>/
    $lang = sizeof($parts) == 2 && $parts[1] !== '' ? $parts[1] : $controller->guessLanguage();
    header("Location: " . $lang . "/");
} else {
    if (array_key_exists($parts[1], $LANGUAGES)) { // global pages
        $request->setLang($parts[1]);
        $content_lang = $request->getQueryParam('clang');
        $request->setContentLang($content_lang);
        ($parts[2] == 'about' || $parts[2] == 'feedback' || $parts[2] == 'search') ? $request->setPage($parts[2]) : $request->setPage('');
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
        try {
            $request->setVocab($parts[1]);
        } catch (Exception $e) {
            $request->setLang($controller->guessLanguage());
            $controller->invokeGenericErrorPage($request);
            exit();
        }
        if (sizeof($parts) == 3) { // language code missing
            $lang = $controller->guessLanguage();
            header("Location: " . $lang . "/");
        } else {
            if (array_key_exists($parts[2], $LANGUAGES)) {
                $lang = $parts[2];
                $content_lang = $request->getQueryParam('clang') ? $request->getQueryParam('clang') : $lang;
                $request->setContentLang($content_lang);
                $request->setLang($parts[2]);
                $request->setPage($parts[3]);
                if (!$request->getPage()) {
                    $request->setPage('vocab');
                    $controller->invokeVocabularyHome($request);
                } elseif ($request->getPage() == 'feedback') {
                    $controller->invokeFeedbackForm($request);
                } elseif ($request->getPage() == 'search') {
                    $controller->invokeVocabularySearch($request);
                } elseif ($request->getPage() == 'index') {
                    if ((sizeof($parts) == 5) && $parts[4] !== '') // letter given
                    {
                        $request->setLetter($parts[4]);
                    }

                    $controller->invokeAlphabeticalIndex($request);
                } elseif ($request->getPage() == 'page') {
                    ($request->getQueryParam('uri')) ? $request->setUri($request->getQueryParam('uri')) : $request->setUri($parts[4]);
                    if ($request->getUri() === null || $request->getUri() === '') {
                        $controller->invokeGenericErrorPage($request);
                    } else {
                        $controller->invokeVocabularyConcept($request);
                    }

                } elseif ($request->getPage() == 'groups') {
                    if (sizeof($parts) == 4) {
                        if ($request->getQueryParam('uri')) {
                            $request->setUri($request->getQueryParam('uri'));
                            $controller->invokeGroupContents($request);
                        } else {
                            $controller->invokeGroupIndex($request);
                        }
                    } else {
                        ($request->getQueryParam('uri')) ? $request->setUri($request->getQueryParam('uri')) : $request->setUri($parts[4]);
                        if ($request->getUri() === null) {
                            $controller->invokeGroupIndex($request);
                        } else {
                            $controller->invokeGroupContents($request);
                        }

                    }
                } else {
                    $controller->invokeGenericErrorPage($request);
                }
            } else { // language code missing, redirect to some language version
                $lang = $controller->guessLanguage($vocab);
                $pattern = '|' . preg_quote("/$vocab/") . '|';
                $location = preg_replace($pattern, "/$vocab/$lang/", $request->getServerConstant('REQUEST_URI'), 1);
                header("Location: $location");
            }
        }
    }
}
