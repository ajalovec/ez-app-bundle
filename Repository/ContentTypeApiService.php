<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Repository;

use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\API\Repository\Values\ContentType\ContentTypeGroup;
use eZ\Publish\SPI\Persistence\Content\VersionInfo;
use Origammi\Bundle\EzAppBundle\Utils\RepositoryUtil;

/**
 * Class ContentTypeService
 *
 * @package   Origammi\Bundle\EzAppBundle\Service
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi AG (http://origammi.co)
 */
class ContentTypeApiService
{
    /**
     * @var ContentTypeService
     */
    protected $contentTypeService;

    /**
     * ContentService constructor.
     *
     * @param ContentTypeService $contentTypeService
     */
    public function __construct(ContentTypeService $contentTypeService)
    {
        $this->contentTypeService = $contentTypeService;
    }

    /**
     * @return ContentTypeService
     */
    public function getService()
    {
        return $this->contentTypeService;
    }

    /**
     * Try to resolve ContentType object from mixed $id argument
     * Accepted values:
     *  id         - int
     *  identifier - string
     *  object     - Location|VersionInfo|ContentType|ContentInfo|Content
     *
     * @param int|string|Location|VersionInfo|ContentType|ContentInfo|Content $id Can be either id, identifier, Location, VersionInfo, ContentType, ContentInfo or Content object
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @return ContentType
     */
    public function load($id)
    {
        if ($id instanceof ContentType) {
            return $id;
        }

        $primaryId = $this->resolveId($id);
        if ($primaryId !== null) {
            return $this->contentTypeService->loadContentType($primaryId);
        }

        return $this->contentTypeService->loadContentTypeByIdentifier((string)$id);
    }

    /**
     * Try to resolve ContentTypeGroup object from mixed $id argument
     * Accepted values:
     *  id        - int|string
     *  object    - ContentTypeGroup
     *
     * @param int|string|ContentTypeGroup $id
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @return ContentType[]
     */
    public function loadByGroup($id)
    {
        if (RepositoryUtil::isPrimaryId($id)) {
            $id = $this->contentTypeService->loadContentTypeGroup((int)$id);
        } elseif (is_string($id)) {
            $id = $this->contentTypeService->loadContentTypeGroupByIdentifier($id);
        }

        return $this->contentTypeService->loadContentTypes($id);
    }


    /**
     * Try to resolve ContentType id from mixed $id argument
     * Accepted values:
     *  id     - int
     *  object - Location|VersionInfo|ContentType|ContentInfo|Content
     *
     * @param int|Location|VersionInfo|ContentType|ContentInfo|Content $object Can be either id, Location, VersionInfo, ContentType, ContentInfo or Content object
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

        if (RepositoryUtil::isPrimaryId($object)) {
            return (int)$object;
        }

        return null;
    }

}
