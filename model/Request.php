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

    /**
     * Initializes the Request Object
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Return the requested GET query parameter as a string. Backslashes are stripped for security reasons.
     * @param string $paramName parameter name
     * @return string parameter content, or null if no parameter found
     */
    public function getQueryParam($paramName)
    {
        $val = filter_input(INPUT_GET, $paramName, FILTER_SANITIZE_STRING);
        return ($val !== null ? str_replace('\\', '', $val) : null);
    }

    /**
     * Return the requested GET query parameter as a string, with no sanitizing.
     * @param string $paramName parameter name
     * @return string parameter content, or null if no parameter found
     */
    public function getQueryParamRaw($paramName)
    {
        return filter_input(INPUT_GET, $paramName, FILTER_UNSAFE_RAW);
    }

    public function getQueryParamPOST($paramName)
    {
        return filter_input(INPUT_POST, $paramName, FILTER_SANITIZE_STRING);
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
        return filter_input(INPUT_SERVER, $paramName, FILTER_SANITIZE_STRING);
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
     * @return string the relative url of the page
     */
    public function getLangUrl()
    {
        return substr(str_replace(str_replace('/index.php', '', $this->getServerConstant('SCRIPT_NAME')), '', $this->getServerConstant('REQUEST_URI')), 1);
    }

    public function getLetter()
    {
        return (isset($this->letter)) ? $this->letter : 'A';
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
            $this->uri = rtrim($uri);
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

    public function getVocab()
    {
        return $this->vocab;
    }

    public function getVocabList() {
        return $this->model->getVocabularyList();
    }

    public function getPlugins() {
        if ($this->vocab) {
            return $this->vocab->getConfig()->getPlugins();
        }
        return new PluginRegister($this->model->getConfig()->getGlobalPlugins());
    }
}
