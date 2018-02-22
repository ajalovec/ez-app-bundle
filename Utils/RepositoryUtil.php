<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Utils;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\LocationList;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;
use eZ\Publish\API\Repository\Values\Content\VersionInfo;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentType;

/**
 * Class RepositoryUtil
 *
 * @package   Origammi\Bundle\EzAppBundle\Utils
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2018 Origammi (http://origammi.co)
 */
class RepositoryUtil
{
    /**
     * @param $id
     *
     * @return bool
     */
    public static function isPrimaryId($id)
    {
        return is_int($id) || ctype_digit((string)$id);
    }

    /**
     * @param SearchResult|LocationList $searchResult
     *
     * @return array
     * @throws InvalidArgumentType
     */
    public static function searchResultToArray($searchResult)
    {
        if (!$searchResult instanceof SearchResult || !$searchResult instanceof LocationList) {
            throw new InvalidArgumentType('searchResult', sprintf('%s or %s', SearchResult::class, LocationList::class), $searchResult);
        }

        $children = array();

        if ($searchResult instanceof SearchResult) {
            foreach ($searchResult->searchHits as $searchHit) {
                $children[] = $searchHit->valueObject;
            }
        } elseif ($searchResult instanceof LocationList) {
            foreach ($searchResult->locations as $location) {
                $children[] = $location;
            }
        }

        return $children;
    }

    /**
     * Convert array of mixed arguments to array of Content::$id values
     *
     * @param array|SearchResult|LocationList $searchResult
     *
     * @return array
     */
    public static function resolveContentIds($searchResult)
    {
        return self::_resolve_ids($searchResult, [self::class, 'resolveContentId']);
    }

    /**
     * Try to resolve Content::$id from mixed argument
     * Accepted values:
     *  id     - string|int
     *  object - Content|Location|VersionInfo|ContentInfo
     *
     * @param Content|Location|string|int $id
     *
     * @return int|null
     */
    public static function resolveContentId($id)
    {
        if ($id instanceof Content) {
            return $id->id;
        }

        if ($id instanceof Location || $id instanceof VersionInfo) {
            $id = $id->contentInfo;
        }

        if ($id instanceof ContentInfo) {
            return $id->id;
        }

        if (static::isPrimaryId($id)) {
            return (int)$id;
        }

        return null;
    }

    /**
     * Convert array of mixed arguments to array of Location::$id values
     *
     * @param array|SearchResult|LocationList $searchResult
     *
     * @return array
     */
    public static function resolveLocationIds($searchResult)
    {
        return self::_resolve_ids($searchResult, [self::class, 'resolveLocationId']);
    }

    /**
     * Try to resolve Location::$id value from mixed argument
     * Accepted values:
     *  id     - string|int
     *  object - Content|Location|VersionInfo|ContentInfo
     *
     * @param Content|Location|VersionInfo|ContentInfo|string|int $id
     *
     * @return int|null
     */
    public static function resolveLocationId($id)
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
     * @param array|SearchResult|LocationList $searchResult
     * @param array              $resolver
     *
     * @return array
     */
    private static function _resolve_ids($searchResult, array $resolver)
    {
        $ids = [];

        if ($searchResult instanceof SearchResult) {
            foreach ($searchResult->searchHits as $searchHit) {
                if ($id = call_user_func_array($resolver, [$searchHit->valueObject])) {
                    $ids[$id] = $id;
                }
            }
        } elseif ($searchResult instanceof LocationList) {
            foreach ($searchResult->locations as $location) {
                if ($id = call_user_func_array($resolver, [$location])) {
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
