<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\QueryType\Core;


use eZ\Publish\API\Repository\Values\Content\LocationQuery;

/**
 * Class AbstractLocationQueryType
 *
 * @package   Origammi\Bundle\EzAppBundle\QueryType\Core
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
abstract class AbstractLocationQueryType extends AbstractQueryType
{
    /**
     * @param array $parameters
     *
     * @return LocationQuery
     */
    final public function getQuery(array $parameters = [])
    {
        return new LocationQuery($this->resolveOptions($parameters));
    }
}
