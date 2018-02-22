<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Repository;

use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\LocationList;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use Origammi\Bundle\EzAppBundle\QueryType\Core\QueryFactory;
use Origammi\Bundle\EzAppBundle\Utils\RepositoryUtil;

/**
 * Class LocationService
 *
 * @package   Origammi\Bundle\EzAppBundle\Repository
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi AG (http://origammi.co)
 */
class LocationApiService
{
    /**
     * @var LocationService
     */
    protected $locationService;

    /**
     * @var SearchService
     */
    protected $searchService;

    /**
     * LocationService constructor.
     *
     * @param LocationService $locationService
     * @param SearchService       $searchService
     */
    public function __construct(LocationService $locationService, SearchService $searchService)
    {
        $this->locationService = $locationService;
        $this->searchService   = $searchService;
    }


    /**
     * @return LocationService
     */
    public function getService()
    {
        return $this->locationService;
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
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
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

        if ($primaryId = RepositoryUtil::resolveLocationId($id)) {
            return $this->loadById($primaryId);
        }

        return $this->loadByRemoteId((string)$id);
    }

    /**
     * @param int $id
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @return Location
     */
    public function loadById($id)
    {
        return $this->locationService->loadLocation((int)$id);
    }

    /**
     * @param string $id
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @return Location
     */
    public function loadByRemoteId($id)
    {
        return $this->locationService->loadLocationByRemoteId((string)$id);
    }

    /**
     * Loads Location objects for given
     *
     * @param Content|Location|VersionInfo|ContentInfo|string|int|array|SearchResult|LocationList $ids
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @return Location[]
     */
    public function findByIds($ids)
    {
        $locationIds = RepositoryUtil::resolveLocationIds($ids);

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
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotImplementedException
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType
     * @return Location[]
     */
    public function findByParent(Location $location, array $allowed_content_types = null)
    {
        $queryFactory = QueryFactory::create()
            ->setSort($location->getSortClauses())
            ->addFilter(new Criterion\ParentLocationId($location->id))
            ->setAllowedContentTypes($allowed_content_types)
        ;;

        return $this->query($queryFactory->createLocationQuery(), true);
    }

    /**
     * @param Location   $location
     * @param array|null $allowed_content_types
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotImplementedException
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType
     * @return Location[]
     */
    public function findBySubtree(Location $location, array $allowed_content_types = null)
    {
        $queryFactory = QueryFactory::create()
//            ->addSort(new Query\SortClause\Location\Path(Query::SORT_ASC))
//            ->addSort(new Query\SortClause\Location\Priority(Query::SORT_ASC))
            ->addSorts($location->getSortClauses())
            ->addFilter(new Criterion\Subtree($location->pathString))
            ->addFilter(new Criterion\Location\Depth(Criterion\Operator::GT, $location->depth))
            ->setAllowedContentTypes($allowed_content_types)
        ;;

        return $this->query($queryFactory->createLocationQuery(), true);
    }


    /**
     * @param string|array $allowed_content_type
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType
     * @return Location[]
     */
    public function findByContentType($allowed_content_type)
    {
        if (!is_array($allowed_content_type)) {
            $allowed_content_type = [ (string)$allowed_content_type ];
        }

        $queryFactory = QueryFactory::create()
            ->addSort(new Query\SortClause\Location\Path(Query::SORT_ASC))
//            ->addSort(new Query\SortClause\Location\Priority(Query::SORT_ASC))
            ->addFilter(new Criterion\Location\Depth(Criterion\Operator::GT, 1))
            ->setAllowedContentTypes($allowed_content_type)
        ;;

        return $this->query($queryFactory->createLocationQuery(), true);
    }

    /**
     * @param LocationQuery $query
     * @param bool          $fetchArray
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType
     * @return Location[]|SearchResult
     */
    public function query(LocationQuery $query, $fetchArray = false)
    {
        $searchResult = $this->searchService->findLocations($query);

        if (true === $fetchArray) {
            return RepositoryUtil::searchResultToArray($searchResult);
        }

        return $searchResult;
    }
}
