<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Repository;

use eZ\Publish\API\Repository\Exceptions\PropertyNotFoundException;
use eZ\Publish\API\Repository\Exceptions\PropertyReadOnlyException;
use eZ\Publish\API\Repository\LocationService as BaseLocationService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use Origammi\Bundle\EzAppBundle\QueryType\Core\QueryFactory;
use Origammi\Bundle\EzAppBundle\Traits\OrigammiEzRepositoryTrait;

/**
 * Class LocationService
 *
 * @package   Origammi\Bundle\EzAppBundle\Repository
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi AG (http://origammi.co)
 */
class LocationService
{
    use OrigammiEzRepositoryTrait;

    /**
     * @var BaseLocationService
     */
    protected $locationService;

    /**
     * @var SearchService
     */
    protected $searchService;

    /**
     * LocationService constructor.
     *
     * @param BaseLocationService $locationService
     * @param SearchService       $searchService
     */
    public function __construct(BaseLocationService $locationService, SearchService $searchService)
    {
        $this->locationService = $locationService;
        $this->searchService   = $searchService;
    }

//    public function __call($name, $arguments)
//    {
//        if (method_exists($this->locationService, $name)) {
//            return call_user_func_array([$this->locationService, $name], $arguments);
//        }
//    }

    public function __get($property)
    {
        switch ($property) {
            case 'api':
                return $this->locationService;
        }

        throw new PropertyNotFoundException($property, get_class($this));
    }

    public function __set($property, $value)
    {
        throw new PropertyReadOnlyException($property, get_class($this));
    }

    /**
     * Try to resolve Location object from mixed $id argument
     * Accepted values:
     *  id        - int|string
     *  remote_id - string
     *  object    - Content|Location|VersionInfo|ContentInfo
     *
     * @param Content|Location|VersionInfo|ContentInfo|int|string $id
     *
     * @return Location|Location[]
     */
    public function load($id)
    {
        if (is_array($id)) {
            $locations = [];
            foreach ($id as $i) {
                $locations[] = $this->load($i);
            }

            return $locations;
        }

        if ($id instanceof Location) {
            return $id;
        }

        if ($primaryId = $this->resolveLocationId($id)) {
            return $this->loadById($primaryId);
        }

        return $this->loadByRemoteId((string)$id);
    }

    /**
     * @param int $id
     *
     * @return Location
     */
    public function loadById($id)
    {
        return $this->locationService->loadLocation((int)$id);
    }

    /**
     * @param string $id
     *
     * @return Location
     */
    public function loadByRemoteId($id)
    {
        return $this->locationService->loadLocationByRemoteId((string)$id);
    }

    /**
     * @param array|SearchResult $ids
     *
     * @return Location[]
     */
    public function find($ids)
    {
        $locationIds = $this->resolveLocationIds($ids);

        $queryFactory = QueryFactory::create()
            ->addFilter(new Criterion\LocationId($locationIds))
        ;

        if (!empty($allowed_content_types)) {
            $queryFactory->setAllowedContentTypes($allowed_content_types);
        }

        $searchResult = $this->searchService->findLocations($queryFactory->createLocationQuery());

        foreach ($searchResult->searchHits as $searchHit) {
            $locationIds[$searchHit->valueObject->id] = $searchHit->valueObject;
        }

        return array_values($locationIds);
    }

    /**
     * @param Location   $location
     * @param array|null $allowed_content_types
     *
     * @return array
     */
    public function findByParent(Location $location, array $allowed_content_types = null)
    {
        $queryFactory = QueryFactory::create()
            ->setSort($location->getSortClauses())
            ->addFilter(new Criterion\ParentLocationId($location->id))
            ->setAllowedContentTypes($allowed_content_types);
        ;

        return $this->query($queryFactory->createLocationQuery(), true);
    }

    /**
     * @param Location   $location
     * @param array|null $allowed_content_types
     *
     * @return array
     */
    public function findBySubtree(Location $location, array $allowed_content_types = null)
    {
        $queryFactory = QueryFactory::create()
//            ->addSort(new Query\SortClause\Location\Path(Query::SORT_ASC))
//            ->addSort(new Query\SortClause\Location\Priority(Query::SORT_ASC))
            ->addSorts($location->getSortClauses())
            ->addFilter(new Criterion\Subtree($location->pathString))
            ->addFilter(new Criterion\Location\Depth(Criterion\Operator::GT, $location->depth))
            ->setAllowedContentTypes($allowed_content_types);
        ;

        return $this->query($queryFactory->createLocationQuery(), true);
    }

    /**
     * @param LocationQuery $query
     * @param bool          $fetchArray
     *
     * @return Location[]|SearchResult
     */
    public function query(LocationQuery $query, $fetchArray = false)
    {
        $searchResult = $this->searchService->findLocations($query);

        if (true === $fetchArray) {
            return $this->searchResultToArray($searchResult);
        }

        return $searchResult;
    }
}
