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

  /**
  * Used for relative url building inside the templates.
  * @property string $path_fix a string for making relative urls eg. '../../'.
  */
  public $path_fix;

  /**
   * Used for passing url parameters to the templates.
   * @property string $parts contains the url parameters.
   */
  public $parts;

  /**
   * Passing the whole request uri to the templates.
   * @property string $request_uri contains the whole request uri.
   */
  public $request_uri;

  public $base_href;
  public $request;

  /**
   * Constructor for the WebController can be given the path_fix as a parameter.
   * @param string $path_fix eg. '../../'
   */
  public function __construct($path_fix)
  {
    parent::__construct();

    // used for making proper hrefs for the language selection
    $this->request_uri = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $this->parts = rtrim($_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'], 'php.xedni');
    $this->path_fix = $path_fix;

    // initialize Twig templates
    $tmp_dir = TEMPLATE_CACHE;

    // check if the cache pointed by config.inc exists, if not we create it.
    if (!file_exists($tmp_dir)) mkdir($tmp_dir);

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
    if (defined('SERVICE_LOGO'))
      $this->twig->addGlobal("ServiceLogo", SERVICE_LOGO);
    // setting the service custom css file from the config.inc
    if (defined('CUSTOM_CSS'))
      $this->twig->addGlobal("ServiceCustomCss", CUSTOM_CSS);
    // set only if the checkbox in the language selection dropdown should be ticked
    if (isset($_GET['anylang']))
      $this->twig->addGlobal("AnyLang", true);
    // setting the list of properties to be displayed in the search results
    $this->twig->addGlobal("PreferredProperties", array('skos:prefLabel', 'skos:narrower', 'skos:broader', 'skosmos:memberOf', 'skos:altLabel', 'skos:related'));
    
    $this->request = new Request();

    // register a Twig filter for generating URLs for vocabulary resources (concepts and groups)
    $controller = $this; // for use by anonymous function below
    $urlFilter = new Twig_SimpleFilter('link_url', function ($uri, $vocab, $lang, $type='page', $clang=null, $term=null) use ($controller) {
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
      if (isset($clang) && $clang !== $lang)
        $params['clang'] = $clang;
      
      if (isset($term))
        $params['q'] = $term;
      
      // case 1: URI within vocabulary namespace: use only local name
      $localname = $vocab->getLocalName($uri);
      if ($localname !== $uri && $localname === urlencode($localname)) {
        // check that the prefix stripping worked, and there are no problematic chars in localname
        $paramstr = sizeof($params) > 0 ? '?' . http_build_query($params) : '';
        if ($type && $type !== '')
          return $controller->base_href . "$vocid/$lang/$type/$localname" . $paramstr;
        return $controller->base_href . "$vocid/$lang/$localname" . $paramstr;
      }

      // case 2: URI outside vocabulary namespace, or has problematic chars
      // pass the full URI as parameter instead
      $params['uri'] = $uri;
      return $controller->base_href . "$vocid/$lang/$type/?" . http_build_query($params);
    });
    $this->twig->addFilter($urlFilter);

    // register a Twig filter for generating strings from language codes with CLDR 
    $langFilter = new Twig_SimpleFilter('lang_name', function ($langcode, $lang) use ($controller) {
      return Language::getName($langcode, $lang); 
    });
    $this->twig->addFilter($langFilter);

    $tplDir = 'view';

    // iterate over all your templates
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tplDir),
            RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
      // force compilation
      $path = explode('.', $file);
      $ext = end($path);
      if ($ext == 'twig')
        $this->twig->loadTemplate(str_replace($tplDir . '/', '', $file));
    }
  }

  private function guessBaseHref()
  {
    $base_dir  = __DIR__; // Absolute path to your installation, ex: /var/www/mywebsite
    $doc_root  = preg_replace("!{$_SERVER['SCRIPT_NAME']}$!", '', $_SERVER['SCRIPT_FILENAME']); # ex: /var/www
    $base_url  = preg_replace("!^{$doc_root}!", '', $base_dir); # ex: '' or '/mywebsite'
    $base_url = str_replace('/controller','/',$base_url);
    $protocol  = empty($_SERVER['HTTPS']) ? 'http' : 'https';
    $port      = $_SERVER['SERVER_PORT'];
    $disp_port = ($protocol == 'http' && $port == 80 || $protocol == 'https' && $port == 443) ? '' : ":$port";
    $domain    = $_SERVER['SERVER_NAME'];
    $full_url  = "$protocol://{$domain}{$disp_port}{$base_url}"; # Ex: 'http://example.com', 'https://example.com/mywebsite', etc.
    return $full_url;
  }

  /**
   * Guess the language of the user. Return a language string that is one
   * of the supported languages defined in the $LANGUAGES setting, e.g. "fi".
   * @param string $vocab_id identifier for the vocabulary eg. 'yso'.
   * @return string returns the language choice as a numeric string value
   */
  public function guessLanguage($vocab_id=null)
  {
    // 1. select language based on SKOSMOS_LANGUAGE cookie
    if (isset($_COOKIE['SKOSMOS_LANGUAGE']))
      return $_COOKIE['SKOSMOS_LANGUAGE'];

    // 2. if vocabulary given, select based on the default language of the vocabulary
    if ($vocab_id) {
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
    $acceptLanguage = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
    $bestLang = $this->negotiator->getBest($acceptLanguage, $langcodes);
    if (isset($bestLang) && in_array($bestLang, $langcodes))
      return $bestLang->getValue();

    // show default site or prompt for language
    return $langcodes[0];
  }

  /**
   * Loads and renders the view containing all the vocabularies.
   * @param string $lang language parameter eg. 'fi' for Finnish.
   */
  public function invokeVocabularies($lang)
  {
    $content_lang = (isset($_GET['clang'])) ? $_GET['clang'] : $lang;
    if ($content_lang !== $lang) $this->twig->addGlobal("ContentLanguage", $content_lang);
    // set language parameters for gettext
    $this->setLanguageProperties($lang);
    // load template
    $template = $this->twig->loadTemplate('light.twig');
    // set template variables
    $requestUri = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $categoryLabel = $this->model->getClassificationLabel($lang);
    $vocabList = $this->model->getVocabularyList();
    $langList = $this->model->getLanguages($lang);
    
    $this->request->setContentLang($content_lang);
    $this->request->setLang($lang);

    // render template
    echo $template
            ->render(
                    array('vocab_list' => $vocabList, 'category_label' => $categoryLabel,
                        'path_fix' => $this->path_fix, 'languages' => $this->languages, 'front_page' => True,
                        'lang' => $lang, 'parts' => $this->parts, 'request_uri' => $this->request_uri, 
                        'lang_list' => $langList, 'request' => $this->request));
  }

  /**
   * Invokes the concept page of a single concept in a specific vocabulary.
   * @param string $vocab_id contains the name of the vocabulary in question.
   * @param string $lang language parameter eg. 'fi' for Finnish.
   * @param string $uri localname or uri of concept.
   */
  public function invokeVocabularyConcept($vocab_id, $lang, $uri)
  {
    $this->setLanguageProperties($lang);
    $template = $this->twig->loadTemplate('concept-info.twig');
    try {
      $vocab = $this->model->getVocabulary($vocab_id);
    } catch (Exception $e) {
      header("HTTP/1.0 404 Not Found");
      if (LOG_CAUGHT_EXCEPTIONS)
        error_log('Caught exception: ' . $e->getMessage());
      echo $template->render(array('path_fix' => $this->path_fix, 'languages' => $this->languages,
          'lang' => $lang, 'vocab_id' => $vocab_id, 'request_uri' => $this->request_uri));
      exit();
    }

    $content_lang = (isset($_GET['clang'])) ? $_GET['clang'] : $lang;
    $newlang = $this->verifyVocabularyLanguage($content_lang, $vocab);
    if ($newlang !== null)
      $content_lang = $newlang;

    if ($content_lang !== $lang) $this->twig->addGlobal("ContentLanguage", $content_lang);
    $langcodes = $vocab->getShowLangCodes();
    $vocab = $this->model->getVocabulary($vocab_id);
    
    $full_uri = $vocab->getConceptURI($uri); // make sure it's a full URI
    // if rendering a page with the uri parameter the param needs to be passed for the template
    $uri_param =  ($full_uri === $uri) ? 'uri=' . $full_uri : ''; 
    $uri = $full_uri;
    
    $this->request->setContentLang($content_lang);
    $this->request->setLang($lang);
    $this->request->setVocabid($vocab->getId());
    $this->request->setPage('page');
    $this->request->setUri($uri);

    $results = $vocab->getConceptInfo($uri, $content_lang);
    $crumbs = $vocab->getBreadCrumbs($content_lang, $uri);
    echo $template->render(Array(
      'search_results' => $results,
      'vocab' => $vocab,
      'vocab_id' => $vocab_id,
      'path_fix' => $this->path_fix,
      'languages' => $this->languages,
      'parts' => $this->parts,
      'lang' => $lang,
      'explicit_langcodes' => $langcodes,
      'request_uri' => $this->request_uri,
      'bread_crumbs' => $crumbs['breadcrumbs'],
      'combined' => $crumbs['combined'],
      'request' => $this->request)
    );
  }

  /**
   * Invokes the feedback page with information of the users current vocabulary.
   * @param string $vocab_id used for the default setting of the dropdown menu.
   * @param string $lang language parameter eg. 'fi' for Finnish.
   */
  public function invokeFeedbackForm($lang, $vocab_id = null)
  {
    $template = $this->twig->loadTemplate('feedback.twig');
    $this->setLanguageProperties($lang);
    $vocabList = $this->model->getVocabularyList(false);
    try {
      $vocab = (isset($vocab_id)) ? $this->model->getVocabulary($vocab_id) : null;
    } catch (Exception $e) {
      header("HTTP/1.0 404 Not Found");
      if (LOG_CAUGHT_EXCEPTIONS)
        error_log('Caught exception: ' . $e->getMessage());
      echo $template->render(array('path_fix' => $this->path_fix,
          'languages' => $this->languages,
          'lang' => $lang, 'vocab_id' => $vocab_id,
          'vocabList' => $vocabList));

      return;
    }
    $content_lang = (isset($_GET['clang'])) ? $_GET['clang'] : $lang;
    if ($content_lang !== $lang) $this->twig->addGlobal("ContentLanguage", $content_lang);

    $feedback_sent = False;
    $feedback_msg = null;
    if (isset($_POST['message'])) {
      $feedback_sent = True;
      $feedback_msg = $_POST['message'];
    }
    $feedback_name = (isset($_POST['name'])) ? $_POST['name'] : null;
    $feedback_email = (isset($_POST['email'])) ? $_POST['email'] : null;
    $feedback_vocab = (isset($_POST['vocab'])) ? $_POST['vocab'] : null;
    $feedback_vocab_email = ($vocab !== null) ? $vocab->getFeedbackRecipient() : null; 

    // if the hidden field has been set a value we have found a spam bot 
    // and we do not actually send the message.
    if ($feedback_sent && $_POST['trap'] === '') {
      $this->sendFeedback($feedback_msg, $feedback_name, $feedback_email, $feedback_vocab, $feedback_vocab_email);
    }

    $this->request->setContentLang($content_lang);
    $this->request->setLang($lang);
    $this->request->setPage('feedback');
    if ($vocab_id !== null)
      $this->request->setVocabid($vocab_id);

    echo $template
            ->render(
                    array('path_fix' => $this->path_fix,
                        'languages' => $this->languages,
                        'vocab' => $vocab,
                        'vocabList' => $vocabList,
                        'feedback_sent' => $feedback_sent,
                        'parts' => $this->parts,
                        'request_uri' => $this->request_uri,
                        'request' => $this->request
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
    $to = ($toMail) ? $toMail : FEEDBACK_ADDRESS;
    if ($fromVocab !== null)
      $message = 'Feedback from vocab: ' . strtoupper($fromVocab) . "<br />" . $message;
    $subject = SERVICE_NAME . " feedback";
    $headers = "MIME-Version: 1.0″ . '\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    if ($toMail)
      $headers .= "Cc: " . FEEDBACK_ADDRESS . "\r\n";
    $headers .= "From: $fromName <$fromEmail>" . "\r\n" . 'X-Mailer: PHP/' . phpversion();
    $envelopeSender = FEEDBACK_ENVELOPE_SENDER;
    $params = empty($envelopeSender) ? '' : "-f $envelopeSender";

    // adding some information about the user for debugging purposes.
    $agent = (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $referer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';
    $ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['HTTP_REFERER'] : '';
    $timestamp = date(DATE_RFC2822);

    $message = $message . "<br /><br /> Debugging information:" 
      . "<br />Timestamp: " . $timestamp 
      . "<br />User agent: " . $agent 
      . "<br />IP address: " . $ip 
      . "<br />Referer: " . $referer;
    
    mail($to, $subject, $message, $headers, $params) or die("Failure");
  }

  /**
   * Invokes the about page for the Skosmos service.
   * @param string $lang language parameter eg. 'fi' for Finnish.
   */
  public function invokeAboutPage($lang = 'en')
  {
    $template = $this->twig->loadTemplate('about.twig');
    $this->setLanguageProperties($lang);
    $vocab_id = 'About';
    $url = $_SERVER['HTTP_HOST'];
    $version = $this->model->getVersion();
    
    $this->request->setLang($lang);
    $this->request->setPage('feedback');

    echo $template
      ->render(array('path_fix' => $this->path_fix, 
                    'languages' => $this->languages,
                    'vocab_id' => $vocab_id, 
                    'version' => $version,
                    'server_instance' => $url, 
                    'request_uri' => $this->request_uri, 
                    'request' => $this->request));
  }

  /**
   * Invokes the search for concepts in all the availible ontologies.
   * @param string $vocab_id contains the name of the vocabulary in question.
   * @param string $lang language parameter eg. 'fi' for Finnish.
   */
  public function invokeGlobalSearch($lang)
  {
    $template = $this->twig->loadTemplate('vocab-search-listing.twig');
    $this->setLanguageProperties($lang);

    $term = htmlspecialchars(isset($_GET['q']) ? $_GET['q'] : "");
    $content_lang = (isset($_GET['clang'])) ? $_GET['clang'] : $lang;
    $search_lang = (isset($_GET['anylang'])) ? '' : $content_lang;
    $type = (isset($_GET['type'])) ? $_GET['type'] : null;
    $group = (isset($_GET['group'])) ? $_GET['group'] : null;
    $parent = (isset($_GET['parent'])) ? $_GET['parent'] : null;
    $offset = (isset($_GET['offset']) && is_numeric($_GET['offset']) && $_GET['offset'] >= 0) ? $_GET['offset'] : 0;
    if ($offset > 0) {
      $rest = 1;
    } else {
      $rest = null;
    }
    $term = trim($term); // surrounding whitespace is not considered significant
    $sterm = strpos($term, "*") === FALSE ? $term . "*" : $term; // default to prefix search

    $vocabs = !empty($_GET['vocabs']) ? $_GET['vocabs'] : null; # optional
    // convert to vocids array to support multi-vocabulary search
    $vocids = $vocabs !== null ? explode(' ', $vocabs) : null;
    
    $content_lang = (isset($_GET['clang'])) ? $_GET['clang'] : $lang;
    if ($content_lang !== $lang) $this->twig->addGlobal("ContentLanguage", $content_lang);

    $count_and_results = $this->model->searchConceptsAndInfo($sterm, $vocids, $content_lang, $search_lang, $offset, 20, $type, $parent, $group);
    $counts = $count_and_results['count'];
    $search_results = $count_and_results['results'];
    $uri_parts = $_SERVER['REQUEST_URI'];
    $vocabList = $this->model->getVocabularyList();
    $langList = $this->model->getLanguages($lang);
    
    $this->request->setContentLang($content_lang);
    $this->request->setLang($lang);
    $this->request->setPage('search');

    echo $template->render(
            array('path_fix' => $this->path_fix,
                'search_count' => $counts,
                'languages' => $this->languages,
                'lang' => $lang,
                'search_results' => $search_results,
                'term' => $term,
                'rest' => $rest, 'parts' => $this->parts, 'global_search' => True, 'uri_parts' => $uri_parts,
                'request_uri' => $this->request_uri,
                'lang_list' => $langList,
                'vocabs' => $vocabs,
                'vocab_list' => $vocabList,
                'request' => $this->request

    ));
  }

  /**
   * Invokes the search for a single vocabs concepts.
   * @param string $vocab_id contains the name of the vocabulary in question.
   * @param string $lang language parameter eg. 'fi' for Finnish.
   */
  public function invokeVocabularySearch($vocab_id, $lang)
  {
    $template = $this->twig->loadTemplate('vocab-search-listing.twig');
    $this->setLanguageProperties($lang);
    try {
      $vocab = $this->model->getVocabulary($vocab_id);
      $vocab_types = $this->model->getTypes($vocab_id, $lang);
    } catch (Exception $e) {
      header("HTTP/1.0 404 Not Found");
      if (LOG_CAUGHT_EXCEPTIONS)
        error_log('Caught exception: ' . $e->getMessage());
      echo $template->render(array('path_fix' => $this->path_fix,
          'languages' => $this->languages,
          'lang' => $lang, 'vocab_id' => $vocab_id));

      return;
    }
    $groups = $vocab->listConceptGroups();
    $term = urldecode(isset($_GET['q']) ? $_GET['q'] : "");
    $content_lang = (isset($_GET['clang']) && $_GET['clang'] !== '') ? $_GET['clang'] : $lang;
    $search_lang = (isset($_GET['anylang'])) ? '' : $content_lang;
    if ($content_lang !== $lang) $this->twig->addGlobal("ContentLanguage", $content_lang);
    $type = (isset($_GET['type'])) ? $_GET['type'] : null;
    if ($type && strpos($type, '+'))
      $type = explode('+',$type);
    else if ($type && !is_array($type)) // if only one type param given place it into an array regardless
      $type = array($type);
    $group = (isset($_GET['group'])) ? $_GET['group'] : null;
    $parent = (isset($_GET['parent'])) ? $_GET['parent'] : null;
    $offset = (isset($_GET['offset']) && is_numeric($_GET['offset']) && $_GET['offset'] >= 0) ? $_GET['offset'] : 0;
    $langcodes = $vocab->getShowLangCodes();
    if ($offset > 0) {
      $rest = 1;
      $template = $this->twig->loadTemplate('vocab-search-listing.twig');
    } else {
      $rest = null;
    }
    
    $this->request->setContentLang($content_lang);
    $this->request->setLang($lang);
    $this->request->setVocabid($vocab->getId());
    $this->request->setPage('search');

    $term = trim($term); // surrounding whitespace is not considered significant
    $sterm = strpos($term, "*") === FALSE ? $term . "*" : $term; // default to prefix search
    try {
      $count_and_results = $this->model->searchConceptsAndInfo($sterm, $vocab_id, $content_lang, $search_lang, $offset, 20, $type, $parent, $group);
      $counts = $count_and_results['count'];
      $search_results = $count_and_results['results'];
    } catch (Exception $e) {
      header("HTTP/1.0 404 Not Found");
      if (LOG_CAUGHT_EXCEPTIONS)
        error_log('Caught exception: ' . $e->getMessage());
      echo $template->render(
            array('path_fix' => $this->path_fix,
                'languages' => $this->languages,
                'lang' => $lang,
                'vocab_id' => $vocab_id,
                'vocab' => $vocab,
                'term' => $term,
                'rest' => $rest, 'parts' => $this->parts));
      return;
    }
    $uri_parts = $_SERVER['REQUEST_URI'];
    echo $template->render(
            array('path_fix' => $this->path_fix,
                'languages' => $this->languages,
                'vocab_id' => $vocab_id,
                'vocab' => $vocab,
                'parts' => $this->parts,
                'search_results' => $search_results,
                'search_count' => $counts,
                'term' => $term,
                'rest' => $rest, 'parts' => $this->parts, 
                'uri_parts' => $uri_parts,
                'limit_parent' => $parent,
                'limit_type' => $type,
                'limit_group' => $group,
                'group_index' => $groups,
                'types' => $vocab_types,
                'explicit_langcodes' => $langcodes,
                'request_uri' => $this->request_uri,
                'request' => $this->request

    ));
  }

  /**
   * Invokes the alphabetical listing for a specific vocabulary.
   * @param string $vocab_id contains the name of the vocabulary in question.
   * @param string $lang language parameter eg. 'fi' for Finnish.
   * @param string $letter eg. 'R'.
   */
  public function invokeAlphabeticalIndex($vocab_id, $lang, $letter = 'A')
  {
    $this->setLanguageProperties($lang);
    $template = $this->twig->loadTemplate('alphabetical-index.twig');

    try {
      $vocab = $this->model->getVocabulary($vocab_id);
    } catch (Exception $e) {
      header("HTTP/1.0 404 Not Found");
      $template = $this->twig->loadTemplate('concept-info.twig');
      if (LOG_CAUGHT_EXCEPTIONS)
        error_log('Caught exception: ' . $e->getMessage());
      echo $template->render(array('path_fix' => $this->path_fix,
          'languages' => $this->languages,
          'lang' => $lang, 'vocab_id' => $vocab_id));

      return;
    }
    
    $offset = (isset($_GET['offset']) && is_numeric($_GET['offset']) && $_GET['offset'] >= 0) ? $_GET['offset'] : 0;
    if (isset($_GET['limit'])) {
      $count = $_GET['limit'];
    } else { 
      $count = ($offset > 0 || !isset($_GET['base_path'])) ? null : 250;
    }
    
    $content_lang = (isset($_GET['clang'])) ? $_GET['clang'] : $lang;
    $lang_support = true;
    $newlang = $this->verifyVocabularyLanguage($content_lang, $vocab);
    if ($newlang !== null) {
      $content_lang = $newlang;
      $lang_support = false;
    }
    if ($content_lang !== $lang) $this->twig->addGlobal("ContentLanguage", $content_lang);

    $all_at_once = $vocab->getAlphabeticalFull();
    if (!$all_at_once) {
      $search_results = $vocab->searchConceptsAlphabetical($letter, $count, $offset, $content_lang);
      $letters = $vocab->getAlphabet($content_lang);
    } else {
      $search_results = $vocab->searchConceptsAlphabetical('*', null, null, $content_lang);
      $letters = null;
    }
    
    $this->request->setContentLang($content_lang);
    $this->request->setLang($lang);
    $this->request->setVocabid($vocab->getId());
    $this->request->setPage('index');
    $this->request->setLetter($letter);

    $controller = $this; // for use by anonymous function below
    echo $template
            ->render(
                    array('path_fix' => $this->path_fix,
                        'languages' => $this->languages,
                        'lang' => $lang,
                        'vocab_id' => $vocab_id,
                        'vocab' => $vocab,
                        'alpha_results' => $search_results,
                        'search_letter' => $letter,
                        'letters' => $letters,
                        'letter' => $letter,
                        'parts' => $this->parts,
                        'all_letters' => $all_at_once,
                        'request_uri' => $this->request_uri,
                        'request' => $this->request
            ));
  }

  /**
   * Invokes the vocabulary group index page template.
   * @param string $vocab_id vocabulary identifier eg. 'yso'.
   * @param string $lang language parameter eg. 'fi' for Finnish.
   */
  public function invokeGroupIndex($vocab_id, $lang, $stats=false)
  {
    $this->setLanguageProperties($lang);
    $template = $this->twig->loadTemplate('group-index.twig');

    try {
      $vocab = $this->model->getVocabulary($vocab_id);
    } catch (Exception $e) {
      header("HTTP/1.0 404 Not Found");
      $template = $this->twig->loadTemplate('concept-info.twig');
      if (LOG_CAUGHT_EXCEPTIONS)
        error_log('Caught exception: ' . $e->getMessage());
      echo $template->render(array('path_fix' => $this->path_fix,
          'languages' => $this->languages,
          'lang' => $lang, 'vocab_id' => $vocab_id));

      return;
    }

    $content_lang = (isset($_GET['clang'])) ? $_GET['clang'] : $lang;
    $lang_support = true;
    $newlang = $this->verifyVocabularyLanguage($content_lang, $vocab);
    if ($newlang !== null) {
      $content_lang = $newlang;
      $lang_support = false;
    }
    if ($content_lang !== $lang) $this->twig->addGlobal("ContentLanguage", $content_lang);

    $groups = $vocab->listConceptGroups(false, $content_lang);
    
    $this->request->setContentLang($content_lang);
    $this->request->setLang($lang);
    $this->request->setVocabid($vocab->getId());
    $this->request->setPage('groups');

    echo $template
            ->render(
                    array('path_fix' => $this->path_fix,
                        'languages' => $this->languages,
                        'stats' => $stats,
                        'vocab' => $vocab,
                        'groups' => $groups,
                        'parts' => $this->parts,
                        'request_uri' => $this->request_uri,
                        'request' => $this->request
            ));
  }

  /**
   * Invokes the vocabulary group contents page template.
   * @param string $vocab_id vocabulary identifier eg. 'yso'.
   * @param string $lang language parameter eg. 'fi' for Finnish.
   * @param string $group group URI.
   */
  public function invokeGroupContents($vocab_id, $lang, $group)
  {
    $this->setLanguageProperties($lang);
    $template = $this->twig->loadTemplate('group-contents.twig');
    $content_lang = (isset($_GET['clang'])) ? $_GET['clang'] : $lang;
    if ($content_lang !== $lang) $this->twig->addGlobal("ContentLanguage", $content_lang);
    try {
      $vocab = $this->model->getVocabulary($vocab_id);
    } catch (Exception $e) {
      header("HTTP/1.0 404 Not Found");
      $template = $this->twig->loadTemplate('concept-info.twig');
      if (LOG_CAUGHT_EXCEPTIONS)
        error_log('Caught exception: ' . $e->getMessage());
      echo $template->render(array('path_fix' => $this->path_fix,
          'languages' => $this->languages,
          'lang' => $lang, 'vocab_id' => $vocab_id));

      return;
    }
    $groupuri = $vocab->getConceptURI($group);
    $contents = $vocab->listConceptGroupContents($groupuri, $content_lang);
    $group_name = $vocab->getGroupName($groupuri);
    $uri = $vocab->getConceptURI($group); // make sure it's a full URI
    $results = $vocab->getConceptInfo($uri, $content_lang);
    
    $this->request->setContentLang($content_lang);
    $this->request->setLang($lang);
    $this->request->setVocabid($vocab->getId());
    $this->request->setPage('groups');
    $this->request->setUri($groupuri);

    echo $template
            ->render(
                    array('path_fix' => $this->path_fix,
                        'languages' => $this->languages,
                        'vocab' => $vocab,
                        'contents' => $contents,
                        'parts' => $this->parts,
                        'label' => $group_name,
                        'request_uri' => $this->request_uri,
                        'search_results' => $results,
                        'request' => $this->request
            ));
  }

  /**
   * Loads and renders the view containing a specific vocabulary.
   * @param string $vocab_id contains the name of the vocabulary in question.
   * @param string $lang language parameter eg. 'fi' for Finnish.
   * @param string $letter letter parameter eg. 'R'.
   */
  public function invokeVocabularyHome($vocab_id, $lang, $letter = 'A')
  {
    // set language parameters for gettext
    $this->setLanguageProperties($lang);

    try {
      $vocab = $this->model->getVocabulary($vocab_id);
    } catch (Exception $e) {
      header("HTTP/1.0 404 Not Found");
      $template = $this->twig->loadTemplate('concept-info.twig');
      if (LOG_CAUGHT_EXCEPTIONS)
        error_log('Caught exception: ' . $e->getMessage());
      echo $template->render(array('path_fix' => $this->path_fix,
        'languages' => $this->languages,
        'lang' => $lang, 'vocab_id' => $vocab_id));

      return;
    }
    
    $defaultView = $vocab->getDefaultSidebarView();
    // load template
    if ($defaultView === 'groups') {
      $this->invokeGroupIndex($vocab_id, $lang, true);
      return;
    } 

    $content_lang = (isset($_GET['clang'])) ? $_GET['clang'] : $lang;
    $lang_support = true;
    $newlang = $this->verifyVocabularyLanguage($content_lang, $vocab);
    if ($newlang !== null)
      $content_lang = $newlang;
    if ($content_lang !== $lang) $this->twig->addGlobal("ContentLanguage", $content_lang);
    
    $template = $this->twig->loadTemplate('vocab.twig');
    
    $this->request->setContentLang($content_lang);
    $this->request->setLang($lang);
    $this->request->setVocabid($vocab->getId());

    echo $template
            ->render(
                    array('path_fix' => $this->path_fix,
                        'languages' => $this->languages,
                        'vocab' => $vocab,
                        'parts' => $this->parts,
                        'search_letter' => 'A',
                        'active_tab' => $defaultView,
                        'request_uri' => $this->request_uri,
                        'request' => $this->request
                    ));
  }

  /**
   * Invokes a very generic errorpage.
   */
  public function invokeGenericErrorPage()
  {
    $lang = $this->guessLanguage();
    $this->setLanguageProperties($lang);
    header("HTTP/1.0 404 Not Found");
    $template = $this->twig->loadTemplate('error-page.twig');
    echo $template
            ->render(
                    array('path_fix' => $this->path_fix,
                        'languages' => $this->languages,
                        'lang' => $lang,
                        'parts' => $this->parts,
                        'requested_page' => $_SERVER['REQUEST_URI']
                    ));
  }

  /**
   * Verify that the requested language is supported by the vocabulary. If not, returns
   * another language supported by the vocabulary.
   * @param string $lang language to set
   * @param Vocabulary $vocab the vocabulary in question 
   * @return string language tag supported by the vocabulary, or null if the given one is supported
   */

  private function verifyVocabularyLanguage($lang, $vocab)
  {
    $vocab_languages = $vocab->getLanguages();
    $lang_support = in_array($lang, $vocab_languages);
    if ($lang_support)
      return null;
    // If desired language is not available just use the default language of the vocabulary 
    else
      return $vocab->getDefaultLanguage();
  }

}
