<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Manager;

use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Values\User\User;
use eZ\Publish\API\Repository\Values\User\UserGroup;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;

/**
 * Class UserManager
 *
 * @package   Origammi\Bundle\EzAppBundle\Manager
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class UserManager extends AbstractManager
{
    const USER_CONTENT_TYPE = 'user';

    /**
     * @return UserService
     */
    public function getService()
    {
        return $this->getRepository()->getUserService();
    }

    /**
     * @param array  $data
     * @param array  $groups
     * @param string $lang
     *
     * @return User
     * @throws \Exception
     */
    public function create(array $data, array $groups, $lang = null)
    {
        if (empty($groups)) {
            throw new \Exception('No user groups set to create user in.');
        }

        $contentTypeService = $this->getRepository()->getContentTypeService();

        $userGroups = array();
        foreach ($groups as $groupId) {
            $userGroup = $this->getService()->loadUserGroup($groupId);

            $userGroups[] = $userGroup;
        }

        // FIXME: Hard coding content type to user for now
        $userContentType = $contentTypeService->loadContentTypeByIdentifier(self::USER_CONTENT_TYPE);

        $userCreateStruct = $this->getService()->newUserCreateStruct(
            $data['username'],
            $data['email'],
            $data['password'],
            $lang ?: $this->getMainLanguage(),
            $userContentType
        );
        $userCreateStruct->setField('first_name', $data['first_name']);
        $userCreateStruct->setField('last_name', $data['last_name']);

        // Create the user
        $user = $this->getService()->createUser($userCreateStruct, $userGroups);

        return $user;
    }

    /**
     * @param User  $user
     * @param array $data
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\ContentFieldValidationException
     * @throws \eZ\Publish\API\Repository\Exceptions\ContentValidationException
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @return User
     */
    public function update(User $user, array $data)
    {
        $userUpdateStruct = $this->getService()->newUserUpdateStruct();

        if (isset($data['email'])) {
            $userUpdateStruct->email = $data['email'];
        }
        if (isset($data['password'])) {
            $userUpdateStruct->password = (string)$data['password'];
        }
        if (isset($data['enabled'])) {
            $userUpdateStruct->enabled = $data['enabled'];
        }

        $user = $this->getService()->updateUser($user, $userUpdateStruct);

        return $user;
    }

    /**
     * @param User $user
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @return User
     */
    public function delete(User $user)
    {
        $this->getService()->deleteUser($user);

        return $user;
    }

    /**
     * Assignes user to groups and removes all groups that are not passed via $groups argument
     *
     * @param User  $user
     * @param array $groups
     *
     * @throws InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\BadStateException
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @return array
     */
    public function setUserGroups(User $user, array $groups)
    {
        if (empty($groups)) {
            throw new InvalidArgumentException('groups', 'You need to specify at least 1 group id.');
        }
        $assignedGroups = $this->getService()->loadUserGroupsOfUser($user);

        $targetGroupIds = [];
        // Assigning new groups to the user
        foreach ($groups as $groupId) {
            $groupToAssign    = $this->getService()->loadUserGroup($groupId);
            $targetGroupIds[] = $groupToAssign->id;

            $present = false;
            foreach ($assignedGroups as $assignedGroup) {
                // Make sure we assign the user only to groups he isn't already assigned to
                if ($assignedGroup->id == $groupToAssign->id) {
                    $present = true;
                    break;
                }
            }

            if (!$present) {
                $this->getService()->assignUserToUserGroup($user, $groupToAssign);
            }
        }

        // Unassigning groups that are not in the list in the migration
        foreach ($assignedGroups as $assignedGroup) {
            if (!in_array($assignedGroup->id, $targetGroupIds)) {
                $this->getService()->unAssignUserFromUserGroup($user, $assignedGroup);
            }
        }

        return $targetGroupIds;
    }

    /**
     * Adds user to additional groups
     *
     * @param User  $user
     * @param array $groups
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @return array
     */
    public function addUserGroups(User $user, array $groups)
    {
        $assignedGroupIds = array_map(function (UserGroup $userGroup) {
            return $userGroup->id;
        }, $this->getService()->loadUserGroupsOfUser($user));

        $newGroupIds = [];
        // Assigning groups to the user
        foreach ($groups as $groupId) {
            if (false !== array_search($groupId, $assignedGroupIds)) {
                continue;
            }

            $groupToAssign = $this->getService()->loadUserGroup($groupId);
            $newGroupIds[] = $groupToAssign->id;

            $this->getService()->assignUserToUserGroup($user, $groupToAssign);
        }

        return $newGroupIds;
    }

    /**
     * @param User  $user
     * @param array $groups
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\BadStateException
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     * @return array
     */
    public function removeUserGroups(User $user, array $groups)
    {
        $assignedGroupIds = array_map(function (UserGroup $userGroup) {
            return $userGroup->id;
        }, $this->getService()->loadUserGroupsOfUser($user));

        $removedGroupIds = [];
        // unAssigning groups from the user
        foreach ($groups as $groupId) {
            if (false !== array_search($groupId, $assignedGroupIds)) {
                continue;
            }

            $groupToUnAssign = $this->getService()->loadUserGroup($groupId);
            $removedGroupIds[] = $groupToUnAssign->id;

            $this->getService()->unAssignUserFromUserGroup($user, $groupToUnAssign);
        }

        return $removedGroupIds;
    }

}
