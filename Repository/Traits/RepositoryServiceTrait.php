<?php

namespace Origammi\Bundle\EzAppBundle\Repository\Traits;

use eZ\Publish\API\Repository\Repository;

/**
 * Trait RepositoryServiceTrait
 * @package   Origammi\Bundle\EzAppBundle\Repository\Traits
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
trait RepositoryServiceTrait
{
    /**
     * @var Repository
     */
    protected $repositoryService;

    /**
     * @required
     *
     * @param Repository $repositoryService
     */
    public function setRepositoryService(Repository $repositoryService)
    {
        $this->repositoryService = $repositoryService;
    }

    /**
     * @return Repository
     */
    public function getRepositoryService()
    {
        return $this->repositoryService;
    }
}
