<?php

namespace Util;

use DateTime;
use DateTimeZone;
use Exception;
use GlobalConfig;

/**
 * Helper class for handling timezone conversions in date formatting
 */
class DateTimeHelper
{
    private ?DateTimeZone $timezone;
    private ?GlobalConfig $config;

    /**
     * @param string|null $timezone The timezone string (e.g., 'Europe/Paris'). If null or empty, defaults to 'UTC'.
     * @param GlobalConfig|null $config The GlobalConfig instance for accessing language settings
     */
    public function __construct(?string $timezone = null, ?GlobalConfig $config = null)
    {
        // Default to UTC if not provided or empty
        if (empty($timezone)) {
            $timezone = 'UTC';
        }

        try {
            $this->timezone = new DateTimeZone($timezone);
        } catch (Exception $e) {
            // Fallback to UTC if the timezone is invalid
            trigger_error("Invalid timezone '{$timezone}', falling back to UTC: " . $e->getMessage(), E_USER_WARNING);
            $this->timezone = new DateTimeZone('UTC');
        }

        $this->config = $config;
    }

    /**
     * Convert a DateTime object to the configured timezone
     *
     * @param DateTime $dateTime The datetime to convert
     * @return DateTime A new DateTime object in the configured timezone
     */
    public function convertToTimezone(DateTime $dateTime): DateTime
    {
        $converted = clone $dateTime;
        $converted->setTimezone($this->timezone);
        return $converted;
    }

    /**
     * Get the configured timezone
     *
     * @return DateTimeZone
     */
    public function getTimezone(): DateTimeZone
    {
        return $this->timezone;
    }

    /**
     * Convert a language code to a Punic-compatible locale
     *
     * @param string $lang Language code (e.g., 'en', 'fr', 'de')
     * @return string Locale string (e.g., 'en_GB', 'fr_FR') with encoding stripped
     */
    private function languageToLocale(string $lang): string
    {
        if ($this->config === null) {
            return $lang; // Fallback if no config available
        }

        $languages = $this->config->getLanguages();

        if (!isset($languages[$lang])) {
            return $lang; // Fallback to language code if not found
        }

        $locale = $languages[$lang];

        // Extract just the xx_XX part, removing encoding like .utf8
        // Examples: "en_GB.utf8" -> "en_GB", "fr_FR.UTF-8" -> "fr_FR"
        if (preg_match('/^([a-z]{2}_[A-Z]{2})/', $locale, $matches)) {
            return $matches[1];
        }

        return $lang; // Fallback to language code
    }

    /**
     * Format a date with timezone conversion using Punic
     *
     * @param DateTime $dateTime The datetime to format
     * @param string $format The format string (e.g., 'short', 'full')
     * @param string|null $lang The language code for formatting (e.g., 'en', 'fr')
     * @return string The formatted date string
     */
    public function formatDate(DateTime $dateTime, string $format = 'short', ?string $lang = null): string
    {
        $converted = $this->convertToTimezone($dateTime);
        $locale = $lang ? $this->languageToLocale($lang) : null;
        return \Punic\Calendar::formatDate($converted, $format, $locale);
    }

    /**
     * Format a date and time with full details and timezone conversion
     *
     * @param DateTime $dateTime The datetime to format
     * @param string $format The format string (e.g., 'short', 'full')
     * @param string|null $lang The language code for formatting (e.g., 'en', 'fr')
     * @return string The formatted datetime string (date + time)
     */
    public function formatDateTime(DateTime $dateTime, string $format = 'short', ?string $lang = null): string
    {
        $converted = $this->convertToTimezone($dateTime);
        $locale = $this->languageToLocale($lang);
        return \Punic\Calendar::formatDatetime($converted, $format, $locale);
    }
}
