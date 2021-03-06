<?php

namespace Origammi\Bundle\EzAppBundle\Repository\Traits;

use Origammi\Bundle\EzAppBundle\Repository\ContentApiService;

/**
 * Trait ContentServiceInterface
 * @package   Origammi\Bundle\EzAppBundle\Repository\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
interface ContentServiceInterface
{
    /**
     * @param ContentApiService $contentService
     */
    public function setContentService(ContentApiService $contentService);
}
