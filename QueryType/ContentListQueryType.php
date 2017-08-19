<?php

namespace Origammi\Bundle\EzAppBundle\QueryType;


use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use EzSystems\EzPlatformSolrSearchEngine\Query\Common;
use Origammi\Bundle\EzAppBundle\QueryType\Core\AbstractLocationQueryType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class ContentType
 *
 * @package   Origammi\Bundle\EzAppBundle\QueryType
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class ContentListQueryType extends AbstractLocationQueryType
{
    protected function getFilters(array $options)
    {
        /** @var Location $location */
        $location = $options['location'];
        $filters  = [
            new Criterion\ParentLocationId($location->id),
//            new Criterion\Location\Depth(Criterion\Operator::BETWEEN, [3,4])
        ];

        return $filters;
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
//            ->setDefault('location', null)
//            ->remove('location')
//            ->setAllowedTypes('location', [Location::class])
//            ->setRequired('location')
            ->setDefault('sort_priority', false)
            ->setAllowedTypes('sort_priority', ['bool'])
            ->setNormalizer('sort_priority', function (Options $options, $value) {
                return $value;
            })
        ;
    }

    final public static function getName()
    {
        return 'ContentListQueryType';
    }
}
