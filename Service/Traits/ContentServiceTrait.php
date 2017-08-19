<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Service\Traits;

use Origammi\Bundle\EzAppBundle\Repository\ContentService;

/**
 * Trait ContentServiceTrait
 * @package   Origammi\Bundle\EzAppBundle\Service\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
trait ContentServiceTrait
{
    /**
     * @var ContentService
     */
    protected $contentService;

    /**
     * @param ContentService|null $contentService
     */
    public function setContentService(ContentService $contentService = null)
    {
        $this->contentService = $contentService;
    }

    /**
     * @return ContentService
     */
    public function getContentService()
    {
        return $this->contentService;
    }
}
