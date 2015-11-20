<?php
/**
 * Copyright (c) 2012-2013 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/**
 * Handles all the requests from the user and changes the view accordingly.
 */
class Controller
{
    /**
     * The controller has to know the model to access the data stored there.
     * @param $model contains the Model object.
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
        $this->negotiator = new \Negotiation\FormatNegotiator();

        // Specify the location of the translation tables
        bindtextdomain('skosmos', 'resource/translations');
        bind_textdomain_codeset('skosmos', 'UTF-8');

        // Choose domain for translations
        textdomain('skosmos');

        // Build arrays of language information, with 'locale' and 'name' keys
        global $LANGUAGES; // global setting defined in config.inc
        $this->languages = array();
        foreach ($LANGUAGES as $langcode => $locale) {
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
            putenv("LC_ALL=$locale");
            setlocale(LC_ALL, $locale);
        } else {
            trigger_error("Unsupported language '$lang', not setting locale", E_USER_WARNING);
        }
    }

}
