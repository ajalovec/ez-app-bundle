<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Service\Traits;

use eZ\Publish\API\Repository\SearchService;

/**
 * Trait SearchServiceInterface
 * @package   Origammi\Bundle\EzAppBundle\Service\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
interface SearchServiceInterface
{
    /**
     * @param SearchService|null $searchService
     */
    public function setSearchService(SearchService $searchService = null);
}
