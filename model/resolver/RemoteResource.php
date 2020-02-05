<?php

interface RemoteResource {
    /**
     * 
     * @param int $timeout timeout value for the resolution attempt, in seconds
     * @return EasyRdf\Resource the resolved resource, or null if the resolution failed
     */
    public function resolve(int $timeout) : ?EasyRdf\Resource;
}
