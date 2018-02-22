<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Command\User;

use eZ\Publish\API\Repository\UserService;
use Origammi\Bundle\EzAppBundle\Manager\UserManager;
use Symfony\Component\Console\Style\OutputStyle;

/**
 * Class AssignGroupCommand
 *
 * @package   Origammi\Bundle\EzAppBundle\Command
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class AssignGroupCommand extends GroupCommand
{
    const COMMAND_NAME = 'origammi:ez:user:assign-group';

    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Add user to one or more user groups')
        ;
    }

    /**
     * @see Command
     *
     * @param UserManager|UserService $manager
     * @param OutputStyle             $output
     * @param string                  $username
     * @param array                   $groups
     * @param bool                    $hasAdminGroup
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    protected function executeGroupCommand(UserManager $manager, OutputStyle $output, $username, array $groups, $hasAdminGroup)
    {
        $user = $manager->loadUserByLogin($username);
        $manager->addUserGroups($user, $groups);

        if ($hasAdminGroup) {
            $message = sprintf('User <info>%s</info> has been assigned to administrator group.', $user->login);
        } else {
            $message = sprintf('User <info>%s</info> has been assigned to group/s: <info>%s</info>', $user->login, implode(', ', $groups));
        }
        $output->writeln($message);
    }

}
