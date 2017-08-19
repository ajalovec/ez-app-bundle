<?php

namespace Origammi\Bundle\EzAppBundle\Service;

use eZ\Publish\Core\Helper\TranslationHelper;
use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;


/**
 * Class LanguageResolver
 *
 * @package   Origammi\Bundle\EzAppBundle\Service
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class LanguageResolver
{
    /** @var LocaleConverterInterface */
    private $localeConverter;

    /** @var TranslationHelper */
    private $translationHelper;

    /** @var RequestStack */
    private $requestStack;

    /**
     * @param LocaleConverterInterface $localeConverter
     * @param TranslationHelper        $translationHelper
     * @param RequestStack             $requestStack
     */
    public function __construct(
        LocaleConverterInterface $localeConverter,
        TranslationHelper $translationHelper,
        RequestStack $requestStack
    ) {
        $this->localeConverter   = $localeConverter;
        $this->translationHelper = $translationHelper;
        $this->requestStack      = $requestStack;
    }

    /**
     * @param Request|null $request
     *
     * @return array
     */
    public function getCurrentLanguage(Request $request = null)
    {
        $locale     = $this->resolveRequest($request)->getLocale();
        $lang       = $this->localeConverter->convertToEz($locale);

        return [
            'siteaccess' => $this->translationHelper->getTranslationSiteAccess($lang),
            'lang'       => $lang,
            'locale'     => $locale,
        ];
    }

    /**
     * @return array
     */
    public function getLanguages()
    {
        $languages = [];

        foreach ($this->translationHelper->getAvailableLanguages() as $lang) {
            $siteaccess = $this->translationHelper->getTranslationSiteAccess($lang);

            $languages[] = [
                'siteaccess' => $siteaccess,
                'lang'       => $lang,
                'locale'     => $this->localeConverter->convertToPOSIX($lang),
            ];
        }

        return $languages;
    }

    /**
     * @param Request|null $request
     *
     * @return null|Request
     */
    private function resolveRequest(Request $request = null)
    {
        return $request ?: $this->requestStack->getMasterRequest();
    }
}
