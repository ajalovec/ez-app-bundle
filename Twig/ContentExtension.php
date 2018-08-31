<?php

namespace Origammi\Bundle\EzAppBundle\Twig;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\VersionInfo;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use Origammi\Bundle\EzAppBundle\Repository\ContentApiService;
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
     * @var ContentApiService
     */
    private $contentApi;

    /**
     * @var ContentTypeResolver
     */
    private $contentTypeResolver;

    /**
     * @var FieldResolver
     */
    private $fieldResolver;


    /**
     * @param ContentApiService   $contentApi
     * @param ContentTypeResolver $contentTypeResolver
     * @param FieldResolver       $fieldResolver
     */
    public function __construct(
        ContentApiService $contentApi,
        ContentTypeResolver $contentTypeResolver,
        FieldResolver $fieldResolver
    ) {
        $this->contentApi          = $contentApi;
        $this->contentTypeResolver = $contentTypeResolver;
        $this->fieldResolver       = $fieldResolver;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'origammi_ez_app_content';
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('app_load_content', [ $this->contentApi, 'load' ]),
            new \Twig_SimpleFunction('app_load_content_list', [ $this->contentApi, 'findByIds' ]),
            new \Twig_SimpleFunction('app_load_content_children', [ $this->contentApi, 'findByParent' ]),

            new \Twig_SimpleFunction('app_is_content_type', [ $this, 'isContentType' ]),
//            new \Twig_SimpleFunction('app_content_type_name', [ $this, 'getContentTypeName' ]),
            new \Twig_SimpleFunction('app_content_type', [ $this->contentTypeResolver, 'getContentType' ]),


            new \Twig_SimpleFunction('app_has_value', [ $this->fieldResolver, 'hasValue' ]),
            new \Twig_SimpleFunction('app_field_name', [ $this->fieldResolver, 'getFieldName' ]),
            new \Twig_SimpleFunction('app_field_value', [ $this->fieldResolver, 'getValue' ], [ 'is_safe' => [ 'html' ] ]),
            new \Twig_SimpleFunction('app_image_value', [ $this->fieldResolver, 'getImageValue' ]),
        ];
    }


    /**
     * @param int|string|Location|VersionInfo|ContentType|ContentInfo|Content $content Can be either id, identifier, Location, VersionInfo, ContentType, ContentInfo or Content object
     * @param string|array                                                    $contentTypeIdentifier
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @return bool
     */
    public function isContentType($content = null, $contentTypeIdentifier)
    {
        return $content
            ? $this->contentTypeResolver->isContentType($contentTypeIdentifier, $content)
            : false;
    }
//
//    /**
//     * @param int|string|Location|VersionInfo|ContentType|ContentInfo|Content $id Can be either id, identifier, Location, VersionInfo, ContentType, ContentInfo or Content object
//     *
//     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
//     * @return string
//     */
//    public function getContentTypeName($id)
//    {
//        return $this->contentTypeResolver->getContentType($id)->identifier;
//    }

//    /**
//     * @param Content         $content
//     * @param string|string[] $fieldNames Can be comma separated string ("prop1,prop2") or array (["prop1", "prop2"])
//     *
//     * @return null|string
//     */
//    public function getFieldName(Content $content, $fieldNames)
//    {
//        return $this->fieldResolver->resolveFieldName($content, $fieldNames);
//    }
//
//    /**
//     * @param Content      $content
//     * @param string|array $fieldName
//     *
//     * @return null|string
//     */
//    public function getFieldValue(Content $content, $fieldName)
//    {
//        return $this->fieldResolver->resolveFieldValue($content, $fieldName);
//    }
//
//    /**
//     * @param Content      $content
//     * @param string|array $fieldNames Can be comma separated string ("prop1,prop2") or array (["prop1", "prop2"])
//     * @param string       $variationName
//     *
//     * @return array|null
//     */
//    public function getImageValue(Content $content, $fieldNames, $variationName = 'original')
//    {
//        return $this->fieldResolver->getImageValue($content, $fieldNames, $variationName);
//    }


}
