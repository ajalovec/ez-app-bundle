<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Service;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\Core\MVC\Symfony\Templating\GlobalHelper;
use Origammi\Bundle\EzAppBundle\Repository\LocationApiService;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LocationResolver
 *
 * @package   Origammi\Bundle\EzAppBundle\Service
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class LocationResolver
{
    /**
     * @var LocationApiService
     */
    private $locationService;

    /**
     * @var GlobalHelper
     */
    private $globalHelper;

    /**
     * @var Location
     */
    private $currentLocation;

    /**
     * @param LocationApiService $locationService
     * @param GlobalHelper       $globalHelper
     *
     * @internal param RequestStack $requestStack
     * @internal param ConfigResolver $configResolver
     */
    public function __construct(LocationApiService $locationService, GlobalHelper $globalHelper)
    {
        $this->locationService = $locationService;
        $this->globalHelper    = $globalHelper;
    }

    /**
     * @return Location
     */
    public function getRootLocation()
    {
        return $this->globalHelper->getRootLocation();
    }

    /**
     * @param null|int|string|Content|Location $location
     *
     * @return Location
     */
    public function resolveLocation($location = null)
    {
        if ($location && $location = $this->locationService->load($location)) {
            return $location;
        }

        return $this->getCurrentLocation();
    }

    /**
     * @return Location
     */
    public function getCurrentLocation()
    {
        if ($this->currentLocation) {
            return $this->currentLocation;
        }

        $this->currentLocation = $this->getLocation($this->globalHelper->getRequestStack()->getMasterRequest());

        return $this->currentLocation ?: $this->globalHelper->getRootLocation();
    }

    /**
     * @return int
     */
    public function getCurrentLocationId()
    {
        return $this->getCurrentLocation()->id;
    }

    /**
     * @param Request $request
     *
     * @return Location|null
     */
    public function getLocation(Request $request)
    {
        $location = $request->attributes->get('location');

        if ($location instanceof Location) {
            return $location;
        }

        $locationId = $request->attributes->get('locationId');

        if ($locationId) {
            return $this->locationService->loadById((int)$locationId);
        }

        return null;
    }
}
