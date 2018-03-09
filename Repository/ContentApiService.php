<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Repository;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\LocationList;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;
use eZ\Publish\API\Repository\Values\Content\VersionInfo;
use Origammi\Bundle\EzAppBundle\QueryType\Core\QueryFactory;
use Origammi\Bundle\EzAppBundle\Utils\RepositoryUtil;

/**
 * Class ContentService
 *
 * @package   Origammi\Bundle\EzAppBundle\Repository
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi AG (http://origammi.co)
 */
class ContentApiService
{
    /**
     * @var ContentService
     */
    protected $contentService;

    /**
     * @var SearchService
     */
    protected $searchService;

    /**
     * ContentService constructor.
     *
     * @param ContentService $contentService
     * @param SearchService      $searchService
     */
    public function __construct(ContentService $contentService, SearchService $searchService)
    {
        $this->contentService = $contentService;
        $this->searchService  = $searchService;
    }

    /**
     * @return ContentService
     */
    public function getService()
    {
        return $this->contentService;
    }


    /**
     * Try to resolve Content object from mixed $id argument
     * Accepted values:
     *  id        - int|string
     *  remote_id - string
     *  object    - Content|Location|VersionInfo|ContentInfo
     *  array     - array of above types
     *
     * @param Content|Location|VersionInfo|ContentInfo|int|string|array $id
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @return Content|Content[]
     */
    public function load($id)
    {
        if (is_array($id)) {
            $content = [];
            foreach ($id as $i) {
                $content[] = $this->load($i);
            }

            return $content;
        }

        if ($id instanceof Content) {
            return $id;
        }

        if ($primaryId = RepositoryUtil::resolveContentId($id)) {
            return $this->contentService->loadContent($primaryId);
        }

        return $this->contentService->loadContentByRemoteId((string)$id);
    }

    /**
     * @param array|SearchResult|LocationList $ids
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @return Content[]
     */
    public function findByIds($ids)
    {
        $contentIds = RepositoryUtil::resolveContentIds($ids);

        if (is_array($contentIds) && count($contentIds) === 0) {
            return [];
        }

        $queryFactory = QueryFactory::create()
            ->addFilter(new Criterion\ContentId($contentIds))
        ;

        if (!empty($allowed_content_types)) {
            $queryFactory->setAllowedContentTypes($allowed_content_types);
        }

        $searchResult = $this->searchService->findContent($queryFactory->createContentQuery());

        foreach ($searchResult->searchHits as $searchHit) {
            $contentIds[$searchHit->valueObject->id] = $searchHit->valueObject;
        }

        return array_values($contentIds);
    }

    /**
     * @param Location   $location
     * @param array|null $allowed_content_types List of contentTypeIdentifiers to whitelist
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotImplementedException
     * @return Content[]
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

        $searchResult = $this->searchService->findLocations($queryFactory->createLocationQuery());

        return $searchResult->totalCount ? $this->findByIds($searchResult) : [];
    }

    /**
     * @param Location   $location
     * @param array|null $allowed_content_types List of contentTypeIdentifiers to whitelist
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotImplementedException
     * @return Content[]
     */
    public function findBySubtree(Location $location, array $allowed_content_types = null, $limit = null, $offset = null)
    {
        $queryFactory = QueryFactory::create()
            ->setSort($location->getSortClauses())
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

        $searchResult = $this->searchService->findLocations($queryFactory->createLocationQuery());

        return $searchResult->totalCount ? $this->findByIds($searchResult) : [];
    }

    /**
     * @param Query $query
     * @param bool  $fetchArray
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType
     * @return Content[]|SearchResult
     */
    public function search(Query $query, $fetchArray = false)
    {
        $searchResult = $this->searchService->findContent($query);

        if (true === $fetchArray) {
            return RepositoryUtil::searchResultToArray($searchResult);
        }

        return $searchResult;
    }

}
