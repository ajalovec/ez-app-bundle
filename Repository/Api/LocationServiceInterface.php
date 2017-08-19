<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Repository\Api;

use Origammi\Bundle\EzAppBundle\Repository\LocationService;

/**
 * Trait ContentServiceInterface
 * @package   Origammi\Bundle\EzAppBundle\Repository\Api
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
interface LocationServiceInterface
{
    /**
     * @param LocationService|null $locationService
     */
    public function setLocationService(LocationService $locationService = null);
}
