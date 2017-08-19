<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Service\Traits;

use Origammi\Bundle\EzAppBundle\Repository\LocationService;

/**
 * Trait LocationServiceTrait
 * @package   Origammi\Bundle\EzAppBundle\Service\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
trait LocationServiceTrait
{
    /**
     * @var LocationService
     */
    protected $locationService;

    /**
     * @param LocationService|null $locationService
     */
    public function setLocationService(LocationService $locationService = null)
    {
        $this->locationService = $locationService;
    }

    /**
     * @return LocationService
     */
    public function getLocationService()
    {
        return $this->locationService;
    }
}
