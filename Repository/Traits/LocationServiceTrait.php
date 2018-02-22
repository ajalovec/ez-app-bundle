<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Repository\Traits;

use Origammi\Bundle\EzAppBundle\Repository\LocationApiService;

/**
 * Trait LocationServiceTrait
 * @package   Origammi\Bundle\EzAppBundle\Repository\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
trait LocationServiceTrait
{
    /**
     * @var LocationApiService
     */
    protected $locationService;

    /**
     * @param LocationApiService|null $locationService
     */
    public function setLocationService(LocationApiService $locationService = null)
    {
        $this->locationService = $locationService;
    }

    /**
     * @return LocationApiService
     */
    public function getLocationService()
    {
        return $this->locationService;
    }
}
