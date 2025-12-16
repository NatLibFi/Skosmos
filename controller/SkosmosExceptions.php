<?php

/**
 * Handles all the self-made exceptions
 */

class TimeoutException extends RuntimeException {
//    protected static $message = "EXCEPTION: This is a message for ...";

    public static function throwExceptionMessage() {
        echo("EXCEPTION: This is a message for ...");
    }
}

