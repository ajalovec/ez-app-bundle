<?php

namespace Origammi\Bundle\EzAppBundle\Service\Traits;

use Origammi\Bundle\EzAppBundle\Service\LocationResolver;

/**
 * Trait LocationResolverTrait
 *
 * @package   Origammi\Bundle\EzAppBundle\Service\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2018 Origammi (http://origammi.co)
 */
trait LocationResolverTrait
{
    /**
     * @var LocationResolver
     */
    protected $locationResolver;

    /**
     * @required
     *
     * @param LocationResolver $locationResolver
     *
     * @return $this
     */
    public function setLocationResolver(LocationResolver $locationResolver)
    {
        $this->locationResolver = $locationResolver;

        return $this;
    }

}
