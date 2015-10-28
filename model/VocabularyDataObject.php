<?php
/**
 * Copyright (c) 2012-2013 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/**
 * Wraps a vocabulary object in the DataObject class.
 */
class VocabularyDataObject extends DataObject
{
    /**
     * Vocabulary instance where this object originated.
     * @property object $vocab
     */
    protected $vocab;

    /**
     * Needs the following parameters.
     * @param Model $model
     * @param Vocabulary $vocab
     * @param EasyRdf_Resource $resource
     */
    public function __construct($model, $vocab, $resource)
    {
        parent::__construct($model, $resource);

        $this->vocab = $vocab;
    }

}
