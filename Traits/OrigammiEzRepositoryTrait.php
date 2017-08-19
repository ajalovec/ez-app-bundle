<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Traits;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;
use eZ\Publish\API\Repository\Values\Content\VersionInfo;

/**
 * Trait OrigammiEzRepositoryTrait
 * @package   Origammi\Bundle\EzAppBundle\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
trait OrigammiEzRepositoryTrait
{
    /**
     * @param $id
     *
     * @return bool
     */
    public static function isPrimaryId($id)
    {
        return is_int($id) || (is_string($id) && ctype_digit($id));
    }

    public function searchResultToArray(SearchResult $searchResult)
    {
        $childLocations = array();
        foreach ($searchResult->searchHits as $searchHit) {
            $childLocations[] = $searchHit->valueObject;
        }

        return $childLocations;
    }

    /**
     * @param array|SearchResult $searchResult
     *
     * @return array
     */
    protected function resolveContentIds($searchResult)
    {
        return $this->_resolve_ids($searchResult, [$this, 'resolveContentId']);
    }

    /**
     * Try to resolve Content id from mixed $id argument
     * Accepted values:
     *  id     - string|int
     *  object - Content|Location|VersionInfo|ContentInfo
     *
     * @param Content|Location|string|int $id
     *
     * @return int|null
     */
    protected function resolveContentId($id)
    {
        if ($id instanceof Content) {
            return $id->id;
        }

        if ($id instanceof Location) {
            return $id->contentId;
        }

        if (static::isPrimaryId($id)) {
            return (int)$id;
        }

        return null;
    }

    /**
     * @param array|SearchResult $searchResult
     *
     * @return array
     */
    protected function resolveLocationIds($searchResult)
    {
        return $this->_resolve_ids($searchResult, [$this, 'resolveLocationId']);
    }

    /**
     * Try to resolve Location id from mixed $id argument
     * Accepted values:
     *  id     - string|int
     *  object - Content|Location|VersionInfo|ContentInfo
     *
     * @param Content|Location|VersionInfo|ContentInfo|string|int $id
     *
     * @return int|null
     */
    protected function resolveLocationId($id)
    {
        if ($id instanceof Location) {
            return $id->id;
        }

        if ($id instanceof Content || $id instanceof VersionInfo) {
            $id = $id->contentInfo;
        }

        if ($id instanceof ContentInfo) {
            return $id->mainLocationId;
        }

        if (static::isPrimaryId($id)) {
            return (int)$id;
        }

        return null;
    }

    /**
     * @param array|SearchResult $searchResult
     * @param array              $resolver
     *
     * @return array
     */
    private function _resolve_ids($searchResult, array $resolver)
    {
        $ids = [];

        if ($searchResult instanceof SearchResult) {
            foreach ($searchResult->searchHits as $searchHit) {
                if ($id = call_user_func_array($resolver, [$searchHit->valueObject])) {
                    $ids[$id] = $id;
                }
            }
        } else {
            foreach ((array)$searchResult as $id) {
                if ($id = call_user_func_array($resolver, [$id])) {
                    $ids[$id] = $id;
                }
            }
        }

        return $ids;
    }

}
