<?php

/**
 * Interface Modifiable.
 */
interface Modifiable
{
    /**
     * @return DateTime|null the modified date, or null if not available
     */
    public function getModifiedDate();

    /**
     * @return boolean
     */
    public function isUseModifiedDate();

}
