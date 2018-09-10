<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\QueryType\Core;


use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;

/**
 * Class QueryFactory
 *
 * @package   Origammi\Bundle\EzAppBundle\QueryType\Core
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class QueryFactory
{
    /**
     * @var array
     */
    protected $parameters = [
        'offset'       => 0,
        'limit'        => 25,
        'sortClauses'  => [],
        'performCount' => true,
    ];

    /**
     * @var bool|null
     */
    protected $visible = true;

    /**
     * @var string|null
     */
    protected $language;

    /**
     * @var Criterion[]
     */
    protected $filters = [];

    /**
     * @var array
     */
    protected $allowedContentTypes = [];


    public function __construct(array $parameters = [])
    {
        foreach ($parameters as $property => $value) {
            switch ($property) {
                case 'offset':
                case 'limit':
                case 'performCount':
                case 'allowedContentTypes':
                case 'filters':
                case 'sort':
                    $this->{'set' . ucfirst($property)}($value);
                    break;
            }
//            $this->{'set'.ucfirst($property)} = $value;
        }
    }

    /**
     * @param array $parameters
     *
     * @return self
     */
    public static function create(array $parameters = [])
    {
        return new self($parameters);
    }

    /**
     * @return LocationQuery
     */
    public function createLocationQuery()
    {
        return new LocationQuery($this->createQueryParameters());
    }

    /**
     * @return Query
     */
    public function createContentQuery()
    {
        return new Query($this->createQueryParameters());
    }

    /**
     * @return array
     */
    public function createQueryParameters()
    {
        $filters = $this->filters;

        if (is_bool($this->visible)) {
            $filters[] = new Criterion\Visibility($this->visible ? Criterion\Visibility::VISIBLE : Criterion\Visibility::HIDDEN);
        }

        if (!empty($this->language) && is_string($this->language) || is_array($this->language)) {
            $filters[] = new Criterion\LanguageCode($this->language);
        }

        if (!empty($this->allowedContentTypes)) {
            $filters[] = new Criterion\ContentTypeIdentifier($this->allowedContentTypes);
        }

        $parameters           = $this->parameters;
        $parameters['filter'] = new Criterion\LogicalAnd($filters);

        return $parameters;
    }

    /**
     * @param null|Criterion[] $filters
     *
     * @return $this
     */
    public function setFilters(array $filters = null)
    {
        $this->filters = [];
        $this->addFilters($filters);

        return $this;
    }

    /**
     * @param Criterion $filter
     *
     * @return $this
     */
    public function addFilter(Criterion $filter)
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * @param null|Criterion[] $filters
     *
     * @return $this
     */
    public function addFilters(array $filters = null)
    {
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                $this->addFilter($filter);
            }
        }

        return $this;
    }

    /**
     * @param array $allowedContentTypes
     *
     * @return $this
     */
    public function setAllowedContentTypes(array $allowedContentTypes = null)
    {
        $this->allowedContentTypes = [];
        $this->addAllowedContentTypes($allowedContentTypes);

        return $this;
    }

    /**
     * @param string $allowedContentType
     *
     * @return $this
     */
    public function addAllowedContentType($allowedContentType)
    {
        $this->allowedContentTypes[] = (string)$allowedContentType;

        return $this;
    }

    /**
     * @param array $allowedContentTypes
     *
     * @return $this
     */
    public function addAllowedContentTypes(array $allowedContentTypes = null)
    {
        if (!empty($allowedContentTypes)) {
            foreach ($allowedContentTypes as $allowedContentType) {
                $this->addAllowedContentType($allowedContentType);
            }
        }

        return $this;
    }

    /**
     * @param null|SortClause[] $sortClauses
     *
     * @return $this
     */
    public function setSort(array $sortClauses = null)
    {
        $this->parameters['sortClauses'] = [];
        $this->addSorts($sortClauses);

        return $this;
    }

    /**
     * @param SortClause $sortClause
     *
     * @return $this
     */
    public function addSort(SortClause $sortClause)
    {
        $this->parameters['sortClauses'][] = $sortClause;

        return $this;
    }

    /**
     * @param SortClause[] $sortClauses
     *
     * @return $this
     */
    public function addSorts(array $sortClauses = null)
    {
        if (!empty($sortClauses)) {
            foreach ($sortClauses as $sortClause) {
                $this->addSort($sortClause);
            }
        }

        return $this;
    }

    /**
     * @param int|string $offset
     *
     * @return $this
     */
    public function setOffset($offset)
    {
        $this->parameters['offset'] = (int)$offset;

        return $this;
    }

    /**
     * @param int|string $limit
     *
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->parameters['limit'] = (int)$limit;

        return $this;
    }

    /**
     * @param bool|null $visible
     *
     * @return $this
     */
    public function setVisible($visible = null)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * @param string|string[]|null $language
     *
     * @return $this
     */
    public function setLanguage($language = null)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @param bool $performCount
     *
     * @return $this
     */
    public function setPerformCount($performCount)
    {
        $this->parameters['performCount'] = (bool)$performCount;

        return $this;
    }

    /**
     * @return SortClause[]
     */
    public function getSort()
    {
        return isset($this->parameters['sortClauses']) ? $this->parameters['sortClauses'] : null;
    }

    /**
     * @return null|int
     */
    public function getOffset()
    {
        return isset($this->parameters['offset']) ? $this->parameters['offset'] : null;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return isset($this->parameters['limit']) ? $this->parameters['limit'] : null;
    }

    /**
     * @return bool
     */
    public function getPerformCount()
    {
        return isset($this->parameters['performCount']) ? $this->parameters['performCount'] : null;
    }
}
