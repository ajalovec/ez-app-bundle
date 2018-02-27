<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller as BaseController;
use eZ\Publish\Core\MVC\Symfony\Templating\GlobalHelper;
use Origammi\Bundle\EzAppBundle\Repository\ApiService;
use Origammi\Bundle\EzAppBundle\Service\ContentTypeResolver;
use Origammi\Bundle\EzAppBundle\Service\FieldResolver;
use Origammi\Bundle\EzAppBundle\Service\LanguageResolver;
use Origammi\Bundle\EzAppBundle\Service\LocationResolver;

/**
 * Class Controller
 *
 * @package   Origammi\Bundle\EzAppBundle\Controller
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
abstract class Controller extends BaseController
{

    /**
     * @return GlobalHelper
     */
    public function getGlobalHelper()
    {
        return $this->container->get('ezpublish.templating.global_helper');
    }

    /**
     * @return \eZ\Publish\Core\MVC\Symfony\SiteAccess|null
     */
    protected function getSiteaccess()
    {
        return $this->getGlobalHelper()->getSiteaccess();
    }

    /**
     * @return ContentTypeResolver
     */
    protected function getContentTypeResolver()
    {
        return $this->container->get('origammi.ez_app.service.content_type_resolver');
    }

    /**
     * @return ApiService
     */
    protected function getApiRepository()
    {
        return $this->container->get('origammi.ez_app.repository.api');
    }

    /**
     * @return FieldResolver
     */
    protected function getFieldResolver()
    {
        return $this->container->get('origammi.ez_app.service.field_resolver');
    }

    /**
     * @return LocationResolver
     */
    protected function getLocationResolver()
    {
        return $this->container->get('origammi.ez_app.service.location_resolver');
    }

    /**
     * @return LanguageResolver
     */
    protected function getLanguageResolver()
    {
        return $this->container->get('origammi.ez_app.service.language_resolver');
    }

    /**
     * @return \Netgen\TagsBundle\Core\SignalSlot\TagsService|object
     */
    protected function getTagsService()
    {
        return $this->get('eztags.api.service.tags');
    }

}
