<?php
/**
 * Copyright (c) 2012-2013 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/**
 * Importing the dependencies.
 */
require_once 'controller/Controller.php';
use \Punic\Language;

/**
 * WebController is an extension of the Controller that handles all
 * the requests originating from the view of the website.
 */
class WebController extends Controller
{
    /**
     * Provides access to the templating engine.
     * @property object $twig the twig templating engine.
     */
    public $twig;

    public $base_href;

    /**
     * Constructor for the WebController.
     * @param Model $model
     */
    public function __construct($model)
    {
        parent::__construct($model);

        // initialize Twig templates
        $tmp_dir = TEMPLATE_CACHE;

        // check if the cache pointed by config.inc exists, if not we create it.
        if (!file_exists($tmp_dir)) {
            mkdir($tmp_dir);
        }

        // specify where to look for templates and cache
        $loader = new Twig_Loader_Filesystem('view');
        // initialize Twig environment
        $this->twig = new Twig_Environment($loader, array('cache' => $tmp_dir,
            'auto_reload' => true, 'debug' => true));
        $this->twig->addExtension(new Twig_Extensions_Extension_I18n());
        //ENABLES DUMP() method for easy and fun debugging!
        $this->twig->addExtension(new Twig_Extension_Debug());
        // used for setting the base href for the relative urls
        $this->base_href = (defined('BASE_HREF')) ? BASE_HREF : $this->guessBaseHref();
        $this->twig->addGlobal("BaseHref", $this->base_href);
        // setting the service name string from the config.inc
        $this->twig->addGlobal("ServiceName", SERVICE_NAME);
        // setting the service logo location from the config.inc
        if (defined('SERVICE_LOGO')) {
            $this->twig->addGlobal("ServiceLogo", SERVICE_LOGO);
        }

        // setting the service custom css file from the config.inc
        if (defined('CUSTOM_CSS')) {
            $this->twig->addGlobal("ServiceCustomCss", CUSTOM_CSS);
        }
        // used for displaying the ui language selection as a dropdown
        if (defined('UI_LANGUAGE_DROPDOWN')) {
            $this->twig->addGlobal("LanguageDropdown", UI_LANGUAGE_DROPDOWN);
        }

        // setting the list of properties to be displayed in the search results
        $this->twig->addGlobal("PreferredProperties", array('skos:prefLabel', 'skos:narrower', 'skos:broader', 'skosmos:memberOf', 'skos:altLabel', 'skos:related'));

        // register a Twig filter for generating URLs for vocabulary resources (concepts and groups)
        $controller = $this; // for use by anonymous function below
        $urlFilter = new Twig_SimpleFilter('link_url', function ($uri, $vocab, $lang, $type = 'page', $clang = null, $term = null) use ($controller) {
            // $vocab can either be null, a vocabulary id (string) or a Vocabulary object
            if ($vocab === null) {
                // target vocabulary is unknown, best bet is to link to the plain URI
                return $uri;
            } elseif (is_string($vocab)) {
                $vocid = $vocab;
                $vocab = $controller->model->getVocabulary($vocid);
            } else {
                $vocid = $vocab->getId();
            }

            $params = array();
            if (isset($clang) && $clang !== $lang) {
                $params['clang'] = $clang;
            }

            if (isset($term)) {
                $params['q'] = $term;
            }

            // case 1: URI within vocabulary namespace: use only local name
            $localname = $vocab->getLocalName($uri);
            if ($localname !== $uri && $localname === urlencode($localname)) {
                // check that the prefix stripping worked, and there are no problematic chars in localname
                $paramstr = sizeof($params) > 0 ? '?' . http_build_query($params) : '';
                if ($type && $type !== '' && $type !== 'vocab') {
                    return "$vocid/$lang/$type/$localname" . $paramstr;
                }

                return "$vocid/$lang/$localname" . $paramstr;
            }

            // case 2: URI outside vocabulary namespace, or has problematic chars
            // pass the full URI as parameter instead
            $params['uri'] = $uri;
            return "$vocid/$lang/$type/?" . http_build_query($params);
        });
        $this->twig->addFilter($urlFilter);

        // register a Twig filter for generating strings from language codes with CLDR
        $langFilter = new Twig_SimpleFilter('lang_name', function ($langcode, $lang) use ($controller) {
            return Language::getName($langcode, $lang);
        });
        $this->twig->addFilter($langFilter);

    }

