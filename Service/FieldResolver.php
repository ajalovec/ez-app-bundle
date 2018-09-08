<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Service;

use eZ\Bundle\EzPublishCoreBundle\Imagine\AliasGenerator;
use eZ\Publish\API\Repository\Exceptions\InvalidVariationException;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Field as ContentField;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\Core\FieldType;
use eZ\Publish\Core\FieldType\RichText\Converter as RichTextConverterInterface;
use eZ\Publish\Core\Helper\FieldHelper;
use eZ\Publish\Core\Helper\TranslationHelper;
use eZ\Publish\Core\MVC\Exception\SourceImageNotFoundException;
use eZ\Publish\SPI\Variation\VariationHandler;
use Origammi\Bundle\EzAppBundle\Repository\ContentTypeApiService;

/**
 * Class FieldResolver
 *
 * @package   Origammi\Bundle\EzAppBundle\Service
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi AG (http://origammi.co)
 */
class FieldResolver
{
    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    /**
     * @var RichTextConverterInterface
     */
    private $richTextConverter;

    /**
     * @var AliasGenerator
     */
    private $imageVariationService;

    /**
     * @var \eZ\Publish\Core\Helper\TranslationHelper
     */
    protected $translationHelper;


    protected $contentTypeService;


    public function __construct(
        FieldHelper $fieldHelper,
        RichTextConverterInterface $richTextConverter,
        VariationHandler $imageVariationService,
        TranslationHelper $translationHelper,
        ContentTypeApiService $contentTypeService
    ) {
        $this->fieldHelper           = $fieldHelper;
        $this->richTextConverter     = $richTextConverter;
        $this->imageVariationService = $imageVariationService;
        $this->translationHelper     = $translationHelper;
        $this->contentTypeService    = $contentTypeService;
    }

    /**
     * Check if field exist and is not empty
     *
     * @param Content         $content
     * @param string|string[] $fieldNames Can be String 'prop1,prop2' or array ['prop1', 'prop2']
     *
     * @return null|string
     */
    public function getFieldName(Content $content, $fieldNames)
    {
        if (is_scalar($fieldNames)) {
            $fieldNames = explode(',', $fieldNames);
        }

        foreach ($fieldNames as $fieldName) {
            $fieldName = trim($fieldName);
            if (array_key_exists($fieldName, $content->fields) && false === $this->fieldHelper->isFieldEmpty($content, $fieldName)) {
                return $fieldName;
            }
        }

        return null;
    }


    /**
     * @param Content         $content
     * @param string|string[] $fieldName Can be String 'prop1,prop2' or array ['prop1', 'prop2']
     *
     * @return null|ContentField
     */
    public function getField(Content $content, $fieldName)
    {
        if ($fieldName = $this->getFieldName($content, $fieldName)) {
            return $this->translationHelper->getTranslatedField($content, $fieldName);
        }

        return null;
    }


    /**
     * @param Content|ContentType|int|string $content
     * @param string|string[]                $fieldName Can be String 'prop1,prop2' or array ['prop1', 'prop2']
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentException
     *
     * @return null|mixed
     */
    public function getFieldSettings($content, $fieldName)
    {
        if ($fieldName = $this->getFieldName($content, $fieldName)) {
            return $this->translationHelper->getTranslatedFieldDefinitionProperty(
                $this->contentTypeService->load($content),
                $fieldName,
                'FieldSettings'
            );
        }

        return null;
    }


    /**
     * @param Content         $content
     * @param string|string[] $fieldName Can be String 'prop1,prop2' or array ['prop1', 'prop2']
     *
     * @return bool
     */
    public function hasValue(Content $content, $fieldName)
    {
        return !!$this->getFieldName($content, $fieldName);
    }


