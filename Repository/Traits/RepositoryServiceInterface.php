<?php

namespace Origammi\Bundle\EzAppBundle\Repository\Traits;

use eZ\Publish\API\Repository\Repository;

/**
 * Trait RepositoryServiceInterface
 * @package   Origammi\Bundle\EzAppBundle\Repository\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
interface RepositoryServiceInterface
{
    /**
     * @required
     *
     * @param Repository $repositoryService
     */
    public function setRepositoryService(Repository $repositoryService);
}
