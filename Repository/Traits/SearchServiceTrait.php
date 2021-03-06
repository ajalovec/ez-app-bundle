<?php

namespace Origammi\Bundle\EzAppBundle\Repository\Traits;

use eZ\Publish\API\Repository\SearchService;

/**
 * Trait SearchServiceTrait
 * @package   Origammi\Bundle\EzAppBundle\Repository\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
trait SearchServiceTrait
{
    /**
     * @var SearchService
     */
    protected $searchService;

    /**
     * @required
     *
     * @param SearchService $searchService
     */
    public function setSearchService(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * @return SearchService
     */
    public function getSearchService()
    {
        return $this->searchService;
    }
}
