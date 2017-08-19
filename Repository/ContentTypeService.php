<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Repository;

use eZ\Publish\API\Repository\ContentTypeService as BaseContentTypeService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Values\ContentType\ContentTypeGroup;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use Origammi\Bundle\EzAppBundle\Traits\OrigammiEzRepositoryTrait;

/**
 * Class ContentTypeService
 *
 * @package   Origammi\Bundle\EzAppBundle\Service
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi AG (http://origammi.co)
 */
class ContentTypeService
{
    use OrigammiEzRepositoryTrait;

    /**
     * @var BaseContentTypeService
     */
    protected $contentTypeService;

    /**
     * ContentService constructor.
     *
     * @param BaseContentTypeService $contentTypeService
     */
    public function __construct(BaseContentTypeService $contentTypeService)
    {
        $this->contentTypeService = $contentTypeService;
    }

    /**
     * Try to resolve ContentType object from mixed $id argument
     * Accepted values:
     *  id        - int|string
     *  remote_id - string
     *  object    - ContentType|Location|VersionInfo|ContentInfo
     *
     * @param Location|VersionInfo|ContentInfo|int|string $id
     *
     * @return ContentType
     */
    public function load($id)
    {
        if ($id instanceof ContentType) {
            return $id;
        }

        $primaryId = $this->resolveId($id);
        if ($primaryId !== null) {
            return $this->contentTypeService->loadContentType((int)$primaryId);
        }

        return $this->contentTypeService->loadContentTypeByIdentifier($id);
    }

    /**
     * Try to resolve ContentTypeGroup object from mixed $id argument
     * Accepted values:
     *  id        - int|string
     *  object    - ContentTypeGroup
     *
     * @param int|string|ContentTypeGroup $id
     *
     * @return ContentType[]
     */
    public function loadByGroup($id)
    {
        if (static::isPrimaryId($id)) {
            $id = $this->contentTypeService->loadContentTypeGroup((int)$id);
        }
        elseif (is_string($id)) {
            $id = $this->contentTypeService->loadContentTypeGroupByIdentifier($id);
        }

        return $this->contentTypeService->loadContentTypes($id);
    }

    /**
     * @return ContentTypeGroup[]
     */
    public function loadAllGroups()
    {
        return $this->contentTypeService->loadContentTypeGroups();
    }

    /**
     * Try to resolve ContentType id from mixed $id argument
     * Accepted values:
     *  remote_id - string
     *  object    - Content|Location|VersionInfo|ContentInfo
     *
     * @param Content|Location|VersionInfo|ContentInfo|string $object
     *
     * @return int|null
     */
    public function resolveId($object)
    {
        if ($object instanceof ContentType) {
            return $object->id;
        }

        if ($object instanceof Content || $object instanceof Location || $object instanceof VersionInfo) {
            $object = $object->contentInfo;
        }

        if ($object instanceof ContentInfo) {
            return $object->contentTypeId;
        }

        if (static::isPrimaryId($object)) {
            return (int)$object;
        }

        return null;
    }

}
