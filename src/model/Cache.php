<?php

/**
 * Wrapper class for key-value caching. Currently supports only APCu.
 */
class Cache
{
    /**
     * Wraps apc_fetch() & apcu_fetch()
     */
    public function fetch($key)
    {
        if (function_exists('apcu_fetch')) {
            return apcu_fetch($key);
        }
        return false;
    }

    /**
     * Wraps apc_store() and apcu_store()
     */
    public function store($key, $value, $ttl=3600)
    {
        if (function_exists('apcu_store')) {
            return apcu_store($key, $value, $ttl);
        }
        return false;
    }

    public function isAvailable()
    {
        return (function_exists('apcu_store') && function_exists('apcu_fetch'));
    }
}