    private function guessBaseHref()
    {
        $script_name = filter_input(INPUT_SERVER, 'SCRIPT_NAME', FILTER_SANITIZE_STRING);
        $script_filename = filter_input(INPUT_SERVER, 'SCRIPT_FILENAME', FILTER_SANITIZE_STRING);
        $script_filename = realpath($script_filename); // resolve any symlinks (see #274)
        $script_filename = str_replace("\\", "/", $script_filename); // fixing windows paths with \ (see #309)
        $base_dir = __DIR__; // Absolute path to your installation, ex: /var/www/mywebsite
        $base_dir = str_replace("\\", "/", $base_dir); // fixing windows paths with \ (see #309)
        $doc_root = preg_replace("!{$script_name}$!", '', $script_filename);
        $base_url = preg_replace("!^{$doc_root}!", '', $base_dir);
        $base_url = str_replace('/controller', '/', $base_url);
        $protocol = filter_input(INPUT_SERVER, 'HTTPS', FILTER_SANITIZE_STRING) === null ? 'http' : 'https';
        $port = filter_input(INPUT_SERVER, 'SERVER_PORT', FILTER_SANITIZE_STRING);
        $disp_port = ($protocol == 'http' && $port == 80 || $protocol == 'https' && $port == 443) ? '' : ":$port";
        $domain = filter_input(INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_STRING);
        $full_url = "$protocol://{$domain}{$disp_port}{$base_url}";
        return $full_url;
    }

    /**
     * Guess the language of the user. Return a language string that is one
     * of the supported languages defined in the $LANGUAGES setting, e.g. "fi".
     * @param string $vocab_id identifier for the vocabulary eg. 'yso'.
     * @return string returns the language choice as a numeric string value
     */
    public function guessLanguage($vocab_id = null)
    {
        // 1. select language based on SKOSMOS_LANGUAGE cookie
        if (filter_input(INPUT_COOKIE, 'SKOSMOS_LANGUAGE', FILTER_SANITIZE_STRING)) {
            return filter_input(INPUT_COOKIE, 'SKOSMOS_LANGUAGE', FILTER_SANITIZE_STRING);
        }

        // 2. if vocabulary given, select based on the default language of the vocabulary
        if ($vocab_id !== null && $vocab_id !== '') {
            try {
                $vocab = $this->model->getVocabulary($vocab_id);
                return $vocab->getDefaultLanguage();
            } catch (Exception $e) {
                // vocabulary id not found, move on to the next selection method
            }
        }

        // 3. select language based on Accept-Language header
        header('Vary: Accept-Language'); // inform caches that a decision was made based on Accept header
        $this->negotiator = new \Negotiation\LanguageNegotiator();
        $langcodes = array_keys($this->languages);
        $acceptLanguage = filter_input(INPUT_SERVER, 'HTTP_ACCEPT_LANGUAGE', FILTER_SANITIZE_STRING) ? filter_input(INPUT_SERVER, 'HTTP_ACCEPT_LANGUAGE', FILTER_SANITIZE_STRING) : '';
        $bestLang = $this->negotiator->getBest($acceptLanguage, $langcodes);
        if (isset($bestLang) && in_array($bestLang, $langcodes)) {
            return $bestLang->getValue();
        }

        // show default site or prompt for language
        return $langcodes[0];
    }

    /**
     * Loads and renders the view containing all the vocabularies.
     * @param Request $request
     */
    public function invokeVocabularies($request)
    {
        // set language parameters for gettext
        $this->setLanguageProperties($request->getLang());
        // load template
        $template = $this->twig->loadTemplate('light.twig');
        // set template variables
        $categoryLabel = $this->model->getClassificationLabel($request->getLang());
        $vocabList = $this->model->getVocabularyList();
        $langList = $this->model->getLanguages($request->getLang());

        // render template
        echo $template->render(
            array(
                'vocab_list' => $vocabList,
                'category_label' => $categoryLabel,
                'languages' => $this->languages,
                'lang_list' => $langList,
                'request' => $request,
            ));
    }

    /**
     * Invokes the concept page of a single concept in a specific vocabulary.
     */
    public function invokeVocabularyConcept($request)
    {
        $lang = $request->getLang();
        $this->setLanguageProperties($lang);
        $template = $this->twig->loadTemplate('concept-info.twig');
        $vocab = $request->getVocab();

        $langcodes = $vocab->getShowLangCodes();
        $uri = $vocab->getConceptURI($request->getUri()); // make sure it's a full URI

        $results = $vocab->getConceptInfo($uri, $request->getContentLang());
        $crumbs = $vocab->getBreadCrumbs($request->getContentLang(), $uri);
        echo $template->render(array(
            'search_results' => $results,
            'vocab' => $vocab,
            'languages' => $this->languages,
            'explicit_langcodes' => $langcodes,
            'bread_crumbs' => $crumbs['breadcrumbs'],
            'combined' => $crumbs['combined'],
            'request' => $request)
        );
    }

