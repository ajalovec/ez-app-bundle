<?php
/**
 * Copyright (c) 2018.
 */

namespace Origammi\Bundle\EzAppBundle\Twig;

use eZ\Publish\API\Repository\Values\Content\Location;
use Origammi\Bundle\EzAppBundle\Repository\LocationApiService;
use Origammi\Bundle\EzAppBundle\Service\LanguageResolver;
use Twig_Extension;

/**
 * Class LocationExtension
 *
 * @package   Origammi\Bundle\EzAppBundle\Twig
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi AG (http://origammi.co)
 */
class LocationExtension extends Twig_Extension
{
    /**
     * @var LocationApiService
     */
    private $locationApi;

    /**
     * @var LanguageResolver
     */
    private $languageResolver;


    public function __construct(
        LocationApiService $locationApi,
        LanguageResolver $languageResolver
    ) {
        $this->locationApi      = $locationApi;
        $this->languageResolver = $languageResolver;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'origammi_ez_app_location';
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('app_load_location', [ $this, 'loadLocation' ]),
            new \Twig_SimpleFunction('app_load_location_children', [ $this, 'loadLocationChildren' ]),
            new \Twig_SimpleFunction('app_load_location_id', [ $this, 'loadLocationId' ]),
            new \Twig_SimpleFunction('app_location_lang_available', [ $this, 'isLocationLangAvailable' ]),
        ];
    }


    /**
     * @param mixed $id
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @return Location|Location[]
     */
    public function loadLocation($id)
    {
        return $this->locationApi->load($id);
    }


    /**
     * @param Location          $location
     * @param string|array|null $contentTypes
     * @param int|null          $limit
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotImplementedException
     *
     * @return Location[]
     */
    public function loadLocationChildren(Location $location, $contentTypes = null, $limit = null)
    {
        if (is_string($contentTypes)) {
            $contentTypes = [ $contentTypes ];
        }

        return $this->locationApi->findByParent($location, $contentTypes, $limit);
    }


    /**
     * @param mixed $id
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @return int
     */
    public function loadLocationId($id)
    {
        return $this->loadLocation($id)->id;
    }


    /**
     * @param Location $location
     * @param          $langCode
     *
     * @return bool
     */
    public function isLocationLangAvailable(Location $location, $langCode)
    {
        return $this->languageResolver->isLocationLangAvailable($location, $langCode);
    }

}
