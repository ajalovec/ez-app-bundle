<?php

namespace Origammi\Bundle\EzAppBundle\Service\Traits;

use Origammi\Bundle\EzAppBundle\Service\FieldResolver;

/**
 * Trait FieldResolverTrait
 *
 * @package   Origammi\Bundle\EzAppBundle\Service\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2018 Origammi (http://origammi.co)
 */
trait FieldResolverTrait
{
    /**
     * @var FieldResolver
     */
    protected $fieldResolver;

    /**
     * @required
     *
     * @param FieldResolver $fieldResolver
     *
     * @return $this
     */
    public function setFieldResolver(FieldResolver $fieldResolver)
    {
        $this->fieldResolver = $fieldResolver;

        return $this;
    }

}
