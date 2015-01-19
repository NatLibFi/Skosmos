<?php
/**
 * Copyright (c) 2012-2013 Aalto University and University of Helsinki
 * MIT License
 * see LICENSE.txt for more information
 */

/**
 * Dataobject for a single concept.
 */

class Concept extends VocabularyDataObject
{
  /**
   * Stores a label string if the concept has been found through
   * a altLabel/label in a another language than the ui.
   */
  private $foundby;
  /** Type of foundby match: 'alt', 'hidden' or 'lang' */
  private $foundbytype;
  /** the EasyRdf_Graph object of the concept */
  private $graph;
  /** the preferred order of properties */
  public $order;

  /** concept properties that should not be shown to users */
  private $DELETED_PROPERTIES = array(
    'skosext:broaderGeneric',		# these are remnants of bad modeling
    'skosext:broaderPartitive',		#

    'skos:hiddenLabel',			# because it's supposed to be hidden

    'skos:topConceptOf',		# because it's too technical, not relevant for users
    'skos:inScheme',			# should be evident in any case
    'skos:member'			    # this is shouldn't be shown on the group page
  );

  /** related concepts that should be shown to users in the appendix */
  private $MAPPING_PROPERTIES = array(
    'skos:exactMatch', 
    'skos:narrowMatch', 
    'skos:broadMatch', 
    'skos:closeMatch', 
    'skos:relatedMatch', 
    'owl:sameAs' 
  );

  /**
   * Initializing the concept object requires the following parameters.
   * @param Model $model
   * @param Vocabulary $vocab
   * @param EasyRdf_Resource $resource
   * @param EasyRdf_Graph $graph
   */
  public function __construct($model, $vocab, $resource, $graph)
  {
    parent::__construct($model, $vocab, $resource);
    $this->order = array("rdf:type", "dc:isReplacedBy", "skos:definition", "skos:broader", "skos:narrower", "skos:related", "skos:altLabel", "skosmos:memberOf", "skos:note", "skos:scopeNote", "skos:historyNote", "rdfs:comment", "dc11:source", "dc:source", "skos:prefLabel");
    $this->graph = $graph;
  }

  /**
   * Returns the concept uri.
   * @return string
   */
  public function getUri()
  {
    return $this->resource->getUri();
  }
  
  /**
   * Returns a boolean value indicating if the concept has been deprecated.
   * @return boolean 
   */
  public function getDeprecated()
  {
    foreach ($this->resource->all('rdf:type') as $type)
      if (strpos($type->getUri(), 'DeprecatedConcept'))
        return true;
    return false;
  }
   
  /**
   * Returns a label for the concept in the ui language or if not possible in any language.
   * @return string
   */
  public function getLabel()
  {
    // 1. label in current language
    if ($this->resource->label($this->lang) !== null)
      return $this->resource->label($this->lang)->getValue();
    // 2. label in any language
    $label = $this->resource->label();
    // if the label lang code is a subset of the ui lang eg. en-GB
    if ($label !== null && strpos($label->getLang(), $this->lang . '-') === 0)
      return $label->getValue();
    if ($label !== null)
      return $label->getValue() . " (" . $label->getLang() . ")";
    // empty
    return "";
  }

  /**
   * Returns the vocabulary identifier string or null if that is not available.
   * @return string
   */
  public function getVocab()
  {
    return $this->vocab ? $this->vocab->getId() : null;
  }
  
  /**
   * Returns the vocabulary shortname string or id if that is not available.
   * @return string
   */
  public function getShortName()
  {
    return $this->vocab ? $this->vocab->getShortName() : null;
  }


  /**
   * Setter for the $foundby property.
   * @param string $label label that was matched
   * @param string $type type of match: 'alt', 'hidden', or 'lang'
   */
  public function setFoundBy($label, $type)
  {
    $this->foundby = $label;
    $this->foundbytype = $type;
  }

  /**
   * Getter for the $foundby property.
   * @return string
   */
  public function getFoundBy()
  {
    return $this->foundby;
  }

  /**
   * Getter for the $foundbytype property.
   * @return string
   */
  public function getFoundByType()
  {
    return $this->foundbytype;
  }

