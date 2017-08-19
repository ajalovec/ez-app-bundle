<?php
/**
 * Copyright (c) 2017.
 */
namespace Origammi\Bundle\EzAppBundle\Service\Traits;

use eZ\Publish\API\Repository\Repository;

/**
 * Trait RepositoryServiceTrait
 * @package   Origammi\Bundle\EzAppBundle\Service\Traits
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
     * @param Repository|null $repositoryService
     */
    public function setRepositoryService(Repository $repositoryService = null)
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
