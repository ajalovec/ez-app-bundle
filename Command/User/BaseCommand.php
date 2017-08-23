<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Command\User;

use eZ\Publish\API\Repository\UserService;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Origammi\Bundle\EzAppBundle\Manager\UserManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Class BaseCommand
 *
 * @package   Origammi\Bundle\EzAppBundle\Command\User
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
abstract class BaseCommand extends ContainerAwareCommand
{

    /**
     * @return UserManager|UserService
     */
    protected function getUserManager()
    {
        return $this->getContainer()->get('origammi_ezapp.manager.user');
    }

    /**
     * @return ConfigResolverInterface
     */
    protected function getConfigResolver()
    {
        return $this->getContainer()->get('ezpublish.config.resolver');
    }
}
