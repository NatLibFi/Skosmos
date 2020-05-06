<?php

/**
 * Handles all the requests from the user and changes the view accordingly.
 */
class Controller
{
    /**
     * How long to store retrieved disk configuration for HTTP 304 header
     * from git information.
     */
    const GIT_MODIFIED_CONFIG_TTL = 600; // 10 minutes

    /**
     * The controller has to know the model to access the data stored there.
     * @var Model $model contains the Model object.
     */
    public $model;

    protected $negotiator;

    protected $languages;

    /**
     * Initializes the Model object.
     */
    public function __construct($model)
    {
        $this->model = $model;
        $this->negotiator = new \Negotiation\Negotiator();
        $domain = 'skosmos';

        // Specify the location of the translation tables
        bindtextdomain($domain, 'resource/translations');
        bind_textdomain_codeset($domain, 'UTF-8');

        // Choose domain for translations
        textdomain($domain);

        // Build arrays of language information, with 'locale' and 'name' keys
        $this->languages = array();
        foreach ($this->model->getConfig()->getLanguages() as $langcode => $locale) {
            $this->languages[$langcode] = array('locale' => $locale);
            $this->setLanguageProperties($langcode);
            $this->languages[$langcode]['name'] = gettext('in_this_language');
            $this->languages[$langcode]['lemma'] = Punic\Language::getName($langcode, $langcode);
        }
    }

    /**
     * Sets the locale language properties from the parameter (used by gettext and some Model classes).
     * @param string $lang language parameter eg. 'fi' for Finnish.
     */
    public function setLanguageProperties($lang)
    {
        if (array_key_exists($lang, $this->languages)) {
            $locale = $this->languages[$lang]['locale'];
            putenv("LANGUAGE=$locale");
            putenv("LC_ALL=$locale");
            setlocale(LC_ALL, $locale);
        } else {
            trigger_error("Unsupported language '$lang', not setting locale", E_USER_WARNING);
        }
    }

    /**
     * Negotiate a MIME type according to the proposed format, the list of valid
     * formats, and an optional proposed format.
     * As a side effect, set the HTTP Vary header if a choice was made based on
     * the Accept header.
     * @param array $choices possible MIME types as strings
     * @param string $accept HTTP Accept header value
     * @param string $format proposed format
     * @return string selected format, or null if negotiation failed
     */
    protected function negotiateFormat($choices, $accept, $format)
    {
        if ($format) {
            if (!in_array($format, $choices)) {
                return null;
            }
            return $format;
        }

        // if there was no proposed format, negotiate a suitable format
        header('Vary: Accept'); // inform caches that a decision was made based on Accept header
        $best = $this->negotiator->getBest($accept, $choices);
        return ($best !== null) ? $best->getValue() : null;
    }

    private function isSecure()
    {
        if ($protocol = filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_PROTO', FILTER_SANITIZE_STRING)) {
            return \in_array(strtolower($protocol), ['https', 'on', 'ssl', '1'], true);
        }

        return filter_input(INPUT_SERVER, 'HTTPS', FILTER_SANITIZE_STRING) !== null;
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
        $protocol = $this->isSecure() ? 'https' : 'http';
        $port = filter_input(INPUT_SERVER, 'SERVER_PORT', FILTER_SANITIZE_STRING);
        $disp_port = ($port == 80 || $port == 443) ? '' : ":$port";
        $domain = filter_input(INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_STRING);
        return "$protocol://{$domain}{$disp_port}{$base_url}";
    }

    public function getBaseHref()
    {
        return ($this->model->getConfig()->getBaseHref() !== null) ? $this->model->getConfig()->getBaseHref() : $this->guessBaseHref();
    }

    /**
     * Creates Skosmos links from uris.
     * @param string $uri
     * @param Vocabulary $vocab
     * @param string $lang
     * @param string $type
     * @param string $clang content
     * @param string $term
     * @throws Exception if the vocabulary ID is not found in configuration
     * @return string containing the Skosmos link
     */
    public function linkUrlFilter($uri, $vocab, $lang, $type = 'page', $clang = null, $term = null) {
        // $vocab can either be null, a vocabulary id (string) or a Vocabulary object
        if ($vocab === null) {
            // target vocabulary is unknown, best bet is to link to the plain URI
            return $uri;
        } elseif (is_string($vocab)) {
            $vocid = $vocab;
            $vocab = $this->model->getVocabulary($vocid);
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
            $paramstr = count($params) > 0 ? '?' . http_build_query($params) : '';
            if ($type && $type !== '' && $type !== 'vocab' && !($localname === '' && $type === 'page')) {
                return "$vocid/$lang/$type/$localname" . $paramstr;
            }

            return "$vocid/$lang/$localname" . $paramstr;
        }

        // case 2: URI outside vocabulary namespace, or has problematic chars
        // pass the full URI as parameter instead
        $params['uri'] = $uri;
        return "$vocid/$lang/$type/?" . http_build_query($params);
    }