  public function getMappingProperties()
  {
    $properties = array();

    $members_array = array();
    $long_uris = $this->resource->propertyUris();

    foreach ($long_uris as &$prop) {
      if (EasyRdf_Namespace::shorten($prop)) // shortening property labels if possible
        $prop = $sprop = EasyRdf_Namespace::shorten($prop);
      else
        $sprop = "<$prop>"; // EasyRdf requires full URIs to be in angle brackets

      // Iterating through every resource and adding these to the data object.
      foreach ($this->resource->allResources($sprop) as $val) {
        $label = null;
        $label_lang = null;
        $exvocab = null;
        $voclabel = null;

        if (in_array($prop, $this->MAPPING_PROPERTIES)) {
          $exuri = $val->getUri();
          $exvoc = $this->model->guessVocabularyFromURI($exuri);
          if ($exvoc) {
            $label_lang = $this->lang;
            $label = $this->getExternalLabel($exvoc, $exuri, $label_lang);
            // if there isn't a label available with the current ui language use the vocabulary default language
            if (!$label) { 
              $label_lang = $exvoc->getDefaultLanguage();
              $label = $this->getExternalLabel($exvoc, $exuri, $label_lang);
            }
            $exvocab = $exvoc->getId();
            $voclabel = $exvoc->getTitle();
          }
          if (!$exvoc || !$label) {
            if ($this->vocab->getExternalResourcesLoading())
              $response = $this->model->getResourceFromUri($exuri);
            if ($response) {
              $pref_label = $this->model->getResourceLabel($response, $this->lang);
              if($pref_label) {
                $label = $pref_label->getValue();
                $label_lang = $pref_label->getLang();
              }
              $scheme = $response->get('skos:inScheme');
              $schemeLabel = null;
              if($scheme) {
                $schemeResource = $this->model->getResourceFromUri($scheme->getUri());
                if ($schemeResource)
                  $schemeLabel = $schemeResource->label();
                if ($schemeLabel)
                  $schemeLabel = $schemeLabel->getValue();
              }
              if ($label !== null && $schemeLabel == null) {
                // got a label for the concept, but not the scheme - use the host name as scheme label
                $schemeLabel = parse_url($exuri, PHP_URL_HOST);
              }
              $prop_info = $this->getPropertyParam($val, $prop);
              $properties[$prop_info['prop']][] = new ConceptPropertyValue(
                $prop_info['prop'],
                $prop_info['concept_uri'],
                $prop_info['vocab'],
                $label_lang,
                $label,
                null,
                null,
                $schemeLabel
              );
            }
            if (!$label) {
              $label = $val->shorten() ? $val->shorten() : $exuri;
              $label_lang = $this->lang;
              $exvocab = null;
              
              $prop_info = $this->getPropertyParam($val, $prop);
              $properties[$prop_info['prop']][] = new ConceptPropertyValue(
                $prop_info['prop'],
                $prop_info['concept_uri'],
                $prop_info['vocab'],
                null,
                $label,
                null,
                null,
                null 
              );

            }
          }
        } else {
          break;
        }
        $prop_info = $this->getPropertyParam($val, $prop);
        if ($prop_info['label'] == null) {
          $prop_info['label'] = $label;
          $prop_info['lang'] = $label_lang;
          $prop_info['exvocab'] = $exvocab;
        }
        if ($prop_info['label'] !== null && $voclabel !== null) {
          $properties[$prop_info['prop']][] = new ConceptPropertyValue(
            $prop_info['prop'],
            $prop_info['concept_uri'],
            $prop_info['vocab'],
            $prop_info['lang'],
            $prop_info['label'],
            $prop_info['exvocab'],
            null,
            $voclabel
          );
        }
      }
    }

    // sorting the properties to a order preferred in the Skosmos concept page.
    $properties = $this->arbitrarySort($properties);

    // clean up: remove unwanted properties
    foreach ($this->DELETED_PROPERTIES as $prop) {
      if (isset($properties[$prop]))
        unset($properties[$prop]);
    }

    $ret = array();
    foreach ($properties as $prop => $values) {
      // sorting the values by vocabulary name for consistency.
      $sortedvalues = array();
      $fixed;
      foreach ($values as $value) {
        $sortedvalues[$value->getVocabName() . $value . $value->getUri()] = $value; 
      }
      ksort($sortedvalues);
      $values = $sortedvalues;
      $propres = new EasyRdf_Resource($prop, $this->graph);
      $proplabel = $propres->label($this->lang); // current language
      if (!$proplabel) $proplabel = $propres->label(); // any language
      $propobj = new ConceptProperty($prop, $proplabel, $values);
      if ($propobj->getLabel()) // only display properties for which we have a label
        $ret[] = $propobj;
    }

    return $ret;
  }

