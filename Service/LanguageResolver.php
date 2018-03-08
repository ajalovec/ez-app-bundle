<?php

namespace Origammi\Bundle\EzAppBundle\Service;

use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class LanguageResolver
 *
 * @package   Origammi\Bundle\EzAppBundle\Service
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi (http://origammi.co)
 */
class LanguageResolver
{
    /**
     * @var string
     */
    private $defaultLanguage;

    /**
     * @var array
     */
    private $siteAccessesByLanguage;

    /**
     * @var SiteAccess
     */
    private $siteaccess;

    /**
     * @var ConfigResolverInterface
     */
    private $configResolver;

    /**
     * @var LocaleConverterInterface
     */
    private $localeConverter;


    /**
     * @param string                  $defaultLanguageCode
     * @param array                   $siteAccessesByLanguage
     * @param SiteAccess              $siteAccess
     * @param ConfigResolverInterface $configResolver
     */
    public function __construct(
        $defaultLanguageCode,
        $siteAccessesByLanguage,
        SiteAccess $siteAccess,
        ConfigResolverInterface $configResolver
    ) {
        $this->defaultLanguage        = $defaultLanguageCode;
        $this->siteAccessesByLanguage = $siteAccessesByLanguage;
        $this->siteaccess               = $siteAccess;
        $this->configResolver         = $configResolver;
    }

    /**
     * @param LocaleConverterInterface $localeConverter
     */
    public function setLocaleConverter(LocaleConverterInterface $localeConverter)
    {
        $this->localeConverter = $localeConverter;
    }

    /**
     * @return string
     */
    public function getDefaultSiteAccess()
    {
        return $this->configResolver->getParameter('default', 'ezsettings', 'siteacess');
    }


    /**
     * @return string
     */
    public function getSiteAccessName()
    {
        return $this->siteaccess->name;
    }


    /**
     * @return string
     */
    public function getSiteAccess()
    {
        return $this->siteaccess;
    }

    /**
     * @param null|string $language
     *
     * @return mixed
     * @throws NotFoundException
     */
    public function getSiteAccessForLanguage($language)
    {
        if (strlen($language) <= 5) {
            $language = $this->localeConverter->convertToEz($language);
        }

        if (!isset($this->siteAccessesByLanguage[$language])) {
            throw new NotFoundException('%ezpublish.siteaccesses_by_language%', $language);
        }

        return $this->siteAccessesByLanguage[$language][0];
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    public function getSiteAccessForRequest(Request $request)
    {
        return $request->attributes->get('siteaccess')->name;
    }

    /**
     * @return string
     */
    public function getDefaultLanguage()
    {
        return $this->defaultLanguage;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->configResolver->getParameter('languages')[0];
    }

    /**
     * @param null|string $siteaccess
     *
     * @return string
     */
    public function getLanguageForSiteAccess($siteaccess = null)
    {
        return $this->configResolver->getParameter('languages', null, $siteaccess)[0];
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    public function getLanguageForRequest(Request $request)
    {
        return self::getLanguageForSiteAccess(self::getSiteAccessForRequest($request));
    }

    /**
     * Returns the list of all available languages, including the ones configured in related SiteAccesses.
     *
     * @return array
     */
    public function getSiteAccesses()
    {
        $siteAccesses = array_intersect(
            $this->configResolver->getParameter('translation_siteaccesses'),
            $this->configResolver->getParameter('related_siteaccesses')
        );

        return $siteAccesses;
    }

    /**
     * Returns the list of all available languages, including the ones configured in related SiteAccesses.
     *
     * @return array
     */
    public function getTranslationSiteAccesses()
    {
        return $this->configResolver->getParameter('translation_siteaccesses') ?:
            $this->configResolver->getParameter('related_siteaccesses');
    }

    /**
     * Returns the list of all available languages, including the ones configured in related SiteAccesses.
     *
     * @return array
     */
    public function getLanguages()
    {
        $translationSiteAccesses = $this->getTranslationSiteAccesses();

        $availableLanguages = [];

        foreach ($translationSiteAccesses as $sa) {
            $languages = $this->configResolver->getParameter('languages', null, $sa);

            $availableLanguages[$sa] = array_shift($languages);
        }

        return array_unique($availableLanguages);
    }

    /**
     * @return array
     */
    public function getLanguagesArray()
    {
        $languages = [];

        foreach ($this->getLanguages() as $siteaccess => $lang) {
            $languages[] = [
                'siteaccess' => $siteaccess,
                'lang'       => $lang,
                'locale'     => $this->localeConverter->convertToPOSIX($lang),
            ];
        }

        return $languages;
    }
}
