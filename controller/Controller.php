<?php

/**
 * Handles all the requests from the user and changes the view accordingly.
 */
class Controller
{
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

        // Specify the location of the translation tables
        bindtextdomain('skosmos', 'resource/translations');
        bind_textdomain_codeset('skosmos', 'UTF-8');

        // Choose domain for translations
        textdomain('skosmos');

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
        $format = ($best !== null) ? $best->getValue() : null;
        return $format;
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
        $disp_port = ($port == 80 || $port == 443) ? '' : ":$port";
        $domain = filter_input(INPUT_SERVER, 'SERVER_NAME', FILTER_SANITIZE_STRING);
        $full_url = "$protocol://{$domain}{$disp_port}{$base_url}";
        return $full_url;
    }

    public function getBaseHref()
    {
        return ($this->model->getConfig()->getBaseHref() !== null) ? $this->model->getConfig()->getBaseHref() : $this->guessBaseHref();
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
            if (!is_null($ifModifiedSince) && $ifModifiedSince >= $modifiedDate) {
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