  /**
   * Iterates over all the properties of the concept and returns those in an array.
   * @return array
   */
  public function getProperties()
  {
    $properties = array();
    $narrowers_by_uri = array(); 
    $in_a_collection = array();
    $members_array = array();
    $long_uris = $this->resource->propertyUris();
    $duplicates = array();

    // looking for collections and linking those with their narrower concepts
    if ($this->vocab->getArrayClassURI() !== null) {
      $collections = $this->graph->allOfType($this->vocab->getArrayClassURI()); 
      if (sizeof($collections) > 0) { 
        // indexing the narrowers once to avoid iterating all of them with every collection
        foreach ($this->resource->allResources('skos:narrower') as $narrower)
          $narrowers_by_uri[$narrower->getUri()] = $narrower;

        foreach ($collections as $coll) {
          $current_collection_members = $this->getCollectionMembers($coll, $narrowers_by_uri);
          foreach ($current_collection_members as $collection)
            if (array_key_exists('sub_members', $collection))
              foreach ($collection['sub_members'] as $member) 
                $in_a_collection[$member['uri']] = true;

          if (array_key_exists('sub_members', $collection))
            if($collection['sub_members']) // do not show empty collections in the narrowers
              $members_array = array_merge($current_collection_members, $members_array);
        }
      }
    }

    foreach ($long_uris as &$prop) {
      if (EasyRdf_Namespace::shorten($prop)) // shortening property labels if possible
        $prop = $sprop = EasyRdf_Namespace::shorten($prop);
      else
        $sprop = "<$prop>"; // EasyRdf requires full URIs to be in angle brackets

      // searching for subproperties of literals too
      foreach ($this->graph->allResources($prop, 'rdfs:subPropertyOf') as $subi) {
        $suburi = EasyRdf_Namespace::shorten($subi->getUri());
        if (!isset($suburi))
          $suburi = $subi->getUri();
        $duplicates[$suburi] = $prop;
      }

      // Iterating through every literal and adding these to the data object.
      foreach ($this->resource->allLiterals($sprop) as $val) {
        if ($val->getLang() == $this->lang || $val->getLang() === null) {
          // if the property is a date object a string representation is passed as the value.
          if ($val->getDataType() === 'xsd:date' || $val->getDataType() === 'xsd:dateTime')
            $properties[$prop][] = new ConceptPropertyValue($prop, null, null, $val->getLang(), $val->__toString());
          else
            $properties[$prop][] = new ConceptPropertyValue($prop, null, null, $val->getLang(), $val->getValue());
        }
      }
      
      // Iterating through every resource and adding these to the data object.
      foreach ($this->resource->allResources($sprop) as $val) {
        $label = null;
        $label_lang = null;
        $exvocab = null;
        // skipping narrower concepts which are already shown in a collection
        if ($prop === 'skos:narrower' && array_key_exists($val->getUri(), $in_a_collection))
          continue;

        if (in_array($prop, $this->MAPPING_PROPERTIES)) {
          break;
        } elseif ($prop === 'rdf:type') {
          $exuri = $val->getUri();
          $exvoc = $this->model->guessVocabularyFromURI($exuri);
          if ($exvoc) {
            $label_lang = $exvoc->getDefaultLanguage();
            $label = $this->getExternalLabel($exvoc, $exuri, $label_lang);
            $exvocab = $exvoc->getId();
          }
          if (!$exvoc || !$label) {
            $label = $val->shorten() ? $val->shorten() : $exuri;
            $label_lang = $this->lang;
            $exvocab = null;
          }
        }
        $prop_info = $this->getPropertyParam($val, $prop);
        if ($prop_info['label'] == null) {
          $prop_info['label'] = $label;
          $prop_info['lang'] = $label_lang;
          $prop_info['exvocab'] = $exvocab;
        }
        if ($prop_info['label'] !== null) {
          $properties[$prop_info['prop']][] = new ConceptPropertyValue(
            $prop_info['prop'],
            $prop_info['concept_uri'],
            $prop_info['vocab'],
            $prop_info['lang'],
            $prop_info['label'],
            $prop_info['exvocab']
          );
        }
      }
    }

    // if skos:narrower properties are actually groups we need to remove duplicates.
    foreach ($members_array as $topConcept) {
      $topProp = new ConceptPropertyValue('skos:narrower', $topConcept['parts'], $topConcept['vocab'], $topConcept['lang'], $topConcept['label'], $exvocab = null);
      $properties['skos:narrower'][] = $topProp;
      foreach ($topConcept['sub_members'] as $subMember) {
        $topProp->addSubMember('skosmos:sub', $subMember['label'], $subMember['parts'], $subMember['vocab'], $subMember['lang'], $subMember['external']);
      }
    }

    // clean up: remove unwanted properties
    foreach ($this->DELETED_PROPERTIES as $prop) {
      if (isset($properties[$prop]))
        unset($properties[$prop]);
    }

    // sorting the properties to a order preferred in the Skosmos concept page.
    $properties = $this->arbitrarySort($properties);

    $propertyValues = array();

    $ret = array();
    foreach ($properties as $prop => $values) {
      $propres = new EasyRdf_Resource($prop, $this->graph);
      $proplabel = $propres->label($this->lang); // current language
      if (!$proplabel) $proplabel = $propres->label(); // any language
      foreach ($values as $value) {
        $vallabel = $value->getLabel();
        if (!is_string($vallabel)) continue;
        $propertyValues[$vallabel][] = $propres->getUri();
      }
      $propobj = new ConceptProperty($prop, $proplabel, $values);
      if ($propobj->getLabel()) // only display properties for which we have a label
        $ret[$prop] = $propobj;
    }

    foreach ($propertyValues as $value => $propnames) {
      // if the value of prefLabel and rdfs:label are the same we can remove rdfs:label as it's redundant
      if (in_array('skos:prefLabel', $propnames) && in_array('rdfs:label', $propnames)) {
        unset($ret['rdfs:label']);
      }
      // if there are multiple properties with the same string value.
      if (count($propnames) > 1) {
        foreach( $propnames as $property)
          // if there is a more accurate property delete the more generic one.
          if (isset($duplicates[$property])) {
            unset($ret[$property]);
          }
      }
    }

    // unsetting the prefLabel last to detect and remove properties with the same value.
    unset($ret['skos:prefLabel']);

    return $ret;
  }

