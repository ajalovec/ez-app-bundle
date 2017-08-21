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
     * @param ConfigResolverInterface $configResolver
     */
    public function __construct(
        $defaultLanguageCode,
        $siteAccessesByLanguage,
        ConfigResolverInterface $configResolver
    ) {
        $this->defaultLanguage        = $defaultLanguageCode;
        $this->siteAccessesByLanguage = $siteAccessesByLanguage;
        $this->configResolver         = $configResolver;
    }

    /**
     * @param SiteAccess $siteaccess
     */
    public function setSiteAccess(SiteAccess $siteaccess)
    {
        $this->siteaccess = $siteaccess;
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
     * @param null|string $language
     *
     * @return string
     * @throws NotFoundException
     */
    public function getSiteAccess($language = null)
    {
        if ($language) {
            if (strlen($language) <= 5) {
                $language = $this->localeConverter->convertToEz($language);
            }

            if (isset($this->siteAccessesByLanguage[$language])) {
                return $this->siteAccessesByLanguage[$language][0];
            }

            throw new NotFoundException('%ezpublish.siteaccesses_by_language%', $language);
        }

        return $this->siteaccess->name;
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    public function getSiteAccessFromRequest(Request $request)
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
     * @param null|string $siteaccess
     *
     * @return string
     */
    public function getLanguage($siteaccess = null)
    {
        return $this->configResolver->getParameter('languages', null, $siteaccess)[0];
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    public function getLanguageFromRequest(Request $request)
    {
        return self::getLanguage(self::getSiteAccessFromRequest($request));
    }

    /**
     * Returns the list of all available languages, including the ones configured in related SiteAccesses.
     *
     * @return array
     */
    public function getLanguages()
    {
        $translationSiteAccesses = array_intersect(
            $this->configResolver->getParameter('translation_siteaccesses'),
            $this->configResolver->getParameter('related_siteaccesses')
        );

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
