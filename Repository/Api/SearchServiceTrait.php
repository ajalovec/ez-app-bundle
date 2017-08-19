<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Repository\Api;

use eZ\Publish\API\Repository\SearchService;

/**
 * Trait SearchServiceTrait
 * @package   Origammi\Bundle\EzAppBundle\Repository\Api
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
     * @param SearchService|null $searchService
     */
    public function setSearchService(SearchService $searchService = null)
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
