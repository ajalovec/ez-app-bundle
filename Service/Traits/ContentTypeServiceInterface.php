<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Service\Traits;

use Origammi\Bundle\EzAppBundle\Repository\ContentTypeService;

/**
 * Trait ContentTypeServiceInterface
 * @package   Origammi\Bundle\EzAppBundle\Service\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
interface ContentTypeServiceInterface
{
    /**
     * @param ContentTypeService|null $contentTypeService
     */
    public function setContentTypeService(ContentTypeService $contentTypeService = null);
}
