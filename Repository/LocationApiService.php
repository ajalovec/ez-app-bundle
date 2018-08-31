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
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use Origammi\Bundle\EzAppBundle\QueryType\Core\QueryFactory;
use Origammi\Bundle\EzAppBundle\Service\LanguageResolver;
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
    protected $languageResolver;

    /**
     * LocationService constructor.
     *
     * @param LocationService  $locationService
     * @param SearchService    $searchService
     * @param LanguageResolver $languageResolver
     */
    public function __construct(LocationService $locationService, SearchService $searchService, LanguageResolver $languageResolver)
    {
        $this->locationService  = $locationService;
        $this->searchService    = $searchService;
        $this->languageResolver = $languageResolver;
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
     * @param bool|null                                           $isAvailable
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @return Location|Location[]
     */
    public function load($id, $isAvailable = null)
    {
        if (is_array($id)) {
            $locations = [];
            foreach ($id as $i) {
                if (!$location = $this->load($i, $isAvailable)) {
                    continue;
                }
                $locations[] = $location;
            }

            return $locations;
        }

        if ($id instanceof Location) {
            return $id;
        }

        if ($primaryId = RepositoryUtil::resolveLocationId($id)) {
            return $this->loadById($primaryId, $isAvailable);
        }

        return $this->loadByRemoteId($id, $isAvailable);
    }

    /**
     * @param int       $id
     * @param bool|null $isAvailable
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @return Location|null
     */
    public function loadById($id, $isAvailable = null)
    {
        $location = $this->locationService->loadLocation((int)$id);

        if (is_bool($isAvailable) && $isAvailable !== $this->isAvailable($location)) {
            return null;
        }

        return $location;
    }

    /**
     * @param string    $id
     * @param bool|null $isAvailable
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @return Location|null
     */
    public function loadByRemoteId($id, $isAvailable = null)
    {
        $location = $this->locationService->loadLocationByRemoteId((string)$id);

        if (is_bool($isAvailable) && $isAvailable !== $this->isAvailable($location)) {
            return null;
        }

        return $location;
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
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotImplementedException
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType
     * @return Location[]
     */
    public function findByParent(Location $location, array $allowed_content_types = null, $limit = null, $offset = null)
    {
        $queryFactory = QueryFactory::create()
            ->setSort($location->getSortClauses())
            ->addFilter(new Criterion\ParentLocationId($location->id))
            ->setAllowedContentTypes($allowed_content_types)
        ;

        if (is_int($limit)) {
            $queryFactory->setLimit($limit);
        }

        if (is_int($offset)) {
            $queryFactory->setOffset($offset);
        }

        return $this->search($queryFactory->createLocationQuery(), true);
    }

    /**
     * @param Location   $location
     * @param array|null $allowed_content_types
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotImplementedException
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType
     * @return Location[]
     */
    public function findBySubtree(Location $location, array $allowed_content_types = null, $limit = null, $offset = null)
    {
        $queryFactory = QueryFactory::create()
//            ->addSort(new Query\SortClause\Location\Path(Query::SORT_ASC))
//            ->addSort(new Query\SortClause\Location\Priority(Query::SORT_ASC))
            ->addSorts($location->getSortClauses())
            ->addFilter(new Criterion\Subtree($location->pathString))
            ->addFilter(new Criterion\Location\Depth(Criterion\Operator::GT, $location->depth))
            ->setAllowedContentTypes($allowed_content_types)
        ;

        if (is_int($limit)) {
            $queryFactory->setLimit($limit);
        }

        if (is_int($offset)) {
            $queryFactory->setOffset($offset);
        }

        return $this->search($queryFactory->createLocationQuery(), true);
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

        return $this->search($queryFactory->createLocationQuery(), true);
    }

    /**
     * @param LocationQuery $query
     * @param bool          $fetchArray
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType
     * @return Location[]|SearchResult
     */
    public function search(LocationQuery $query, $fetchArray = false)
    {
        $searchResult = $this->searchService->findLocations($query);

        if (true === $fetchArray) {
            return RepositoryUtil::searchResultToArray($searchResult);
        }

        return $searchResult;
    }


    /**
     * @param Location    $location
     * @param string|null $language
     *
     * @return bool
     */
    public function isAvailable(Location $location, $language = null)
    {
        return $location->contentInfo->isPublished() && !$location->hidden && $this->languageResolver->isLocationLangAvailable($location, $language);
    }


    /**
     * @param Location    $location
     * @param string|null $language
     *
     * @throws NotFoundException
     * @return Location
     */
    public function isAvailableException(Location $location, $language = null)
    {
        if (!$this->isAvailable($location, $language)) {
            throw new NotFoundException('Location', "id: {$location->id}, language: {$this->languageResolver->getLanguage()}, siteaccess: {$this->languageResolver->getSiteAccessName()}");
        }

        return $location;
    }

}
