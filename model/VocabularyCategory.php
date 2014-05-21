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
  /**
   * Returns all vocabularies in the category.
   */
  public function getVocabularies()
  {
    return $this->model->getVocabulariesInCategory($this->resource);
  }

  /**
   * Returns the title of the category.
   */
  public function getTitle()
  {
    return $this->resource->label($this->lang)->getValue();
  }

}
