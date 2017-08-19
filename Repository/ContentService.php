<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Repository;

use eZ\Publish\API\Repository\ContentService as BaseContentService;
use eZ\Publish\API\Repository\Exceptions\PropertyNotFoundException;
use eZ\Publish\API\Repository\Exceptions\PropertyReadOnlyException;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;
use eZ\Publish\API\Repository\Values\Content\VersionInfo;
use Origammi\Bundle\EzAppBundle\QueryType\Core\QueryFactory;
use Origammi\Bundle\EzAppBundle\Traits\OrigammiEzRepositoryTrait;

/**
 * Class ContentService
 *
 * @package   Origammi\Bundle\EzAppBundle\Repository
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi AG (http://origammi.co)
 */
class ContentService
{
    use OrigammiEzRepositoryTrait;

    /**
     * @var BaseContentService
     */
    protected $contentService;

    /**
     * @var SearchService
     */
    protected $searchService;

    /**
     * ContentService constructor.
     *
     * @param BaseContentService $contentService
     * @param SearchService      $searchService
     */
    public function __construct(BaseContentService $contentService, SearchService $searchService)
    {
        $this->contentService = $contentService;
        $this->searchService  = $searchService;
    }

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
     * Try to resolve Content object from mixed $id argument
     * Accepted values:
     *  id        - int|string
     *  remote_id - string
     *  object    - Content|Location|VersionInfo|ContentInfo
     *
     * @param Content|Location|VersionInfo|ContentInfo|int|string|array $id
     *
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

        if ($id instanceof Location || $id instanceof VersionInfo) {
            $id = $id->contentInfo;
        }

        if ($id instanceof ContentInfo) {
            return $this->loadByContentInfo($id);
        }

        if ($primaryId = $this->resolveContentId($id)) {
            return $this->loadById($primaryId);
        }

        return $this->loadByRemoteId((string)$id);
    }

    /**
     * @param int $id
     *
     * @return Content
     */
    public function loadById($id)
    {
        return $this->contentService->loadContent((int)$id);
    }

    /**
     * @param string $id
     *
     * @return Content
     */
    public function loadByRemoteId($id)
    {
        return $this->contentService->loadContentByRemoteId((string)$id);
    }

    /**
     * @param ContentInfo $contentInfo
     *
     * @return Content
     */
    public function loadByContentInfo(ContentInfo $contentInfo)
    {
        return $this->contentService->loadContentByContentInfo($contentInfo);
    }

    /**
     * @param VersionInfo $versionInfo
     *
     * @return Content
     */
    public function loadByVersionInfo(VersionInfo $versionInfo)
    {
        return $this->contentService->loadContentByVersionInfo($versionInfo);
    }

    /**
     * @param array|SearchResult $ids
     *
     * @return Content[]
     */
    public function find($ids)
    {
        $contentIds = $this->resolveContentIds($ids);

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
     *
     * @return Content[]
     */
    public function findByParent(Location $location, array $allowed_content_types = null)
    {
        $queryFactory = QueryFactory::create()
            ->setSort($location->getSortClauses())
            ->addFilter(new Criterion\ParentLocationId($location->id))
            ->setAllowedContentTypes($allowed_content_types);
        ;

        $searchResult = $this->searchService->findLocations($queryFactory->createLocationQuery());

        return $this->find($searchResult);
    }

    /**
     * @param Location   $location
     * @param array|null $allowed_content_types List of contentTypeIdentifiers to whitelist
     *
     * @return Content[]
     */
    public function findBySubtree(Location $location, array $allowed_content_types = null)
    {
        $queryFactory = QueryFactory::create()
            ->setSort($location->getSortClauses())
            ->addFilter(new Criterion\Subtree($location->pathString))
            ->addFilter(new Criterion\Location\Depth(Criterion\Operator::GT, $location->depth))
            ->setAllowedContentTypes($allowed_content_types);
        ;

        $searchResult = $this->searchService->findLocations($queryFactory->createLocationQuery());

        return $this->find($searchResult);
    }

    /**
     * @param Query $query
     * @param bool  $fetchArray
     *
     * @return Content[]|SearchResult
     */
    public function query(Query $query, $fetchArray = false)
    {
        $searchResult = $this->searchService->findContent($query);

        if (true === $fetchArray) {
            return $this->searchResultToArray($searchResult);
        }

        return $searchResult;
    }

}
