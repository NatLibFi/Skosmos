<?php

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
     * @param EasyRdf\Resource $resource
     */
    public function __construct($model, $vocab, $resource)
    {
        parent::__construct($model, $resource);

        $this->vocab = $vocab;
    }

}
