<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Repository\Traits;

use Origammi\Bundle\EzAppBundle\Repository\ContentTypeApiService;

/**
 * Trait ContentTypeServiceInterface
 * @package   Origammi\Bundle\EzAppBundle\Repository\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
interface ContentTypeServiceInterface
{
    /**
     * @param ContentTypeApiService|null $contentTypeService
     */
    public function setContentTypeService(ContentTypeApiService $contentTypeService = null);
}
