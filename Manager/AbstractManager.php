<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Manager;

use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\Repository\Values\User\UserReference;

/**
 * Class AbstractManager
 *
 * @package   Origammi\Bundle\EzAppBundle\Manager
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
abstract class AbstractManager
{
    const DEFAULT_LANGUAGE_CODE = 'eng-GB';

    /**
     * @var null|int
     */
    private $adminUserId;

    /**
     * @var string
     */
    private $mainLanguage;

    /**
     * @var Repository
     */
    private $repository;


    /**
     * @return mixed
     */
    abstract public function getService();

    /**
     * AbstractManager constructor.
     *
     * @param int    $adminUserId
     * @param string $mainLanguage
     */
    public function __construct($adminUserId, $mainLanguage = self::DEFAULT_LANGUAGE_CODE)
    {
        // TODO: do not implicitly set user id when service is created but make it modifiable
        $this->adminUserId  = (int)$adminUserId;
        $this->mainLanguage = $mainLanguage;
    }

    /**
     * @param Repository $repository
     */
    public function setRepository(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @return string
     */
    public function getMainLanguage()
    {
        return $this->mainLanguage;
    }

    public function loginAdminUser()
    {
        $this->loginUser($this->adminUserId);
    }

    public function loginUser($userId)
    {
        $permissionResolver = $this->repository->getPermissionResolver();

        if ($userId instanceof UserReference) {
            $userId = $userId->getUserId();
        }

        if (is_int($userId) || ctype_xdigit($userId)) {
            $previousUser = $permissionResolver->getCurrentUserReference();
            if ($userId != $previousUser->getUserId()) {
                $permissionResolver->setCurrentUserReference(new UserReference($userId));

                return true;
            }
        }

        return false;
    }

    protected function test()
    {
        // TODO: test implementation of sudo execution which doesnt restrict actions based on the current repository user
        $result = $this->repository->sudo(function (\eZ\Publish\API\Repository\Repository $repository) {
            $searchService = $repository->getSearchService();

            return true;
        });
    }

    /**
     * @param $name
     * @param $args
     *
     * @return mixed
     */
    public function __call($name, $args)
    {
        if (method_exists($this->getService(), $name)) {
            return call_user_func_array([$this->getService(), $name], $args);
        }
    }

}
