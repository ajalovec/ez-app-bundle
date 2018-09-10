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
use Origammi\Bundle\EzAppBundle\Service\LanguageResolver;
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
     * @var LanguageResolver
     */
    protected $languageResolver;

    public function __construct(ContentService $contentService, SearchService $searchService, LanguageResolver $languageResolver)
    {
        $this->contentService   = $contentService;
        $this->searchService    = $searchService;
        $this->languageResolver = $languageResolver;
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
    public function load($id, $languageCode = null)
    {
        if (is_array($id)) {
            $result = [];
            foreach ($id as $i) {
                if ($content = $this->load($i, $languageCode)) {
                    $result[] = $content;
                }
            }

            return $result;
        }

        if ($id instanceof Content) {
            return $id;
        } elseif ($id instanceof ContentInfo) {
            return $this->loadByContentInfo($id, $languageCode);
        } elseif ($id instanceof VersionInfo) {
            return $this->loadByVersionInfo($id, $languageCode);
        }

        if ($primaryId = RepositoryUtil::resolveContentId($id)) {
            return $this->loadById($id, $languageCode);
        }

        return $this->loadByRemoteId($id, $languageCode);
    }


    /**
     * @param int                  $id
     * @param string|string[]|bool $languageCode
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @return Content
     */
    public function loadById($id, $languageCode = null)
    {
        return $this->contentService->loadContent((int)$id, true === $languageCode ? $this->languageResolver->getLanguages() : $languageCode);
    }


    /**
     * @param string               $id
     * @param string|string[]|bool $languageCode
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @return Content
     */
    public function loadByRemoteId($id, $languageCode = null)
    {
        return $this->contentService->loadContentByRemoteId((string)$id, true === $languageCode ? $this->languageResolver->getLanguages() : $languageCode);
    }


    /**
     * @param ContentInfo          $contentInfo
     * @param string|string[]|bool $languageCode
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @return Content
     */
    public function loadByContentInfo(ContentInfo $contentInfo, $languageCode = null)
    {
        return $this->contentService->loadContentByContentInfo($contentInfo, true === $languageCode ? $this->languageResolver->getLanguages() : $languageCode);
    }


    /**
     * @param VersionInfo          $versionInfo
     * @param string|string[]|bool $languageCode
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @return Content
     */
    public function loadByVersionInfo(VersionInfo $versionInfo, $languageCode = null)
    {
        return $this->contentService->loadContentByVersionInfo($versionInfo, true === $languageCode ? $this->languageResolver->getLanguages() : $languageCode);
    }


    /**
     * @param array|SearchResult|LocationList $ids
     * @param int                             $limit
     * @param int                             $offset
     * @param string|string[]|bool            $languageCode Either specific language code or true for current resolved language
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @return Content[]
     */
    public function findByIds($ids, $limit = null, $offset = null, $languageCode = null)
    {
        $contentIds = RepositoryUtil::resolveContentIds($ids);

        if (is_array($contentIds) && count($contentIds) === 0) {
            return [];
        }

        $queryFactory = QueryFactory::create()
            ->addFilter(new Criterion\ContentId($contentIds))
        ;

        if ($languageCode) {
            $queryFactory->setLanguage(true === $languageCode ? $this->languageResolver->getLanguages() : $languageCode);
        }

        if (is_int($limit)) {
            $queryFactory->setLimit($limit);
        }

        if (is_int($offset)) {
            $queryFactory->setOffset($offset);
        }

        $searchResult = $this->searchService->findContent($queryFactory->createContentQuery());

        foreach ($searchResult->searchHits as $searchHit) {
            $contentIds[$searchHit->valueObject->id] = $searchHit->valueObject;
        }

        return array_values($contentIds);
    }

    /**
     * @param Location             $location
     * @param array|string         $allowed_content_types List of contentTypeIdentifiers to whitelist
     * @param int                  $limit
     * @param int                  $offset
     * @param string|string[]|bool $languageCode
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotImplementedException
     * @return Content[]
     */
    public function findByParent(Location $location, $allowed_content_types = null, $limit = null, $offset = null, $languageCode = null)
    {
        $queryFactory = QueryFactory::create()
            ->setSort($location->getSortClauses())
            ->addFilter(new Criterion\ParentLocationId($location->id))
            ->setAllowedContentTypes((array)$allowed_content_types)
        ;

        if ($languageCode) {
            $queryFactory->setLanguage(true === $languageCode ? $this->languageResolver->getLanguages() : $languageCode);
        }

        if (is_int($limit)) {
            $queryFactory->setLimit($limit);
        }

        if (is_int($offset)) {
            $queryFactory->setOffset($offset);
        }

        $searchResult = $this->searchService->findLocations($queryFactory->createLocationQuery());

        return $searchResult->totalCount ? $this->findByIds($searchResult, $limit, $offset) : [];
    }

    /**
     * @param Location             $location
     * @param array|string         $allowed_content_types List of contentTypeIdentifiers to whitelist
     * @param int                  $limit
     * @param int                  $offset
     * @param string|string[]|bool $languageCode
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotImplementedException
     * @return Content[]
     */
    public function findBySubtree(Location $location, $allowed_content_types = null, $limit = null, $offset = null, $languageCode = null)
    {
        $queryFactory = QueryFactory::create()
            ->setSort($location->getSortClauses())
            ->addFilter(new Criterion\Subtree($location->pathString))
            ->addFilter(new Criterion\Location\Depth(Criterion\Operator::GT, $location->depth))
            ->setAllowedContentTypes((array)$allowed_content_types)
        ;

        if ($languageCode) {
            $queryFactory->setLanguage(true === $languageCode ? $this->languageResolver->getLanguages() : $languageCode);
        }

        if (is_int($limit)) {
            $queryFactory->setLimit($limit);
        }

        if (is_int($offset)) {
            $queryFactory->setOffset($offset);
        }

        $searchResult = $this->searchService->findLocations($queryFactory->createLocationQuery());

        return $searchResult->totalCount ? $this->findByIds($searchResult, $limit, $offset) : [];
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

    /**
     * @param Query $query
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @return int
     */
    public function count(Query $query)
    {
        $query->performCount = true;
        $searchResult        = $this->searchService->findContentInfo($query);

        return $searchResult->totalCount;
    }

}
