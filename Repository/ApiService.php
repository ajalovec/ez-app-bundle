<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Repository;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;
use eZ\Publish\Core\QueryType\QueryType;
use eZ\Publish\Core\QueryType\QueryTypeRegistry;
use Origammi\Bundle\EzAppBundle\Service\Traits\ContentServiceInterface;
use Origammi\Bundle\EzAppBundle\Service\Traits\ContentServiceTrait;
use Origammi\Bundle\EzAppBundle\Service\Traits\ContentTypeServiceInterface;
use Origammi\Bundle\EzAppBundle\Service\Traits\ContentTypeServiceTrait;
use Origammi\Bundle\EzAppBundle\Service\Traits\LocationServiceInterface;
use Origammi\Bundle\EzAppBundle\Service\Traits\LocationServiceTrait;
use Origammi\Bundle\EzAppBundle\Service\Traits\RepositoryServiceTrait;
use Origammi\Bundle\EzAppBundle\Service\Traits\SearchServiceInterface;
use Origammi\Bundle\EzAppBundle\Service\Traits\SearchServiceTrait;

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
     * @param $id
     *
     * @return Location|Location[]
     */
    public function loadLocation($id)
    {
        return $this->locationService->load($id);
    }

    /**
     * @param $id
     *
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
     * @return Location[]|SearchResult
     */
    public function findLocations($queryType, array $parameters = [], $fetchArray = false)
    {
        $query = $this->createQuery($queryType, $parameters);
        $data  = $this->locationService->query($query, $fetchArray);

        return $data;
    }

    /**
     * @param string|Query|QueryType $queryType
     * @param array                  $parameters
     * @param bool                   $fetchArray
     *
     * @return Content[]|SearchResult
     */
    public function findContent($queryType, array $parameters = [], $fetchArray = false)
    {
        $query = $this->createQuery($queryType, $parameters);
        $data  = $this->contentService->query($query, $fetchArray);

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
