<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Repository\Traits;

use Origammi\Bundle\EzAppBundle\Repository\ContentApiService;

/**
 * Trait ContentServiceTrait
 * @package   Origammi\Bundle\EzAppBundle\Repository\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
trait ContentServiceTrait
{
    /**
     * @var ContentApiService
     */
    protected $contentService;

    /**
     * @param ContentApiService|null $contentService
     */
    public function setContentService(ContentApiService $contentService = null)
    {
        $this->contentService = $contentService;
    }

    /**
     * @return ContentApiService
     */
    public function getContentService()
    {
        return $this->contentService;
    }
}
