<?php

/**
 * Original class from msurguy/Honeypot. Licensed under the MIT License. Instead of using Laravel Crypt class,
 * this implementation simply returns the decrypted value base64-encoded, as the encoded value.
 * @see https://github.com/msurguy/Honeypot
 */
class Honeypot
{
    protected $disabled = false;
    /**
     * Enable the Honeypot validation
     */
    public function enable() : void
    {
        $this->disabled = false;
    }
    /**
     * Disable the Honeypot validation
     */
    public function disable() : void
    {
        $this->disabled = true;
    }
    /**
     * Generate a new honeypot and return the form HTML
     * @param  string $honey_name
     * @param  string $honey_time
     * @return string
     */
    public function generate($honey_name, $honey_time) : string
    {
        // Encrypt the current time
        $honey_time_encrypted = $this->getEncryptedTime();
        return '<div id="' . $honey_name . '_wrap" style="display:none;">' . "\r\n" .
               '<input name="' . $honey_name . '" type="text" value="" id="' . $honey_name . '"/>' . "\r\n" .
               '<input name="' . $honey_time . '" type="text" value="' . $honey_time_encrypted . '"/>' . "\r\n" .
                '</div>';
    }
    /**
    * Validate honeypot is empty
    *
    * @param  mixed $value
    * @return boolean
    */
    public function validateHoneypot($value) : bool
    {
        if ($this->disabled) {
            return true;
        }
        return $value == '';
    }
    /**
     * Validate honey time was within the time limit
     *
     * @param  string $value base64 encoded time value
     * @param  int $minDelta minimum time difference in seconds
     * @return boolean
     */
    public function validateHoneytime($value, $minDelta) : bool
    {
        if ($this->disabled) {
            return true;
        }

        // Get the decrypted time
        $value = $this->decryptTime($value);
        // The current time should be greater than the time the form was built + minimum
        return ( is_numeric($value) && time() > ($value + $minDelta) );
    }
    /**
     * Get encrypted time
     * @return string
     */
    public function getEncryptedTime() : string
    {
        return base64_encode(time());
    }
    /**
     * Decrypt the given time
     *
     * @param  mixed $time
     * @return int|null
     */
    public function decryptTime($time) : ?int
    {
        try {
            return intval(base64_decode($time));
        } catch (\Exception $exception) {
            return null;
        }
    }
}