  /**
   * Returns an array of a resources property
   * @param EasyRdf_Resource $val
   * @param string $prop identifier eg. 'skos:narrower'.
   * @return array
   */
  private function getPropertyParam($val, $prop = null)
  {
    $exvocab = $this->model->guessVocabularyFromURI($val->getUri());
    if (isset($exvocab))
      $exvocid = $exvocab->getId();
    $ret = array();

    if ($val->label($this->lang) !== null) { // current language
      $ret['label'] = $val->label($this->lang)->getValue();
      $ret['lang'] = $this->lang;
    } elseif ($val->label() !== null) { // any language
      $label = $val->label();
      $ret['label'] = $label->getValue();
      $ret['lang'] = $label->getLang();
    } else {
      $ret['label'] = null;
      $ret['lang'] = null;
    }

    $ret['concept_uri'] = $val->getUri();
    $ret['vocab'] = $this->getVocab();
    $ret['prop'] = $prop;
    $ret['exvocab'] = isset($exvocid) ? $exvocid : null;

    return $ret;
  }

  /**
   * Gets the members of a specific collection.
   * @param $coll
   * @param array containing all narrowers as EasyRdf_Resource
   * @return array
   */
  private function getCollectionMembers($coll, $narrowers)
  {
    $members_array = Array();
    $coll_info = $this->getPropertyParam($coll);
    $external = false;
    if (strstr($coll_info['concept_uri'], 'http')) // for identifying concepts that are found with a uri not consistent with the current vocabulary
      $external = true;
    $members_array[$coll->getUri()] = array('type' => 'resource', 'label' => $coll_info['label'], 'lang' => $coll_info['lang'],
        'uri' => $coll_info['concept_uri'], 'vocab' => $coll_info['vocab'], 'parts' => $coll->getUri(), 'external' => $external);
    foreach ($coll->allResources('skos:member') as $member) {
      if (!array_key_exists($member->getUri(), $narrowers))
        continue;
      $narrower = $narrowers[$member->getUri()];
      if (isset($narrower))
        $narrow_info = $this->getPropertyParam($narrower);
      else 
        continue;
      $external = false;
      if (strstr($narrow_info['concept_uri'], 'http')) // for identifying concepts that are found with a uri not consistent with the current vocabulary
        $external = true;
      if ($narrow_info['label'] == null) { // fixes json encoded unicode characters causing labels to disappear in afo
        $narrow_info['label'] = ('"' . $narrow_info['concept_uri'] . '"');
        $narrow_info['label'] = json_decode($narrow_info['label']);
        $narrow_info['concept_uri'] = $narrow_info['label'];
        $narrow_info['label'] = strtr($narrow_info['label'], '_', ' ');
      }
      $members_array[$coll->getUri()]['sub_members'][] = array('type' => 'resource', 'label' => $narrow_info['label'], 'lang' => $narrow_info['lang'],
        'uri' => $narrow_info['concept_uri'], 'vocab' => $narrow_info['vocab'], 'parts' => $narrower->getUri(), 'external' => $external);
    }

    return $members_array;
  }

