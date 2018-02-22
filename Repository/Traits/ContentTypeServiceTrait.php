<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Repository\Traits;

use Origammi\Bundle\EzAppBundle\Repository\ContentTypeApiService;


/**
 * Trait ContentTypeServiceTrait
 * @package   Origammi\Bundle\EzAppBundle\Repository\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
trait ContentTypeServiceTrait
{
    /**
     * @var ContentTypeApiService
     */
    protected $contentTypeService;

    /**
     * @param ContentTypeApiService|null $contentTypeService
     */
    public function setContentTypeService(ContentTypeApiService $contentTypeService = null)
    {
        $this->contentTypeService = $contentTypeService;
    }

    /**
     * @return ContentTypeApiService
     */
    public function getContentTypeService()
    {
        return $this->contentTypeService;
    }
}
