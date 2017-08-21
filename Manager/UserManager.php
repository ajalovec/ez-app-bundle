<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Manager;

use eZ\Publish\API\Repository\Values\User\User;

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
     * @return \eZ\Publish\API\Repository\UserService
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
    public function create(array $data, array $groups, $lang)
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
            $lang,
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

    public function updateGroups(User $user, array $groups)
    {
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

        return $user;
    }

    /**
     * @param User $user
     *
     * @return User
     */
    public function delete(User $user)
    {
        $this->getService()->deleteUser($user);

        return $user;
    }

}