  /**
   * Gets the groups the concept belongs to.
   */
  public function getGroupProperties() {
    // finding out if the concept is a member of some group
    $groups = array();
    $reverseResources = $this->graph->resourcesMatching('skos:member', $this->resource);
    if (isset($reverseResources)) {
      $arrayClassURI = $this->vocab !== null ? $this->vocab->getArrayClassURI() : null;
      $arrayClass = $arrayClassURI !== null ? EasyRdf_Namespace::shorten($arrayClassURI) : null;
      foreach ($reverseResources as $reverseResource) {
        $property = in_array($arrayClass, $reverseResource->types()) ? "skosmos:memberOfArray" : "skosmos:memberOf" ;
        $exvoc = $this->model->guessVocabularyFromURI($reverseResource->getUri());
        $exvocab = $exvoc ? $exvoc->getId() : null;
        $reverseUri = $reverseResource->getUri(null);
        $label = $reverseResource->label($this->lang) ? $reverseResource->label($this->lang) : $reverseResource->label();
        $labelLang = $label ? $label->getLang() : null;
        $label = $label ? $label->getValue() : null;
        $super = $reverseResource->get('isothes:superGroup');
        while(isset($super)) {
          $groups[] = new ConceptPropertyValue('isothes:superGroup', $super->getUri(), $exvocab, $labelLang, $super->label($this->lang));
          $super = $super->get('isothes:superGroup');
        }
        $groups[] = new ConceptPropertyValue($property, $reverseUri, $exvocab, $labelLang, $label);
      }
    }
    return $groups;
  }


  /**
   * Gets the values for the property in question in all other languages than the ui language.
   */
  public function getForeignLabels()
  {
    global $LANGUAGES;
    $labels = array();
    foreach ($this->resource->allLiterals('skos:prefLabel') as $lit) {
      // filtering away subsets of the current language eg. en vs en-GB
      if ($lit->getLang() != $this->lang && strpos($lit->getLang(), $this->lang . '-') !== 0)
        $labels[$lit->getLang()][] = new ConceptPropertyValue(
            'skos:prefLabel',
            '',
            '',
            $lit->getLang(),
            $lit->getValue()
          );

    }
    foreach ($this->resource->allLiterals('skos:altLabel') as $lit) {
      // filtering away subsets of the current language eg. en vs en-GB
      if ($lit->getLang() != $this->lang && strpos($lit->getLang(), $this->lang . '-') !== 0)
        $labels[$lit->getLang()][] = new ConceptPropertyValue(
            'skos:altLabel',
            '',
            '',
            $lit->getLang(),
            $lit->getValue()
          );
    }
    ksort($labels);
    return $labels;
  }

}

