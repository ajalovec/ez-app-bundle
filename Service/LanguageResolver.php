<?php

namespace Origammi\Bundle\EzAppBundle\Service;

use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\Core\Base\Exceptions\NotFoundException;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\Locale\LocaleConverterInterface;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use eZ\Publish\Core\Repository\URLAliasService;
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
     * @var URLAliasService
     */
    private $urlAliasService;


    /**
     * @param string                                     $defaultLanguageCode
     * @param array                                      $siteAccessesByLanguage
     * @param SiteAccess                                 $siteAccess
     * @param ConfigResolverInterface                    $configResolver
     * @param \eZ\Publish\API\Repository\URLAliasService $urlAliasService
     */
    public function __construct(
        $defaultLanguageCode,
        $siteAccessesByLanguage,
        SiteAccess $siteAccess,
        ConfigResolverInterface $configResolver,
        \eZ\Publish\API\Repository\URLAliasService $urlAliasService
    ) {
        $this->defaultLanguage        = $defaultLanguageCode;
        $this->siteAccessesByLanguage = $siteAccessesByLanguage;
        $this->siteaccess             = $siteAccess;
        $this->configResolver         = $configResolver;
        $this->urlAliasService        = $urlAliasService;
    }

    /**
     * @param LocaleConverterInterface $localeConverter
     */
    public function setLocaleConverter(LocaleConverterInterface $localeConverter)
    {
        $this->localeConverter = $localeConverter;
    }


    /**
     * @return LocaleConverterInterface
     */
    public function getLocaleConverter()
    {
        return $this->localeConverter;
    }

    /**
     * @return string
     */
    public function getDefaultSiteAccess()
    {
        return $this->configResolver->getParameter('default', 'ezsettings', 'siteacess');
    }


    /**
     * @return SiteAccess
     */
    public function getSiteAccess()
    {
        return $this->siteaccess;
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
     * @param bool $posix
     * @return string
     */
    public function getDefaultLanguage($posix = null)
    {
        return !$posix ? $this->defaultLanguage : $this->localeConverter->convertToPOSIX($this->defaultLanguage);
    }

    /**
     * @param bool $posix
     *
     * @return string
     */
    public function getLanguage($posix = null)
    {
        $lang = $this->configResolver->getParameter('languages')[0];
        return !$posix ? $lang : $this->localeConverter->convertToPOSIX($lang);
    }


    /**
     * @param bool $posix
     * @return array
     */
    public function getLanguages($posix = null)
    {
        $langs = (array)$this->configResolver->getParameter('languages');
        return !$posix ? $langs : array_map([$this->localeConverter, 'convertToPOSIX'], $langs);
    }

    /**
     * @param null|string $siteaccess
     * @param bool $posix
     *
     * @return string
     */
    public function getLanguageForSiteAccess($siteaccess = null, $posix = null)
    {
        $lang = $this->configResolver->getParameter('languages', null, $siteaccess)[0];
        return !$posix ? $lang : $this->localeConverter->convertToPOSIX($lang);
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
     * @param bool $posix
     * @return array
     */
    public function getTranslationLanguages($posix = null)
    {
        $translationSiteAccesses = $this->getTranslationSiteAccesses();

        $availableLanguages = [];

        foreach ($translationSiteAccesses as $sa) {
            $languages = $this->configResolver->getParameter('languages', null, $sa);

            $availableLanguages[$sa] = array_shift($languages);
        }

        return !$posix ? array_unique($availableLanguages) : array_map([$this->localeConverter, 'convertToPOSIX'], array_unique($availableLanguages));
    }

    /**
     * @return array
     */
    public function getLanguagesArray()
    {
        $languages = [];

        foreach ($this->getTranslationLanguages() as $siteaccess => $lang) {
            $languages[] = [
                'siteaccess' => $siteaccess,
                'lang'       => $lang,
                'locale'     => $this->localeConverter->convertToPOSIX($lang),
            ];
        }

        return $languages;
    }


    /**
     * @param Location    $location
     * @param string|null $languageCode
     *
     * @return bool
     */
    public function isLocationLangAvailable(Location $location, $languageCode = null)
    {
        $aliases = $this->urlAliasService->listLocationAliases($location, false, $languageCode ?: $this->getLanguage(), true);

        if (count($aliases) === 0) {
            $aliases = $this->urlAliasService->listLocationAliases($location, false, $this->defaultLanguage, true);

            if (count($aliases)) {
                $alias = $aliases[0];

                return $alias->alwaysAvailable;
            }

            return false;
        }

        return true;
    }
}
