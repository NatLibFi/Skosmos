<?php

abstract class RemoteResource
{
    protected $model;
    protected $uri;


    /**
     * Construct the RemoteResource
     * @param Model $model the Model object representing the application
     * @param string $uri the remote URI this object represents
     */

    public function __construct(Model $model, string $uri)
    {
        $this->model = $model;
        $this->uri = $uri;
    }

    /**
     * Resolve the URI of this resource to an RDF graph containing information about it
     * @param int $timeout timeout value for the resolution attempt, in seconds
     * @return EasyRdf\Resource the resolved resource, or null if the resolution failed
     */
    abstract public function resolve(int $timeout): ?EasyRdf\Resource;
}