    /**
     * Resolve text value from content for given fields, if none of the fields exists or are empty it returns null
     *
     * @param Content         $content
     * @param string|string[] $fieldNames Can be String 'prop1,prop2' or array ['prop1', 'prop2']
     * @param mixed           $params
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentException
     * @return null|mixed
     */
    public function getValue(Content $content, $fieldNames, $params = null)
    {
        // if a field doesn't exist or is empty we return null
        if (!$field = $this->getField($content, $fieldNames)) {
            return null;
        }

        $fieldName  = $field->fieldDefIdentifier;
        $fieldValue = $field->value;

        switch (true) {

            case $fieldValue instanceof FieldType\Date\Value:
            case $fieldValue instanceof FieldType\DateAndTime\Value:
            case $fieldValue instanceof FieldType\Time\Value:
                return $this->getDateValue($content, $fieldName, $params);

            case $fieldValue instanceof FieldType\Relation\Value:
                return $fieldValue->destinationContentId;

            case $fieldValue instanceof FieldType\RelationList\Value:
                return $fieldValue->destinationContentIds;

            case $fieldValue instanceof FieldType\Selection\Value:
                return $this->getSelectValue($content, $fieldName);

            case $fieldValue instanceof FieldType\Checkbox\Value:
                return $fieldValue->bool;

            case $fieldValue instanceof FieldType\Integer\Value:
            case $fieldValue instanceof FieldType\Float\Value:
                return $fieldValue->value;

            case $fieldValue instanceof FieldType\TextBlock\Value:
            case $fieldValue instanceof FieldType\TextLine\Value:
                return $fieldValue->text;

            case $fieldValue instanceof FieldType\RichText\Value:
                return $this->richTextConverter->convert($fieldValue->xml)->saveHTML();

            case $fieldValue instanceof FieldType\EmailAddress\Value:
                return $fieldValue->email;

            case $fieldValue instanceof FieldType\Image\Value:
                return $this->getImageValue($content, $fieldName, $params);

//            case $fieldValue instanceof FieldType\Media\Value:
//                return $fieldValue;

//            case $fieldValue instanceof FieldType\Url\Value:
//                return $fieldValue;
//
//            case $fieldValue instanceof FieldType\MapLocation\Value:
//                return $fieldValue;

            case $fieldValue instanceof FieldType\Keyword\Value:
                return $fieldValue->values;

            case $fieldValue instanceof FieldType\ISBN\Value:
                return $fieldValue->isbn;

//            case $fieldValue instanceof FieldType\Page\Value:
//                return $fieldValue;
//
//            case $fieldValue instanceof FieldType\Rating\Value:
//                return $fieldValue;
//
//            case $fieldValue instanceof FieldType\User\Value:
//                return $fieldValue;

            default:
                return $fieldValue;
        }
    }


    /**
     * @param Content          $content
     * @param string|string[]  $fieldName Can be String 'prop1,prop2' or array ['prop1', 'prop2']
     * @param null|bool|string $format
     *
     * @return string|null
     */
    public function getDateValue(Content $content, $fieldName, $format = null)
    {
        if ($field = $this->getField($content, $fieldName)) {
            $fieldValue = $field->value;

            if ($field->value instanceof FieldType\Date\Value) {
                $fieldValue = $field->value->date;
            } elseif ($field->value instanceof FieldType\DateAndTime\Value) {
                $fieldValue = $field->value->value;
            } elseif ($field->value instanceof FieldType\Time\Value) {
                $fieldValue = new \DateTime("@{$field->value->time}");
            }

            if ($fieldValue instanceof \DateTime) {
                if ($format) {
                    return $fieldValue->format(is_string($format) ? $format : $field->value->stringFormat);
                }

                return $fieldValue;
            }
        }

        return null;
    }


    /**
     * @param Content         $content
     * @param string|string[] $fieldName Can be String 'prop1,prop2' or array ['prop1', 'prop2']
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\Core\Base\Exceptions\InvalidArgumentException
     * @return array|mixed|null
     */
    public function getSelectValue(Content $content, $fieldName)
    {
        if ($field = $this->getField($content, $fieldName)) {
            if ($field->value instanceof FieldType\Selection\Value) {
                $fieldSettings = $this->getFieldSettings($content, $field->fieldDefIdentifier);
                $fieldValue    = array_intersect_key($fieldSettings['options'], array_flip($field->value->selection));

                if (!$fieldSettings['isMultiple']) {
                    return array_shift($fieldValue);
                }

                return $fieldValue;
            }
        }

        return null;
    }


