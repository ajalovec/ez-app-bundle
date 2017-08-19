<?php

namespace Origammi\Bundle\EzAppBundle\QueryType;


use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\QueryType\OptionsResolverBasedQueryType;
use eZ\Publish\Core\QueryType\QueryType;
use Origammi\Bundle\EzAppBundle\QueryType\Core\QueryFactory;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FindByDestinationIdsQueryType
 *
 * @package   Origammi\Bundle\EzAppBundle\QueryType
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class FindByDestinationIdsQueryType extends OptionsResolverBasedQueryType implements QueryType
{

    protected function doGetQuery(array $parameters)
    {
        $queryFactory = QueryFactory::create()
            ->addSort(new Query\SortClause\ContentName(Query::SORT_ASC))
            ->setLimit($parameters['limit'])
        ;

        if ($parameters['content_id']) {
            $queryFactory->addFilter(new Criterion\ContentId($parameters['content_id']));
        }

        return $queryFactory->createContentQuery();
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'limit'      => 10,
                'content_id' => null,
            ])
            ->setAllowedTypes('limit', ['int'])
            ->setAllowedTypes('content_id', ['int', 'string', 'array', Content::class])
            ->setNormalizer('content_id', function (Options $options, $value) {
                if (is_array($value)) {
                    return array_map([$this, 'resolveContentParameter'], $value);
                }

                return (int)($value instanceof Content ? $value->id : $value);
            })
        ;
    }

    public static function getName()
    {
        return 'OrigammiEzApp:Content:FindBy';
    }


    private function resolveContentParameter($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'resolveContentParameter'], $value);
        }

        return (int)($value instanceof Content ? $value->id : $value);
    }
}
