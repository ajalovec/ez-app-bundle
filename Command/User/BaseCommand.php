<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Command\User;

use eZ\Publish\API\Repository\UserService;
use Origammi\Bundle\EzAppBundle\Manager\UserManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @var int
     */
    protected $adminUserId;

    /**
     * @var UserManager|UserService
     */
    protected $userManager;


    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $container = $this->getContainer();
        $this->adminUserId = (int)$container->getParameter('origammi.ez_app.manager.admin_user.id');
        $this->userManager = $container->get('origammi.ez_app.manager.user');
    }


    /**
     * @return UserManager|UserService
     */
    protected function getUserManager()
    {
        return $this->userManager;
    }


    /**
     * Returns value for $paramName, in $namespace.
     *
     * @param string $paramName The parameter name, without $prefix and the current scope (i.e. siteaccess name).
     * @param string $namespace Namespace for the parameter name. If null, the default namespace should be used.
     * @param string $scope     The scope you need $paramName value for.
     *
     * @throws \eZ\Publish\Core\MVC\Exception\ParameterNotFoundException
     *
     * @return mixed
     */
    protected function getScopeParameter($paramName, $namespace = null, $scope = null)
    {
        return $this->getContainer()->get('ezpublish.config.resolver')->getParameter($paramName, $namespace, $scope);
    }

}
