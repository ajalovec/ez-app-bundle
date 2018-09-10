<?php

namespace Origammi\Bundle\EzAppBundle\Repository\Traits;

use eZ\Publish\API\Repository\SearchService;

/**
 * Trait SearchServiceInterface
 * @package   Origammi\Bundle\EzAppBundle\Repository\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
interface SearchServiceInterface
{
    /**
     * @required
     *
     * @param SearchService $searchService
     */
    public function setSearchService(SearchService $searchService);
}
