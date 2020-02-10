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
    public function enable()
    {
        $this->disabled = false;
    }
    /**
     * Disable the Honeypot validation
     */
    public function disable()
    {
        $this->disabled = true;
    }
    /**
     * Generate a new honeypot and return the form HTML
     * @param  string $honey_name
     * @param  string $honey_time
     * @return string
     */
    public function generate($honey_name, $honey_time)
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
    public function validateHoneypot($value)
    {
        if ($this->disabled) {
            return true;
        }
        return $value == '';
    }
    /**
     * Validate honey time was within the time limit
     *
     * @param  mixed $value
     * @param  array $parameters
     * @return boolean
     */
    public function validateHoneytime($value, $parameters)
    {
        if ($this->disabled) {
            return true;
        }

        // Get the decrypted time
        $value = $this->decryptTime($value);
        // The current time should be greater than the time the form was built + the speed option
        return ( is_numeric($value) && time() > ($value + $parameters[0]) );
    }
    /**
     * Get encrypted time
     * @return string
     */
    public function getEncryptedTime()
    {
        return base64_encode(time());
    }
    /**
     * Decrypt the given time
     *
     * @param  mixed $time
     * @return string|null
     */
    public function decryptTime($time)
    {
        try {
            return base64_decode($time);
        } catch (\Exception $exception) {
            return null;
        }
    }
}
