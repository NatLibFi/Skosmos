<?php

/**
 * Wrapper class for key-value caching. Currently supports APC and APCu.
 */
class Cache
{

    /**
     * Wraps apc_fetch() & apcu_fetch()
     */
    public function fetch($key) {
        if (function_exists('apc_fetch')) {
            return apc_fetch($key);
        }
        if (function_exists('apcu_fetch')) {
            return apcu_fetch($key);
        }
        return false;
    }

    /**
     * Wraps apc_store() and apcu_store()
     */
    public function store($key, $value, $ttl=3600) {
        if (function_exists('apc_store')) {
            return apc_store($key, $value);
        }
        else if (function_exists('apcu_store')) {
            return apcu_store($key, $value, $ttl);
        }
    }

    public function isAvailable() {
        return ((function_exists('apc_store') && function_exists('apc_fetch')) || (function_exists('apcu_store') && function_exists('apcu_fetch')));
    }
}
