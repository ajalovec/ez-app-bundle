<?php

namespace Origammi\Bundle\EzAppBundle\Repository\Traits;

use Origammi\Bundle\EzAppBundle\Repository\LocationApiService;

/**
 * Trait ContentServiceInterface
 * @package   Origammi\Bundle\EzAppBundle\Repository\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
interface LocationServiceInterface
{
    /**
     * @param LocationApiService $locationService
     */
    public function setLocationService(LocationApiService $locationService);
}