    /**
     * Resolve text value from content for given fields
     *
     * @param Content         $content
     * @param string|string[] $fieldName Can be String 'prop1,prop2' or array ['prop1', 'prop2']
     * @param null|int        $maxLength Slice output string to specific length
     * @param bool|string     $stripTags See argument $allowable_tags for @link http://php.net/manual/en/function.strip-tags.php
     *
     * @return string
     */
    public function getTextValue(Content $content, $fieldName, $maxLength = null, $stripTags = false)
    {
        if ($field = $this->getField($content, $fieldName)) {
            $fieldValue = $field->value;

            if ($fieldValue instanceof FieldType\RichText\Value) {
                $fieldValue = $this->richTextConverter->convert($fieldValue->xml)->saveHTML();
            } else {
                $fieldValue = (string)$fieldValue;
            }

            if ($stripTags) {
                $stripTags  = is_string($stripTags) ? $stripTags : null;
                $fieldValue = strip_tags($fieldValue, $stripTags);
            }

            if (is_int($maxLength) && $maxLength > 0) {
                $fieldValue = substr($fieldValue, 0, $maxLength);
            }

            return $fieldValue;
        }

        return null;
    }


    /**
     * Resolve image value from content for given fields
     *
     * @param Content         $content
     * @param string|string[] $fieldName Can be String 'prop1,prop2' or array ['prop1', 'prop2']
     * @param string          $variationName
     *
     * @return ImageField|null ['uri' => 'url/to/image', 'value' => ['image fields']]
     */
    public function getImageValue(Content $content, $fieldName, $variationName = null)
    {
        if ($field = $this->getField($content, $fieldName)) {
            $original = $field->value;

            if ($original instanceof FieldType\Image\Value) {
                if ($thumb = $this->resolveImageVariation($field, $content, $variationName)) {

                    return new ImageField($original, $thumb);
//                    return [ 'value' => $original, 'uri' => $thumb->uri, 'image' => $thumb ];
                }
            }
        }

        return null;
    }


    /**
     * @param ContentField $field
     * @param Content      $content
     * @param null|string  $variationName
     *
     * @return null|\eZ\Publish\SPI\Variation\Values\Variation
     */
    private function resolveImageVariation(ContentField $field, Content $content, $variationName = null)
    {
        try {
            return $this->imageVariationService->getVariation(
                $field,
                $content->getVersionInfo(),
                (string)$variationName ?: 'original'
            );
        } catch (InvalidVariationException $e) {
            if (isset($this->logger)) {
                $this->logger->error("Couldn't get variation '{$variationName}' for image with id {$field->value->id}");
            }
        } catch (SourceImageNotFoundException $e) {
            if (isset($this->logger)) {
                $this->logger->error(
                    "Couldn't create variation '{$variationName}' for image with id {$field->value->id} because source image can't be found"
                );
            }
        } catch (\InvalidArgumentException $e) {
            if (isset($this->logger)) {
                $this->logger->error(
                    "Couldn't create variation '{$variationName}' for image with id {$field->value->id} because an image could not be created from the given input"
                );
            }
        }

        return null;
    }


    public function getFileMimeType($file)
    {
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $type  = finfo_file($finfo, $file);
            finfo_close($finfo);
        } else {
            $type = mime_content_type($file);
        }

        return $type;
    }


    /**
     * @deprecated Use getValue method instead
     */
    public function resolveFieldValue(Content $content, $fieldNames, $params = null)
    {
        return $this->getValue($content, $fieldNames, $params);
    }


    /**
     * @deprecated Use getValue method instead
     */
    public function resolveFieldName(Content $content, $fieldNames)
    {
        return $this->getFieldName($content, $fieldNames);
    }


    /**
     * @deprecated Use getValue method instead
     */
    public function resolveImageField(Content $content, $fieldNames, $variationName = null)
    {
        return $this->getImageValue($content, $fieldNames, $variationName);
    }


    /**
     * @deprecated Use getValue method instead
     */
    public function resolveTextField(Content $content, $fieldNames, $maxLength = null, $stripTags = false)
    {
        return $this->getTextValue($content, $fieldNames, $maxLength, $stripTags);
    }
}
