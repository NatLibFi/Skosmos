<?php

/**
 * Dataobject wraps EasyRdf resources and provides access to the data.
 */
class DataObject
{
    /**
     * Preferred order of properties, to be set by subclasses
     */
    protected $order;
    /**
     * Model instance which created this object
     */
    protected $model;
    /**
     * EasyRdf resource representing this object
     */
    protected $resource;

    /**
     * Initializes the DataObject
     * @param Model $model
     * @param EasyRdf\Resource $resource
     * @throws Exception
     */
    public function __construct($model, $resource)
    {
        if (!($model instanceof Model) || !($resource instanceof EasyRdf\Resource)) {
            throw new InvalidArgumentException('Invalid constructor parameter given to DataObject.');
        }

        $this->model = $model;
        $this->resource = $resource;
        $this->order = array();
    }

    /**
     * Generates and makes a query into a external vocabulary for an exact
     * match for a particular concept.
     * @param Vocabulary $exvoc external vocabulary to query
     * @param string $exuri resource URI
     * @param string $lang language of label to query for
     * @return EasyRdf\Literal label, or null if not found in vocabulary
     */
    protected function getExternalLabel($exvoc, $exuri, $lang)
    {
        if ($exvoc) {
            $exsparql = $exvoc->getSparql();
            $results = $exsparql->queryLabel($exuri, $lang);

            return isset($results[$lang]) ? $results[$lang] : null;
        }
        return null;
    }

    /**
     * Generates and makes a query into a external vocabulary for the notation of an exact
     * match for a particular concept.
     * @param Vocabulary $exvoc external vocabulary to query
     * @param string $exuri resource URI
     */
    protected function getExternalNotation($exvoc, $exuri)
    {
        if ($exvoc) {
            $exsparql = $exvoc->getSparql();
            $results = $exsparql->queryNotation($exuri);
            return isset($results) ? $results : null;
        }
        return null;
    }

    /**
     * Sorting the result list to a arbitrary order defined below in mycompare()
     * @param array $sortable
     */
    protected function arbitrarySort($sortable)
    {
        // sorting the result list to a arbitrary order defined below in mycompare()
        if ($sortable !== null) {
            uksort($sortable, array($this, 'mycompare'));
            foreach ($sortable as $prop => $vals) {
                if (is_array($prop)) // the ConceptProperty objects have their own sorting methods
                {
                    ksort($sortable[$prop]);
                }
            }
        }
        return $sortable;
    }

    /**
     * Compares the given objects and returns -1 or 1 depending which ought to be first.
     * $order defines the priorities of the different properties possible in the array.
     * @param string $a the first item to be compared
     * @param string $b the second item to be compared
     */
    protected function mycompare($a, $b)
    {
        if ($a === $b) {
            return 0;
        }
        $order = $this->order;
        $position = array_search($a, $order);
        $position2 = array_search($b, $order);

        //if both are in the $order, then sort according to their order in $order...
        if ($position2 !== false && $position !== false) {
            return ($position < $position2) ? -1 : 1;
        }
        //if only one is in $order, then sort to put the one in $order first...
        if ($position !== false) {
            return -1;
        }
        if ($position2 !== false) {
            return 1;
        }

        //if neither in $order, then a simple alphabetic sort...
        return ($a < $b) ? -1 : 1;
    }

    /**
     * Getter function to retrieve the ui language from the locale.
     */
    public function getEnvLang()
    {
       // get language from locale, same as used by gettext, set by Controller
       return substr(getenv("LC_ALL"), 0, 2); // @codeCoverageIgnore
    }

    /**
     * Getter function for retrieving the resource.
     */
    public function getResource()
    {
        return $this->resource;
    }
}
