<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Repository\Traits;

use Origammi\Bundle\EzAppBundle\Repository\ContentService;

/**
 * Trait ContentServiceInterface
 * @package   Origammi\Bundle\EzAppBundle\Repository\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
interface ContentServiceInterface
{
    /**
     * @param ContentService|null $contentService
     */
    public function setContentService(ContentService $contentService = null);
}