<?php

/**
 * Provides access to the http request information
 */
class Request
{

    private $lang;
    private $clang;
    private $page;
    private $vocab;
    private $vocabids;
    private $uri;
    private $letter;
    private $model;
    private $queryParams;
    private $queryParamsPOST;
    private $serverConstants;
    private $cookies;

    /**
     * Initializes the Request Object
     */
    public function __construct($model)
    {
        $this->model = $model;

        // Store GET parameters in a local array, so we can mock them in tests.
        // We do not apply any filters at this point.
        $this->queryParams = [];
        foreach (filter_input_array(INPUT_GET) ?: [] as $key => $val) {
            $this->queryParams[$key] = $val;
        }

        // Store POST parameters in a local array, so we can mock them in tests.
        // We do not apply any filters at this point.
        $this->queryParamsPOST = [];
        foreach (filter_input_array(INPUT_POST) ?: [] as $key => $val) {
            $this->queryParamsPOST[$key] = $val;
        }

        // Store SERVER parameters in a local array, so we can mock them in tests.
        // We do not apply any filters at this point.
        $this->serverConstants = [];
        foreach (filter_input_array(INPUT_SERVER) ?: [] as $key => $val) {
            $this->serverConstants[$key] = $val;
        }

        // Store cookies in a local array, so we can mock them in tests.
        // We do not apply any filters at this point.
        $this->cookies = [];
        foreach (filter_input_array(INPUT_COOKIE) ?: [] as $key => $val) {
            $this->cookies[$key] = $val;
        }
    }

    /**
     * Set a GET query parameter to mock it in tests.
     * @param string $paramName parameter name
     * @param string $value parameter value
     */
    public function setQueryParam($paramName, $value)
    {
        $this->queryParams[$paramName] = $value;
    }

    /**
     * Set a POST query parameter to mock it in tests.
     * @param string $paramName parameter name
     * @param string $value parameter value
     */
    public function setQueryParamPOST($paramName, $value)
    {
        $this->queryParamsPOST[$paramName] = $value;
    }

    /**
     * Set a SERVER constant to mock it in tests.
     * @param string $paramName parameter name
     * @param string $value parameter value
     */
    public function setServerConstant($paramName, $value)
    {
        $this->serverConstants[$paramName] = $value;
    }

    /**
     * Set a cookie to mock it in tests.
     * @param string $paramName parameter name
     * @param string $value parameter value
     */
    public function setCookie($paramName, $value)
    {
        $this->cookies[$paramName] = $value;
    }

