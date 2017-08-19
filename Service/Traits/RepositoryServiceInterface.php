<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Service\Traits;

use eZ\Publish\API\Repository\Repository;

/**
 * Trait RepositoryServiceInterface
 * @package   Origammi\Bundle\EzAppBundle\Service\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
interface RepositoryServiceInterface
{
    /**
     * @param Repository|null $repositoryService
     */
    public function setRepositoryService(Repository $repositoryService = null);
}