    /**
     * Invokes the feedback page with information of the users current vocabulary.
     */
    public function invokeFeedbackForm($request)
    {
        $template = $this->twig->loadTemplate('feedback.twig');
        $this->setLanguageProperties($request->getLang());
        $vocabList = $this->model->getVocabularyList(false);
        $vocab = $request->getVocab();

        $feedback_sent = false;
        $feedback_msg = null;
        if (filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING)) {
            $feedback_sent = true;
            $feedback_msg = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
        }
        $feedback_name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $feedback_email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_STRING);
        $feedback_vocab = filter_input(INPUT_POST, 'vocab', FILTER_SANITIZE_STRING);
        $feedback_vocab_email = ($vocab !== null) ? $vocab->getFeedbackRecipient() : null;

        // if the hidden field has been set a value we have found a spam bot
        // and we do not actually send the message.
        if ($feedback_sent && filter_input(INPUT_POST, 'trap', FILTER_SANITIZE_STRING) === '') {
            $this->sendFeedback($feedback_msg, $feedback_name, $feedback_email, $feedback_vocab, $feedback_vocab_email);
        }

        echo $template->render(
            array(
                'languages' => $this->languages,
                'vocab' => $vocab,
                'vocabList' => $vocabList,
                'feedback_sent' => $feedback_sent,
                'request' => $request,
            ));
    }

    /**
     * Sends the user entered message through the php's mailer.
     * @param string $message only required parameter is the actual message.
     * @param string $fromName senders own name.
     * @param string $fromEmail senders email adress.
     * @param string $fromVocab which vocabulary is the feedback related to.
     */
    public function sendFeedback($message, $fromName = null, $fromEmail = null, $fromVocab = null, $toMail = null)
    {
        $toAddress = ($toMail) ? $toMail : FEEDBACK_ADDRESS;
        if ($fromVocab !== null) {
            $message = 'Feedback from vocab: ' . strtoupper($fromVocab) . "<br />" . $message;
        }

        $subject = SERVICE_NAME . " feedback";
        $headers = "MIME-Version: 1.0″ . '\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        if ($toMail) {
            $headers .= "Cc: " . FEEDBACK_ADDRESS . "\r\n";
        }

        $headers .= "From: $fromName <$fromEmail>" . "\r\n" . 'X-Mailer: PHP/' . phpversion();
        $envelopeSender = FEEDBACK_ENVELOPE_SENDER;
        $params = empty($envelopeSender) ? '' : "-f $envelopeSender";

        // adding some information about the user for debugging purposes.
        $agent = (filter_input(INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_STRING)) ? filter_input(INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_STRING) : '';
        $referer = (filter_input(INPUT_SERVER, 'HTTP_REFERER', FILTER_SANITIZE_STRING)) ? filter_input(INPUT_SERVER, 'HTTP_REFERER', FILTER_SANITIZE_STRING) : '';
        $ipAddress = (filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING)) ? filter_input(INPUT_SERVER, 'HTTP_REFERER', FILTER_SANITIZE_STRING) : '';
        $timestamp = date(DATE_RFC2822);

        $message = $message . "<br /><br /> Debugging information:"
            . "<br />Timestamp: " . $timestamp
            . "<br />User agent: " . $agent
            . "<br />IP address: " . $ipAddress
            . "<br />Referer: " . $referer;

        try {
            mail($toAddress, $subject, $message, $headers, $params);
        } catch (Exception $e) {
            header("HTTP/1.0 404 Not Found");
            $template = $this->twig->loadTemplate('error-page.twig');
            if (LOG_CAUGHT_EXCEPTIONS) {
                error_log('Caught exception: ' . $e->getMessage());
            }

            echo $template->render(
                array(
                    'languages' => $this->languages,
                ));

            return;
        }
    }

    /**
     * Invokes the about page for the Skosmos service.
     */
    public function invokeAboutPage($request)
    {
        $template = $this->twig->loadTemplate('about.twig');
        $this->setLanguageProperties($request->getLang());
        $url = $request->getServerConstant('HTTP_HOST');
        $version = $this->model->getVersion();

        echo $template->render(
            array(
                'languages' => $this->languages,
                'version' => $version,
                'server_instance' => $url,
                'request' => $request,
            ));
    }

    /**
     * Invokes the search for concepts in all the availible ontologies.
     */
    public function invokeGlobalSearch($request)
    {
        $lang = $request->getLang();
        $template = $this->twig->loadTemplate('vocab-search-listing.twig');
        $this->setLanguageProperties($lang);

        $term = $request->getQueryParam('q');
        $content_lang = $request->getContentLang();
        $search_lang = $request->getQueryParam('anylang') ? '' : $content_lang;
        $type = $request->getQueryParam('type');
        $group = $request->getQueryParam('group');
        $parent = $request->getQueryParam('parent');
        $offset = ($request->getQueryParam('offset') && is_numeric($request->getQueryParam('offset')) && $request->getQueryParam('offset') >= 0) ? $request->getQueryParam('offset') : 0;
        if ($offset > 0) {
            $rest = 1;
        } else {
            $rest = null;
        }
        $term = trim($term); // surrounding whitespace is not considered significant
        $sterm = strpos($term, "*") === false ? $term . "*" : $term; // default to prefix search

        $vocabs = $request->getQueryParam('vocabs'); # optional
        // convert to vocids array to support multi-vocabulary search
        $vocids = ($vocabs !== null && $vocabs !== '') ? explode(' ', $vocabs) : null;

        $count_and_results = $this->model->searchConceptsAndInfo($sterm, $vocids, $content_lang, $search_lang, $offset, 20, $type, $parent, $group);
        $counts = $count_and_results['count'];
        $search_results = $count_and_results['results'];
        $vocabList = $this->model->getVocabularyList();
        $langList = $this->model->getLanguages($lang);

        echo $template->render(
            array(
                'search_count' => $counts,
                'languages' => $this->languages,
                'search_results' => $search_results,
                'term' => $term,
                'rest' => $rest,
                'global_search' => true,
                'lang_list' => $langList,
                'vocabs' => $vocabs,
                'vocab_list' => $vocabList,
                'request' => $request,
            ));
    }

    /**
     * Invokes the search for a single vocabs concepts.
     */
    public function invokeVocabularySearch($request)
    {
        $lang = $request->getLang();
        $template = $this->twig->loadTemplate('vocab-search-listing.twig');
        $this->setLanguageProperties($lang);
        $vocab = $request->getVocab();
        try {
            $vocab_types = $this->model->getTypes($request->getVocabid(), $lang);
        } catch (Exception $e) {
            header("HTTP/1.0 404 Not Found");
            if (LOG_CAUGHT_EXCEPTIONS) {
                error_log('Caught exception: ' . $e->getMessage());
            }

            echo $template->render(
                array(
                    'languages' => $this->languages,
                ));

            return;
        }
        $term = $request->getQueryParam('q');
        $content_lang = $request->getContentLang();
        $groups = $vocab->listConceptGroups($content_lang);
        $search_lang = $request->getQueryParam('anylang') ? '' : $content_lang;
        $type = $request->getQueryParam('type') !== '' ? $request->getQueryParam('type') : null;
        if ($type && strpos($type, '+')) {
            $type = explode('+', $type);
        } else if ($type && !is_array($type)) {
            // if only one type param given place it into an array regardless
            $type = array($type);
        }

        $group = $request->getQueryParam('group') !== '' ? $request->getQueryParam('group') : null;
        $parent = $request->getQueryParam('parent') !== '' ? $request->getQueryParam('parent') : null;
        $offset = ($request->getQueryParam('offset') && is_numeric($request->getQueryParam('offset')) && $request->getQueryParam('offset') >= 0) ? $request->getQueryParam('offset') : 0;
        $langcodes = $vocab->getShowLangCodes();
        if ($offset > 0) {
            $rest = 1;
            $template = $this->twig->loadTemplate('vocab-search-listing.twig');
        } else {
            $rest = null;
        }

        $term = trim($term); // surrounding whitespace is not considered significant
        $sterm = strpos($term, "*") === false ? $term . "*" : $term; // default to prefix search
        try {
            $count_and_results = $this->model->searchConceptsAndInfo($sterm, $request->getVocabid(), $content_lang, $search_lang, $offset, 20, $type, $parent, $group);
            $counts = $count_and_results['count'];
            $search_results = $count_and_results['results'];
        } catch (Exception $e) {
            header("HTTP/1.0 404 Not Found");
            if (LOG_CAUGHT_EXCEPTIONS) {
                error_log('Caught exception: ' . $e->getMessage());
            }

            echo $template->render(
                array(
                    'languages' => $this->languages,
                    'vocab' => $vocab,
                    'term' => $term,
                    'rest' => $rest,
                ));
            return;
        }
        echo $template->render(
            array(
                'languages' => $this->languages,
                'vocab' => $vocab,
                'search_results' => $search_results,
                'search_count' => $counts,
                'term' => $term,
                'rest' => $rest,
                'limit_parent' => $parent,
                'limit_type' => $type,
                'limit_group' => $group,
                'group_index' => $groups,
                'types' => $vocab_types,
                'explicit_langcodes' => $langcodes,
                'request' => $request,
            ));
    }

    /**
     * Invokes the alphabetical listing for a specific vocabulary.
     */
    public function invokeAlphabeticalIndex($request)
    {
        $lang = $request->getLang();
        $this->setLanguageProperties($lang);
        $template = $this->twig->loadTemplate('alphabetical-index.twig');
        $vocab = $request->getVocab();

        $offset = ($request->getQueryParam('offset') && is_numeric($request->getQueryParam('offset')) && $request->getQueryParam('offset') >= 0) ? $request->getQueryParam('offset') : 0;
        if ($request->getQueryParam('limit')) {
            $count = $request->getQueryParam('limit');
        } else {
            $count = ($offset > 0) ? null : 250;
        }

        $content_lang = $request->getContentLang();

        $all_at_once = $vocab->getAlphabeticalFull();
        if (!$all_at_once) {
            $search_results = $vocab->searchConceptsAlphabetical($request->getLetter(), $count, $offset, $content_lang);
            $letters = $vocab->getAlphabet($content_lang);
        } else {
            $search_results = $vocab->searchConceptsAlphabetical('*', null, null, $content_lang);
            $letters = null;
        }

        $request->setContentLang($content_lang);

        echo $template->render(
            array(
                'languages' => $this->languages,
                'vocab' => $vocab,
                'alpha_results' => $search_results,
                'letters' => $letters,
                'all_letters' => $all_at_once,
                'request' => $request,
            ));
    }

    /**
     * Invokes the vocabulary group index page template.
     * @param boolean $stats set to true to get vocabulary statistics visible.
     */
    public function invokeGroupIndex($request, $stats = false)
    {
        $lang = $request->getLang();
        $this->setLanguageProperties($lang);
        $template = $this->twig->loadTemplate('group-index.twig');
        $vocab = $request->getVocab();

        echo $template->render(
            array(
                'languages' => $this->languages,
                'stats' => $stats,
                'vocab' => $vocab,
                'request' => $request,
            ));
    }

    /**
     * Invokes the vocabulary group contents page template.
     */
    public function invokeGroupContents($request)
    {
        $lang = $request->getLang();
        $this->setLanguageProperties($lang);
        $template = $this->twig->loadTemplate('group-contents.twig');
        $vocab = $request->getVocab();

        $uri = $vocab->getConceptURI($request->getUri()); // make sure it's a full URI
        $results = $vocab->getConceptInfo($uri, $request->getContentLang());

        echo $template->render(
            array(
                'languages' => $this->languages,
                'vocab' => $vocab,
                'search_results' => $results,
                'request' => $request,
            ));
    }

    /**
     * Loads and renders the view containing a specific vocabulary.
     */
    public function invokeVocabularyHome($request)
    {
        $lang = $request->getLang();
        // set language parameters for gettext
        $this->setLanguageProperties($lang);
        $vocab = $request->getVocab();

        $defaultView = $vocab->getDefaultSidebarView();
        // load template
        if ($defaultView === 'groups') {
            $this->invokeGroupIndex($request, true);
            return;
        }

        $template = $this->twig->loadTemplate('vocab.twig');

        echo $template->render(
            array(
                'languages' => $this->languages,
                'vocab' => $vocab,
                'search_letter' => 'A',
                'active_tab' => $defaultView,
                'request' => $request,
            ));
    }

    /**
     * Invokes a very generic errorpage.
     */
    public function invokeGenericErrorPage($request)
    {
        $this->setLanguageProperties($request->getLang());
        header("HTTP/1.0 404 Not Found");
        $template = $this->twig->loadTemplate('error-page.twig');
        echo $template->render(
            array(
                'languages' => $this->languages,
                'request' => $request,
                'requested_page' => filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING),
            ));
    }

    /**
     * Loads and renders the view containing a list of recent changes in the vocabulary.
     * @param Request $request
     */
    public function invokeChangeList($request)
    {
        // set language parameters for gettext
        $this->setLanguageProperties($request->getLang());
        $vocab = $request->getVocab();
        $offset = ($request->getQueryParam('offset') && is_numeric($request->getQueryParam('offset')) && $request->getQueryParam('offset') >= 0) ? $request->getQueryParam('offset') : 0;
        // load template
        $template = $this->twig->loadTemplate('changes.twig');

        // render template
        echo $template->render(
            array(
                'vocab' => $vocab,
                'languages' => $this->languages,
                'request' => $request,
                'changeList' => $vocab->getChangeList($request->getContentLang(), $offset)
            ));
    }

}
