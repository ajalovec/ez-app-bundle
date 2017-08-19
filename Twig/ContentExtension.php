<?php

namespace Origammi\Bundle\EzAppBundle\Twig;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Location;
use Origammi\Bundle\EzAppBundle\Repository\ApiService;
use Origammi\Bundle\EzAppBundle\Service\ContentTypeResolver;
use Origammi\Bundle\EzAppBundle\Service\FieldResolver;
use Twig_Extension;

/**
 * Class ContentExtension
 *
 * @package   Origammi\Bundle\EzAppBundle\Twig
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi AG (http://origammi.co)
 */
class ContentExtension extends Twig_Extension
{
    /**
     * @var FieldResolver
     */
    private $fieldResolver;

    /**
     * @var ApiService
     */
    private $repositoryApi;

    /**
     * @var ContentTypeResolver
     */
    private $contentTypeResolver;

    /**
     * @param ContentTypeResolver $contentTypeResolver
     * @param ApiService          $repositoryApi
     * @param FieldResolver       $fieldResolver
     */
    public function __construct(
        ContentTypeResolver $contentTypeResolver,
        ApiService $repositoryApi,
        FieldResolver $fieldResolver
    ) {
        $this->fieldResolver       = $fieldResolver;
        $this->repositoryApi       = $repositoryApi;
        $this->contentTypeResolver = $contentTypeResolver;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'origammi_ezapp_twig_content_extension';
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            'app_load_content'      => new \Twig_Function_Method($this, 'loadContent'),
            'app_load_location'     => new \Twig_Function_Method($this, 'loadLocation'),
            'app_load_content_id'   => new \Twig_Function_Method($this, 'loadContentId'),
            'app_load_location_id'  => new \Twig_Function_Method($this, 'loadLocationId'),
            'app_is_content_type'   => new \Twig_Function_Method($this, 'isContentType'),
            'app_field_name'        => new \Twig_Function_Method($this, 'getFieldName'),
            'app_has_value'         => new \Twig_Function_Method($this, 'hasValue'),
            'app_field_value'       => new \Twig_Function_Method($this, 'getFieldValue', ['is_safe' => ['html']]),
            'app_image_value'       => new \Twig_Function_Method($this, 'getImageValue'),
            'app_content_type'      => new \Twig_Function_Method($this, 'getContentType'),
            'app_content_type_name' => new \Twig_Function_Method($this, 'getContentTypeName'),
        ];
    }

    /**
     * @param $id
     *
     * @return Content|Content[]
     */
    public function loadContent($id)
    {
        return $this->repositoryApi->loadContent($id);
    }

    /**
     * @param $id
     *
     * @return Location|Location[]
     */
    public function loadLocation($id)
    {
        return $this->repositoryApi->loadLocation($id);
    }


    /**
     * @param $id
     *
     * @return Content|Content[]
     */
    public function loadContentId($id)
    {
        return $this->repositoryApi->loadContent($id)->id;
    }

    /**
     * @param $id
     *
     * @return Location|Location[]
     */
    public function loadLocationId($id)
    {
        return $this->repositoryApi->loadLocation($id)->id;
    }

    /**
     * @param Content|Location|null $content
     * @param string|array          $contentTypeIdentifier
     *
     * @return bool
     */
    public function isContentType($content = null, $contentTypeIdentifier)
    {
        if ($content instanceof Content || $content instanceof Location) {

            return $content
                ? $this->contentTypeResolver->isContentType($contentTypeIdentifier, $content->contentInfo)
                : false;
        }

        return false;
    }

    /**
     * @param int|string|Location|Content $id
     *
     * @return \eZ\Publish\API\Repository\Values\ContentType\ContentType
     */
    public function getContentType($id)
    {
        return $this->contentTypeResolver->getContentType($id);
    }

    /**
     * @param int|string|Location|Content $id
     *
     * @return string
     */
    public function getContentTypeName($id)
    {
        return $this->contentTypeResolver->getContentType($id)->identifier;
    }

    /**
     * @param Content         $content
     * @param string|string[] $fieldNames Can be comma separated string ("prop1,prop2") or array (["prop1", "prop2"])
     *
     * @return null|string
     */
    public function getFieldName(Content $content, $fieldNames)
    {
        return $this->fieldResolver->resolveFieldName($content, $fieldNames);
    }

    /**
     * @param Content $content
     * @param string  $fieldName
     *
     * @return bool
     */
    public function hasValue(Content $content, $fieldName)
    {
        return $this->fieldResolver->hasValue($content, $fieldName);
    }

    /**
     * @param Content      $content
     * @param string|array $fieldName
     *
     * @return null|string
     */
    public function getFieldValue(Content $content, $fieldName)
    {
        return $this->fieldResolver->resolveFieldValue($content, $fieldName);
    }

    /**
     * @param Content      $content
     * @param string|array $fieldNames Can be comma separated string ("prop1,prop2") or array (["prop1", "prop2"])
     * @param string       $variationName
     *
     * @return array|null
     */
    public function getImageValue(Content $content, $fieldNames, $variationName = 'original')
    {
        return $this->fieldResolver->resolveImageField($content, $fieldNames, $variationName);
    }


}
