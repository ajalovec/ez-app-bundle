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
    const ADMIN_USER_ID = 14;

    /**
     * @var Repository
     */
    private $repository;


    /**
     * @return mixed
     */
    abstract public function getService();


    /**
     * @param Repository $repository
     */
    public function setRepository(Repository $repository)
    {
        $this->repository = $repository;
        $this->setUser(self::ADMIN_USER_ID);
    }

    /**
     * @return Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param $userId
     *
     * @return bool
     */
    protected function setUser($userId)
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
        $result = $this->repository->sudo(function (\eZ\Publish\API\Repository\Repository $repository) {
            $searchService = $repository->getSearchService();

            return true;
        });
    }

//    /**
//     * @param $name
//     * @param $args
//     *
//     * @return mixed
//     */
//    public function __call($name, $args)
//    {
//        if (method_exists($this->getService(), $name)) {
//            return call_user_func_array([$this->getService(), $name], $args);
//        }
//    }

}
