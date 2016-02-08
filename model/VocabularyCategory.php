<?php
/**
 * Copyright (c) 2012-2013 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/**
 * Groups vocabularies of a specific category into a DataObject.
 */
class VocabularyCategory extends DataObject
{
    public function __construct($model, $resource)
    {
        if (!($model instanceof Model)) {
            throw new Exception('Invalid constructor parameter given to DataObject.');
        }

        $this->model = $model;
        $this->resource = $resource;
        $this->order = array();
    }

    /**
     * Returns all vocabularies in the category.
     */
    public function getVocabularies()
    {
        if ($this->resource) {
            return $this->model->getVocabulariesInCategory($this->resource);
        }
        return $this->model->getVocabularies();
    }

    /**
     * Returns the title of the category.
     */
    public function getTitle()
    {
        if ($this->resource) {
            $label = $this->resource->label($this->getEnvLang());
            return is_null($label) ? $this->resource->localName() : $label->getValue();
        }
        return gettext('Vocabularies');
    }

}
