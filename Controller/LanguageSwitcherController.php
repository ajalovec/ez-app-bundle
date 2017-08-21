<?php

namespace Origammi\Bundle\EzAppBundle\Controller;

use eZ\Publish\Core\MVC\Symfony\Controller\Content\ViewController;
use Origammi\Bundle\EzAppBundle\Service\LanguageResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LanguageSwitcherController
 *
 * @package   Origammi\Bundle\EzAppBundle\Controller
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class LanguageSwitcherController
{
    /**
     * @var LanguageResolver
     */
    private $languageResolver;

    /**
     * @var ViewController
     */
    private $viewController;

    /**
     * @param LanguageResolver $languageResolver
     * @param ViewController   $viewController
     */
    public function __construct(LanguageResolver $languageResolver, ViewController $viewController)
    {
        $this->languageResolver = $languageResolver;
        $this->viewController   = $viewController;
    }

    /**
     * Renders language switcher view.
     *
     * @param Request $request
     * @param string  $template
     *
     * @return Response
     */
    public function show(Request $request, $template)
    {
        return $this->viewController->render(
            $template,
            [
                'currentLanguage' => $this->languageResolver->getLanguage($request),
                'languages'       => $this->languageResolver->getLanguages(),
            ]
        );
    }
}