    /**
     * Echos an error message when the request can't be fulfilled.
     * @param string $code
     * @param string $status
     * @param string $message
     */
    protected function returnError($code, $status, $message)
    {
        header("HTTP/1.0 $code $status");
        header("Content-type: text/plain; charset=utf-8");
        echo "$code $status : $message";
    }

    protected function notModified(Modifiable $modifiable = null)
    {
        $notModified = false;
        if ($modifiable !== null && $modifiable->isUseModifiedDate()) {
            $modifiedDate = $this->getModifiedDate($modifiable);
            $notModified = $this->sendNotModifiedHeader($modifiedDate);
        }
        return $notModified;
    }

    /**
     * Return the modified date.
     *
     * @param Modifiable $modifiable
     * @return DateTime|null
     */
    protected function getModifiedDate(Modifiable $modifiable = null)
    {
        $modified = null;
        $modifiedDate = $modifiable->getModifiedDate();
        $gitModifiedDate = $this->getGitModifiedDate();
        $configModifiedDate = $this->getConfigModifiedDate();

        // max with an empty list raises an error and returns bool
        if ($modifiedDate || $gitModifiedDate || $configModifiedDate) {
            $modified = max($modifiedDate, $gitModifiedDate, $configModifiedDate);
        }
        return $modified;
    }

    /**
     * Return the datetime of the latest commit, or null if git is not available or if the command failed
     * to execute.
     *
     * @see https://stackoverflow.com/a/33986403
     * @return DateTime|null
     */
    protected function getGitModifiedDate()
    {
        $commitDate = null;
        $cache = $this->model->getConfig()->getCache();
        $cacheKey = "git:modified_date";
        $gitCommand = 'git log -1 --date=iso --pretty=format:%cd';
        if ($cache->isAvailable()) {
            $commitDate = $cache->fetch($cacheKey);
            if (!$commitDate) {
                $commitDate = $this->executeGitModifiedDateCommand($gitCommand);
                if ($commitDate) {
                    $cache->store($cacheKey, $commitDate, static::GIT_MODIFIED_CONFIG_TTL);
                }
            }
        } else {
            $commitDate = $this->executeGitModifiedDateCommand($gitCommand);
        }
        return $commitDate;
    }

    /**
     * Execute the git command and return a parsed date time, or null if the command failed.
     *
     * @param string $gitCommand git command line that returns a formatted date time
     * @return DateTime|null
     */
    protected function executeGitModifiedDateCommand($gitCommand)
    {
        $commitDate = null;
        $commandOutput = @exec($gitCommand);
        if ($commandOutput) {
            $commitDate = new \DateTime(trim($commandOutput));
            $commitDate->setTimezone(new \DateTimeZone('UTC'));
        }
        return $commitDate;
    }

    /**
     * Return the datetime of the modified time of the config file. This value is read in the GlobalConfig
     * for every request, so we simply access that value and if not null, we will return a datetime. Otherwise,
     * we return a null value.
     *
     * @see http://php.net/manual/en/function.filemtime.php
     * @return DateTime|null
     */
    protected function getConfigModifiedDate()
    {
        $dateTime = null;
        $configModifiedTime = $this->model->getConfig()->getConfigModifiedTime();
        if ($configModifiedTime !== null) {
            $dateTime = (new DateTime())->setTimestamp($configModifiedTime);
        }
        return $dateTime;
    }

    /**
     * If the $modifiedDate is a valid DateTime, and if the $_SERVER variable contains the right info, and
     * if the $modifiedDate is not more recent than the latest value in $_SERVER, then this function sets the
     * HTTP 304 not modified and returns true..
     *
     * If the $modifiedDate is still valid, then it sets the Last-Modified header, to be used by the browser for
     * subsequent requests, and returns false.
     *
     * Otherwise, it returns false.
     *
     * @param DateTime $modifiedDate the last modified date to be compared against server's modified since information
     * @return bool whether it sent the HTTP 304 not modified headers or not (useful for sending the response without
     *              further actions)
     */
    protected function sendNotModifiedHeader($modifiedDate): bool
    {
        if ($modifiedDate) {
            $ifModifiedSince = $this->getIfModifiedSince();
            $this->sendHeader("Last-Modified: " . $modifiedDate->format('Y-m-d H:i:s'));
            if ($ifModifiedSince !== null && $ifModifiedSince >= $modifiedDate) {
                $this->sendHeader("HTTP/1.0 304 Not Modified");
                return true;
            }
        }
        return false;
    }

    /**
     * @return DateTime|null a DateTime object if the value exists in the $_SERVER variable, null otherwise
     */
    protected function getIfModifiedSince()
    {
        $ifModifiedSince = null;
        if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
            // example value set by a browser: "2019-04-13 08:28:23"
            $ifModifiedSince = DateTime::createFromFormat("Y-m-d H:i:s", $_SERVER["HTTP_IF_MODIFIED_SINCE"]);
        }
        return $ifModifiedSince;
    }

    /**
     * Sends HTTP headers. Simply calls PHP built-in header function. But being
     * a function here, it can easily be tested/mocked.
     *
     * @param $header string header to be sent
     */
    protected function sendHeader($header)
    {
        header($header);
    }
}
