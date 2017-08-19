<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\QueryType\Core;


use eZ\Publish\API\Repository\Values\Content\Query;

/**
 * Class AbstractContentQueryType
 *
 * @package   Origammi\Bundle\EzAppBundle\QueryType\Core
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
abstract class AbstractContentQueryType extends AbstractQueryType
{
    /**
     * @param array $parameters
     *
     * @return Query
     */
    final public function getQuery(array $parameters = [])
    {
        return new Query($this->resolveOptions($parameters));
    }
}
