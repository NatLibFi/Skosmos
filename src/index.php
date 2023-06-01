<?php

/**
 * Use Composer autoloader to automatically load library classes.
 */
try {
    if (!file_exists('../vendor/autoload.php')) {
        throw new Exception('Dependencies managed by Composer missing. Please run "php composer.phar install".');
    }
    require_once '../vendor/autoload.php';
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    return;
}

$config = new GlobalConfig();
$model = new Model($config);
$controller = new WebController($model);
$request = new Request($model);

// PATH_INFO, for example "/ysa/fi"
$path = $request->getServerConstant('PATH_INFO') ? $request->getServerConstant('PATH_INFO') : '';
$parts = explode('/', $path);


if (sizeof($parts) <= 2) {
    // if language code missing, redirect to guessed language
    // in any case, redirect to <lang>/
    $lang = sizeof($parts) == 2 && $parts[1] !== '' ? $parts[1] : $controller->guessLanguage($request);
    header("Location: " . $lang . "/");
} else {
    if (array_key_exists($parts[1], $config->getLanguages())) { // global pages
        $request->setLang($parts[1]);
        $controller->setLocale($parts[1]);
        $content_lang = $request->getQueryParam('clang');
        $request->setContentLang($content_lang);
        ($parts[2] == 'about' || $parts[2] == 'feedback' || $parts[2] == 'search') ? $request->setPage($parts[2]) : $request->setPage('');
        if ($request->getPage() == '') {
            $controller->invokeLandingPage($request);
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
        } catch (Exception | ValueError $e) {
            $request->setLang($controller->guessLanguage($request));
            $controller->invokeGenericErrorPage($request);
            return;
        }
        if (sizeof($parts) == 3) { // language code missing
            $lang = $controller->guessLanguage($request);
            $newurl = $controller->getBaseHref() . $vocab . "/" . $lang . "/";
            header("Location: " . $newurl);
        } else {
            if (array_key_exists($parts[2], $config->getLanguages())) {
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
                    if ((sizeof($parts) == 5) && $parts[4] !== '') {
                        // letter given
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
                    $controller->invokeGroupIndex($request);
                } elseif ($request->getPage() == 'changes') {
                    $controller->invokeChangeList($request, 'dc:modified');
                } elseif ($request->getPage() == 'new') {
                    $controller->invokeChangeList($request);
                } else {
                    $controller->invokeGenericErrorPage($request);
                }
            } else { // language code missing, redirect to some language version
                $lang = $controller->guessLanguage($request, $vocab);
                $newurl = $controller->getBaseHref() . $vocab . "/" . $lang . "/" . implode('/', array_slice($parts, 2));
                $qs = $request->getServerConstant('QUERY_STRING');
                if ($qs) {
                    $newurl .= "?" . $qs;
                }
                header("Location: $newurl");
            }
        }
    }
}
