<?php

namespace Origammi\Bundle\EzAppBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class DefaultMainLanguageCompilerPass
 *
 * @package   Origammi\Bundle\EzAppBundle\DependencyInjection\Compiler
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class DefaultMainLanguageCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $defaultSiteaccess = $container->getParameterBag()->get('ezpublish.siteaccess.default');
        $languages         = $container->getParameterBag()->get('ezsettings.' . $defaultSiteaccess . '.languages');

        $container->setParameter('origammi.ez_app.main_language_code', array_shift($languages));
    }

}
