<?php

/**
 * Groups vocabularies of a specific category into a DataObject.
 */
class VocabularyCategory extends DataObject
{
    public function __construct($model, $resource)
    {
        if (!($model instanceof Model)) {
            throw new InvalidArgumentException('Invalid constructor parameter given to DataObject.');
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
            $label = $this->resource->label($this->getLang());
            return is_null($label) ? $this->resource->localName() : $label->getValue();
        }
        return $this->model->getText('Vocabularies');
    }

}
