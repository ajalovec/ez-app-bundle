<?php

namespace Origammi\Bundle\EzAppBundle\Service\Traits;

use Origammi\Bundle\EzAppBundle\Service\LanguageResolver;

/**
 * Trait LanguageResolverTrait
 *
 * @package   Origammi\Bundle\EzAppBundle\Service\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2018 Origammi (http://origammi.co)
 */
trait LanguageResolverTrait
{
    /**
     * @var LanguageResolver
     */
    protected $languageResolver;

    /**
     * @required
     *
     * @param LanguageResolver $languageResolver
     *
     * @return $this
     */
    public function setLanguageResolver(LanguageResolver $languageResolver)
    {
        $this->languageResolver = $languageResolver;

        return $this;
    }

}
