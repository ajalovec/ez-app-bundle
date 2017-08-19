<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\QueryType\Core;


use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\QueryType\QueryType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class AbstractQueryType
 *
 * @package   Origammi\Bundle\EzAppBundle\QueryType\Core
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
abstract class AbstractQueryType implements QueryType
{
    /** @var OptionsResolver */
    private $resolver;

    /**
     * Configures the OptionsResolver for the QueryType.
     *
     * Example:
     * ```php
     * // type is required
     * $resolver->setRequired('type');
     * // limit is optional, and has a default value of 10
     * $resolver->setDefault('limit', 10);
     * ```
     *
     * @param OptionsResolver $optionsResolver
     */
    abstract protected function configureOptions(OptionsResolver $optionsResolver);

    /**
     * @param array $options
     *
     * @return array
     */
    abstract protected function getFilters(array $options);

    final protected function resolveOptions(array $parameters = [])
    {
        $parameters = $this->getResolver()->resolve($parameters);

        $filters = [new Criterion\Visibility(Criterion\Visibility::VISIBLE)];
        $filters = array_merge($filters, $parameters['filters'], $this->getFilters($parameters));

        if ($parameters['allowed_content_types']) {
            $filters[] = new Criterion\ContentTypeIdentifier($parameters['allowed_content_types']);
        }

        $options = [
            'filter'       => new Criterion\LogicalAnd($filters),
            'offset'       => $parameters['offset'],
            'limit'        => $parameters['limit'],
            'sortClauses'  => $parameters['sort'],
            'performCount' => $parameters['count'],
        ];

        return $options;
    }

    final public function getSupportedParameters()
    {
        return $this->getResolver()->getDefinedOptions();
    }

    /**
     * Builds the resolver, and configures it using configureOptions().
     *
     * @return OptionsResolver
     */
    private function getResolver()
    {
        if ($this->resolver === null) {
            $this->resolver = new OptionsResolver();

            $this->resolver
                ->setRequired('location')
                ->setAllowedTypes('location', [Location::class, 'null'])

                ->setDefault('count', false)
                ->setAllowedTypes('count', ['bool'])

                ->setDefault('offset', 0)
                ->setAllowedTypes('offset', ['int', 'string', 'null'])
                ->setNormalizer('offset', function (Options $options, $offset) {
                    return $offset >= 0 ? (int)$offset : 0;
                })
                ->setDefault('limit', 100)
                ->setAllowedTypes('limit', ['int', 'string'])
                ->setNormalizer('limit', function (Options $options, $limit) {
                    return $limit >= 0 ? (int)$limit : null;
                })
                ->setDefault('allowed_content_types', null)
                ->setAllowedTypes('allowed_content_types', ['string', 'array', 'null'])
                ->setNormalizer('allowed_content_types', function (Options $options, $value) {
                    if (is_string($value) && false !== strpos($value, ',')) {
                        $value = explode(',', $value);
                    }

                    return is_array($value) && count($value) ? $value : null;
                })
                ->setDefault('sort', [])
                ->setAllowedTypes('sort', ['array'])
                ->setNormalizer('sort', function (Options $options, $value) {
                    return is_array($value) && count($value) ? $value : ($options['location'] ? $options['location']->getSortClauses() : []);
                })
                ->setDefault('filters', [])
                ->setAllowedTypes('filters', ['array'])
//                ->setNormalizer('filters', function (Options $options, $value) {
//                    return is_array($value) && count($value) ? $value : ($options['location'] ? $options['location']->getSortClauses() : []);
//                })
            ;
            $this->configureOptions($this->resolver);
        }

        return $this->resolver;
    }
}
