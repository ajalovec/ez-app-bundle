<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Service;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use Origammi\Bundle\EzAppBundle\Repository\ContentTypeApiService;

/**
 * Class ContentTypeResolver
 *
 * @package   Origammi\Bundle\EzAppBundle\Service
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi AG (http://origammi.co)
 */
class ContentTypeResolver
{
    /**
     * @var ContentType[]
     */
    private $loadedTypes = [];

    /**
     * @var array
     */
    private $loadedTypesIdentifiers = [];

    /**
     * @var ContentTypeApiService
     */
    private $contentTypeService;

    /**
     * @param ContentTypeApiService $contentTypeService
     */
    public function __construct(ContentTypeApiService $contentTypeService)
    {
        $this->contentTypeService = $contentTypeService;
    }

    /**
     * @param int|string|Location|ContentInfo|Content $contentType Can be either id, identifier, Location, ContentInfo or Content object
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @return ContentType
     */
    public function getContentType($contentType)
    {
        $contentType = $this->contentTypeService->resolveId($contentType);

        if (is_scalar($contentType)) {
            if (is_string($contentType) && isset($this->loadedTypesIdentifiers[$contentType])) {
                $contentType = $this->loadedTypesIdentifiers[$contentType];
            }

            if (isset($this->loadedTypes[$contentType])) {
                return $this->loadedTypes[$contentType];
            }
        }

        $contentType = $this->contentTypeService->load($contentType);

        $this->loadedTypesIdentifiers[$contentType->identifier] = $contentType->id;
        $this->loadedTypes[$contentType->id]                    = $contentType;

        return $contentType;
    }

    /**
     * @param string|array                     $identifier
     * @param int|Location|ContentInfo|Content $content Can be either id, Location, ContentInfo or Content object
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @return bool
     */
    public function isContentType($identifier, $content)
    {
        if (is_array($identifier)) {
            foreach ($identifier as $item) {
                if ($this->isContentType($item, $content)) {
                    return true;
                }
            }

            return false;
        }

        return $identifier === $this->getContentType($content)->identifier;
    }

}
