<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\QueryType;


use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\QueryType\QueryType;
use Origammi\Bundle\EzAppBundle\QueryType\Core\AbstractLocationQueryType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class SubtreeChildrenQueryType
 *
 * @package   Origammi\Bundle\EzAppBundle\QueryType
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class SubtreeChildrenQueryType extends AbstractLocationQueryType implements QueryType
{
    protected function getFilters(array $options)
    {
        /** @var Location $location */
        $location = $options['location'];
        $filters  = [
            new Criterion\Subtree($location->pathString),
            new Criterion\Location\Depth(Criterion\Operator::BETWEEN, [3, 4]),
        ];

        return $filters;
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('location');
    }

    final public static function getName()
    {
        return 'SubtreeChildrenQueryType';
    }
}
