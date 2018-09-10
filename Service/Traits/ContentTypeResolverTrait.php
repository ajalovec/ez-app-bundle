<?php

namespace Origammi\Bundle\EzAppBundle\Service\Traits;

use Origammi\Bundle\EzAppBundle\Service\ContentTypeResolver;


/**
 * Trait ContentTypeResolverTrait
 *
 * @package   Origammi\Bundle\EzAppBundle\Service\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2018 Origammi (http://origammi.co)
 */
trait ContentTypeResolverTrait
{
    /**
     * @var ContentTypeResolver
     */
    protected $contentTypeResolver;

    /**
     * @required
     *
     * @param ContentTypeResolver $contentTypeResolver
     *
     * @return $this
     */
    public function setContentTypeResolver(ContentTypeResolver $contentTypeResolver)
    {
        $this->contentTypeResolver = $contentTypeResolver;

        return $this;
    }

}
