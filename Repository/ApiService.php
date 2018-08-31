<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Repository;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;
use eZ\Publish\API\Repository\Values\Content\VersionInfo;
use eZ\Publish\Core\QueryType\QueryType;
use eZ\Publish\Core\QueryType\QueryTypeRegistry;
use Origammi\Bundle\EzAppBundle\Repository\Traits\ContentServiceInterface;
use Origammi\Bundle\EzAppBundle\Repository\Traits\ContentServiceTrait;
use Origammi\Bundle\EzAppBundle\Repository\Traits\ContentTypeServiceInterface;
use Origammi\Bundle\EzAppBundle\Repository\Traits\ContentTypeServiceTrait;
use Origammi\Bundle\EzAppBundle\Repository\Traits\LocationServiceInterface;
use Origammi\Bundle\EzAppBundle\Repository\Traits\LocationServiceTrait;
use Origammi\Bundle\EzAppBundle\Repository\Traits\RepositoryServiceTrait;
use Origammi\Bundle\EzAppBundle\Repository\Traits\SearchServiceInterface;
use Origammi\Bundle\EzAppBundle\Repository\Traits\SearchServiceTrait;

/**
 * Class ApiService
 *
 * @package   Origammi\Bundle\EzAppBundle\Repository
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi AG (http://origammi.co)
 */
class ApiService implements SearchServiceInterface, LocationServiceInterface, ContentServiceInterface, ContentTypeServiceInterface
{
    use SearchServiceTrait;
    use LocationServiceTrait;
    use ContentServiceTrait;
    use ContentTypeServiceTrait;
    use RepositoryServiceTrait;

    /**
     * @var QueryTypeRegistry
     */
    protected $queryTypeService;

    /**
     * ApiService constructor.
     *
     * @param QueryTypeRegistry $queryTypeService
     */
    public function __construct(QueryTypeRegistry $queryTypeService)
    {
        $this->queryTypeService = $queryTypeService;
    }

    /**
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
    public function loadLocation($id)
    {
        return $this->locationService->load($id);
    }

    /**
     * @param $id
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @return Content|Content[]
     */
    public function loadContent($id)
    {
        return $this->contentService->load($id);
    }

    /**
     * @param string|Query|QueryType $queryType
     * @param array                  $parameters
     * @param bool                   $fetchArray
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType
     * @return Location[]|SearchResult
     */
    public function findLocations($queryType, array $parameters = [], $fetchArray = false)
    {
        $query = $this->createQuery($queryType, $parameters);
        $data  = $this->locationService->search($query, $fetchArray);

        return $data;
    }

    /**
     * @param string|Query|QueryType $queryType
     * @param array                  $parameters
     * @param bool                   $fetchArray
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentType
     * @return Content[]|SearchResult
     */
    public function findContent($queryType, array $parameters = [], $fetchArray = false)
    {
        $query = $this->createQuery($queryType, $parameters);
        $data  = $this->contentService->search($query, $fetchArray);

        return $data;
    }

    /**
     * @param string|Query|QueryType $type
     * @param array                  $parameters
     *
     * @return Query|LocationQuery
     */
    public function createQuery($type, array $parameters = [])
    {
        if ($type instanceof Query) {
            return $type;
        }

        $queryType = $type instanceof QueryType ? $type : $this->createQueryType($type);

        return $queryType->getQuery($parameters);
    }

    /**
     * @param $type
     *
     * @return \eZ\Publish\Core\QueryType\QueryType
     */
    public function createQueryType($type)
    {
        return $this->queryTypeService->getQueryType($type);
    }
}