/**
 * Class for handling concept properties.
 */
class ConceptProperty
{
  /** stores the property type */
  private $prop;
  /** stores the property label */
  private $label;
  /** stores the property values */
  private $values;

  /**
   * Label parameter seems to be optional in this phase.
   * @param string $prop property type eg. 'rdf:type'.
   * @param string $label
   * @param array $values contains ConceptPropertyValues
   */
  public function __construct($prop, $label, $values)
  {
    $this->prop = $prop;
    $this->label = $label;
    $this->values = $values;
  }

  /**
   * Gets the gettext translation for a property or returns the identifier as a fallback.
   */
  public function getLabel()
  {
    // first see if we have a translation
    $label = gettext($this->prop);
    if ($label != $this->prop) return $label;
    // if not, see if there was a label for the property in the graph
    if ($this->label) return $this->label;
    // when no label is found, don't show the property at all
    return null;
  }

  /**
   * Returns a gettext translation for the property tooltip.
   * @return string
   */
  public function getDescription()
  {
    $helpprop = $this->prop . "_help";

    return gettext($helpprop); // can't use string constant, it'd be picked up by xgettext
  }

  /**
   * Returns an array of the property values.
   * @return array containing ConceptPropertyValue objects.
   */
  public function getValues()
  {
    return $this->values;
  }

  /**
   * Returns property type as a string.
   * @return string eg. 'rdf:type'.
   */
  public function getType()
  {
    return $this->prop;
  }
}

/**
 * Class for handling concept property values.
 */
class ConceptPropertyValue
{
  /** language code of the value literal */
  private $lang;
  /** if the concept is inherited from a another vocabulary store that identifier here */
  private $exvocab;
  /** property type */
  private $type;
  /** literal value of the property */
  private $label;
  /** uri of the concept the property value belongs to */
  private $uri;
  /** id of the vocabulary the concept belongs to */
  private $vocab;
  /** vocabulary label */
  private $vocabName;
  /** if the property is a subProperty of a another property */
  private $parentProperty;
  private $submembers;

  public function __construct($prop, $uri, $vocab, $lang, $label, $exvocab = null, $parent = null, $vocabname = null)
  {
    $this->submembers = array();
    $this->lang = $lang;
    $this->exvocab = $exvocab;
    $this->type = $prop;
    $this->label = $label;
    $this->uri = $uri;
    $this->vocab = $vocab;
    $this->vocabName = $vocabname;
    $this->parentProperty = $parent;
  }

  public function __toString()
  {
    if ($this->label === null)
      return "";
    return $this->label;
  }

  public function getLang()
  {
    return $this->lang;
  }

  public function getExVocab()
  {
    return $this->exvocab;
  }

  public function getType()
  {
    return $this->type;
  }

  public function getLabel()
  {
    return $this->label;
  }

  public function getUri()
  {
    return $this->uri;
  }

  public function getParent()
  {
    return $this->parentProperty;
  }

  public function getVocab()
  {
    return $this->vocab;
  }
  
  public function getVocabName()
  {
    return $this->vocabName;
  }

  public function addSubMember($type, $label, $uri, $vocab, $lang, $exvocab = null)
  {
    $this->submembers[$label] = new ConceptPropertyValue($type, $uri, $vocab, $lang, $label, $exvocab = null);
    $this->sortSubMembers();
  }

  public function getSubMembers()
  {
    if (empty($this->submembers))
      return null;
    return $this->submembers;
  }

  public function sortSubMembers()
  {
    if (!empty($this->submembers))
      ksort($this->submembers);
  }

}
