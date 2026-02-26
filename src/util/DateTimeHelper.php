<?php

namespace Util;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Helper class for handling timezone conversions in date formatting
 */
class DateTimeHelper
{
    private ?DateTimeZone $timezone;

    /**
     * @param string|null $timezone The timezone string (e.g., 'Europe/Paris'). If null or empty, defaults to 'UTC'.
     */
    public function __construct(?string $timezone = null)
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
     * Format a date with timezone conversion using Punic
     *
     * @param DateTime $dateTime The datetime to format
     * @param string $format The format string (e.g., 'short', 'full')
     * @param string|null $lang The language code for formatting
     * @return string The formatted date string
     */
    public function formatDate(DateTime $dateTime, string $format = 'short', ?string $lang = null): string
    {
        $converted = $this->convertToTimezone($dateTime);
        return \Punic\Calendar::formatDate($converted, $format, $lang);
    }

    /**
     * Format a date and time with timezone conversion using Punic
     *
     * @param DateTime $dateTime The datetime to format
     * @param string $format The format string (e.g., 'HH:mm:ss')
     * @param string|null $lang The language code for formatting
     * @return string The formatted time string
     */
    public function formatTime(DateTime $dateTime, string $format, ?string $lang = null): string
    {
        $converted = $this->convertToTimezone($dateTime);
        return \Punic\Calendar::format($converted, $format, $lang);
    }

    /**
     * Format a date and time with full details and timezone conversion
     *
     * @param DateTime $dateTime The datetime to format
     * @param string $lang The language code for formatting
     * @return string The formatted datetime string (date + time)
     */
    public function formatDateTime(DateTime $dateTime, string $lang): string
    {
        $converted = $this->convertToTimezone($dateTime);
        return \Punic\Calendar::formatDatetime($converted, 'full', $lang);
    }
}
