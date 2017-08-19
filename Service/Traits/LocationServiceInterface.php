<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Service\Traits;

use Origammi\Bundle\EzAppBundle\Repository\LocationService;

/**
 * Trait ContentServiceInterface
 * @package   Origammi\Bundle\EzAppBundle\Service\Traits
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