    /**
     * Return the requested GET query parameter as a string. Backslashes are stripped for security reasons.
     * @param string $paramName parameter name
     * @return string parameter content, or null if no parameter found
     */
    public function getQueryParam($paramName)
    {
        if (!isset($this->queryParams[$paramName])) return null;
        $val = filter_var($this->queryParams[$paramName], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        return ($val !== null ? str_replace('\\', '', $val) : null);
    }

    /**
     * Return the requested GET query parameter as a string, with no sanitizing.
     * @param string $paramName parameter name
     * @return string parameter content, or null if no parameter found
     */
    public function getQueryParamRaw($paramName)
    {
        return isset($this->queryParams[$paramName]) ? $this->queryParams[$paramName] : null;
    }

    /**
     * Return the requested POST query parameter as a string. Backslashes are stripped for security reasons.
     * @param string $paramName parameter name
     * @param int $maxlength maximum length of parameter, or null if unlimited
     * @return string parameter content, or null if no parameter found
     */
    public function getQueryParamPOST($paramName, $maxlength=null)
    {
        if (!isset($this->queryParamsPOST[$paramName])) return null;
        $val = filter_var($this->queryParamsPOST[$paramName], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if ($maxlength !== null) {
            return substr($val, 0, $maxlength);
        } else {
            return $val;
        }
    }

    public function getQueryParamBoolean($paramName, $default)
    {
        $val = $this->getQueryParamRaw($paramName);
        if ($val !== NULL) {
            $val = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }
        return ($val !== null) ? $val : $default;
    }

    public function getServerConstant($paramName)
    {
        if (!isset($this->serverConstants[$paramName])) return null;
        return filter_var($this->serverConstants[$paramName], FILTER_SANITIZE_ADD_SLASHES);
    }

    public function getCookie($paramName)
    {
        if (!isset($this->cookies[$paramName])) return null;
        return filter_var($this->cookies[$paramName], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }

    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Sets the language variable
     * @param string $lang
     */
    public function setLang($lang)
    {
        if ($lang !== '') {
            $this->lang = $lang;
        }

    }

    public function getContentLang()
    {
        return $this->clang;
    }

    /**
     * Sets the language variable
     * @param string $clang
     */
    public function setContentLang($clang)
    {
        $this->clang = $this->verifyContentLang($clang);
    }

    private function verifyContentLang($lang)
    {
        if ($this->vocab) {
            return $this->vocab->verifyVocabularyLanguage($lang);
        }

        return $lang;
    }

    public function getPage()
    {
        return $this->page;
    }

    /**
     * Sets the page id variable eg. 'groups'
     * @param string $page
     */
    public function setPage($page)
    {
        if ($page !== '') {
            $this->page = $page;
        }

    }

    public function getRequestUri()
    {
        return $this->getServerConstant('HTTP_HOST') . $this->getServerConstant('REQUEST_URI');
    }

    /**
     * Returns the relative page url eg. '/yso/fi/search?clang=en&q=cat'
     * @param string $newlang new UI language to set
     * @return string the relative url of the page
     */
    public function getLangUrl($newlang=null)
    {
        $script_name = str_replace('/index.php', '', $this->getServerConstant('SCRIPT_NAME'));
        $langurl = substr(str_replace($script_name, '', strval($this->getServerConstant('REQUEST_URI'))), 1);
        if ($newlang !== null) {
            $langurl = preg_replace("#^(.*/)?{$this->lang}/#", "$1{$newlang}/", $langurl);
        }
        // make sure that the resulting URL isn't interpreted as an absolute URL
        $langurl = str_replace(":", "", $langurl);
        return $langurl;
    }

    public function getLetter()
    {
        return (isset($this->letter)) ? $this->letter : '';
    }

    /**
     * Sets the page id variable eg. 'B'
     * @param string $letter
     */
    public function setLetter($letter)
    {
        if ($letter !== '') {
            $this->letter = $letter;
        }

    }

    public function getURI()
    {
        return $this->uri;
    }

    /**
     * Sets the uri variable
     * @param string $uri
     */
    public function setURI($uri)
    {
        if ($uri !== '') {
            $this->uri = rtrim(strval($uri));
        }

    }

    /**
     * Used to set the vocab id variable when multiple vocabularies have been chosen eg. 'lcsh+yso'
     * @param string $ids
     */
    public function setVocabids($ids)
    {
        $this->vocabids = $ids;
    }

    public function getVocabid()
    {
        if ($this->vocabids) {
            return $this->vocabids;
        }

        return isset($this->vocab) ? $this->vocab->getId() : '';
    }

    /**
     * Creates a Vocabulary object
     * @param string $vocabid
     */
    public function setVocab($vocabid)
    {
        if (strpos($vocabid, ' ') !== false) // if there are multiple vocabularies just storing the string
        {
            $this->setVocabids($vocabid);
        } else {
            $this->vocab = $this->model->getVocabulary($vocabid);
        }

    }

    /**
     * @return Vocabulary
     */
    public function getVocab()
    {
        return $this->vocab;
    }

    public function getVocabList() {
        return $this->model->getVocabularyList();
    }

    public function getPlugins() {
        if ($this->vocab) {
            return $this->vocab->getConfig()->getPluginRegister();
        }
        return new PluginRegister($this->model->getConfig()->getGlobalPlugins());
    }

    /**
     * Return the version of this Skosmos installation, or "unknown" if
     * it cannot be determined. The version information is based on Git tags.
     * @return string version
     */
    public function getVersion() : string
    {
        return $this->model->getVersion();
    }
}
