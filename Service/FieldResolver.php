<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Service;

use eZ\Publish\API\Repository\Exceptions\InvalidVariationException;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Field as ContentField;
use eZ\Publish\API\Repository\Values\Content\VersionInfo;
use eZ\Publish\Core\FieldType;
use eZ\Publish\Core\FieldType\RichText\Converter as RichTextConverterInterface;
use eZ\Publish\Core\Helper\FieldHelper;
use eZ\Publish\Core\Helper\TranslationHelper;
use eZ\Publish\Core\MVC\Exception\SourceImageNotFoundException;
use eZ\Publish\SPI\Variation\VariationHandler;

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
     * @var VariationHandler
     */
    private $imageVariationService;

    /**
     * @var \eZ\Publish\Core\Helper\TranslationHelper
     */
    protected $translationHelper;


    public function __construct(
        FieldHelper $fieldHelper,
        RichTextConverterInterface $richTextConverter,
        VariationHandler $imageVariationService,
        TranslationHelper $translationHelper
    ) {
        $this->fieldHelper           = $fieldHelper;
        $this->richTextConverter     = $richTextConverter;
        $this->imageVariationService = $imageVariationService;
        $this->translationHelper     = $translationHelper;
    }

    /**
     * @param Content $content
     * @param string  $fieldName
     *
     * @return bool
     */
    public function hasValue(Content $content, $fieldName)
    {
        return array_key_exists($fieldName, $content->fields) && false === $this->fieldHelper->isFieldEmpty($content, $fieldName);
    }

    /**
     * @param Content         $content
     * @param string|string[] $fieldNames Can be comma separated string ("prop1,prop2") or array (["prop1", "prop2"])
     *
     * @return null|string
     */
    public function resolveFieldName(Content $content, $fieldNames)
    {
        if (is_scalar($fieldNames)) {
            $fieldNames = explode(',', $fieldNames);
        }

        foreach ($fieldNames as $fieldName) {
            if (array_key_exists($fieldName, $content->fields) && false === $this->fieldHelper->isFieldEmpty($content, $fieldName)) {
                return $fieldName;
            }
        }

        return null;
    }

    /**
     * Resolve text value from content for given fields
     *
     * @param Content      $content
     * @param string|array $fieldName Can be comma separated string ("prop1,prop2") or array (["prop1", "prop2"])
     *
     * @return null|string|mixed
     */
    public function resolveFieldValue(Content $content, $fieldName, array $params = [])
    {
        if (array_key_exists($fieldName, $content->fields) && false === $this->fieldHelper->isFieldEmpty($content, $fieldName)) {
            $field = $this->translationHelper->getTranslatedField($content, $fieldName);

            # TODO: improve validation
            switch (get_class($field->value)) {
                case FieldType\RichText\Value::class:
                    return $this->richTextConverter->convert($field->value->xml)->saveHTML();
                case FieldType\TextLine\Value::class:
                case FieldType\TextBlock\Value::class:
                    return $field->value->text;
                case FieldType\EmailAddress\Value::class:
                    return $field->value->email;
                case FieldType\Float\Value::class:
                case FieldType\Integer\Value::class:
                    return $field->value->value;
                case FieldType\Checkbox\Value::class:
                    return $field->value->bool;
                case FieldType\Selection\Value::class:
                    return $field->value->selection;
                case FieldType\Image\Value::class:
                    $image = $this->getImageVariation(
                        $field,
                        $content->getVersionInfo(),
                        isset($params['variationName']) ? $params['variationName'] : 'original'
                    );

                    return ['value' => $field->value, 'uri' => $image->uri];
                default:
                    return $field->value;
            }
        }

        return null;
    }

    /**
     * Resolve text value from content for given fields
     *
     * @param Content      $content
     * @param string|array $fieldNames Can be comma separated string ("prop1,prop2") or array (["prop1", "prop2"])
     * @param null|int     $maxLength  Slice output string to specific length
     * @param bool|string  $stripTags  See argument $allowable_tags for @link http://php.net/manual/en/function.strip-tags.php
     *
     * @return null|string
     */
    public function resolveTextField(Content $content, $fieldNames, $maxLength = null, $stripTags = false)
    {
        if (is_scalar($fieldNames)) {
            $fieldNames = explode(',', $fieldNames);
        }

        $value = '';

        foreach ($fieldNames as $fieldName) {
            if (array_key_exists($fieldName, $content->fields) && false === $this->fieldHelper->isFieldEmpty($content, $fieldName)) {
                $value = $this->translationHelper->getTranslatedField($content, $fieldName)->value;
                # TODO: improve validation
                if ($value instanceof FieldType\RichText\Value) {
                    $value = $this->richTextConverter->convert($value->xml)->saveHTML();
                } else {
                    $value = (string)$value;
                }

                break;
            }
        }

        if ($stripTags) {
            $stripTags = is_string($stripTags) ? $stripTags : null;
            $value     = strip_tags($value, $stripTags);
        }

        if (is_int($maxLength) && $maxLength > 0) {
            $value = substr($value, 0, $maxLength);
        }

        return $value;
    }

    /**
     * Resolve image value from content for given fields
     *
     * @param Content      $content
     * @param string|array $fieldNames Finds first populatd field. Can be comma separated string ("prop1,prop2") or array (["prop1", "prop2"])
     * @param string       $variationName
     *
     * @return array|null ['uri' => 'url/to/image', 'value' => ['image fields']]
     */
    public function resolveImageField(Content $content, $fieldNames, $variationName = 'original')
    {
        if (is_scalar($fieldNames)) {
            $fieldNames = explode(',', $fieldNames);
        }

        foreach ($fieldNames as $fieldName) {
            if (array_key_exists($fieldName, $content->fields) && false === $this->fieldHelper->isFieldEmpty($content, $fieldName)) {
                $field = $this->translationHelper->getTranslatedField($content, $fieldName);
                # TODO: add validation
                $image = $this->getImageVariation(
                    $field,
                    $content->getVersionInfo(),
                    $variationName
                );

                return ['value' => $field->value, 'uri' => $image->uri];
            }
        }

        return null;
    }

    private function getImageVariation(ContentField $field, VersionInfo $versionInfo, $variationName)
    {
        try {
            return $this->imageVariationService->getVariation($field, $versionInfo, $variationName);
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
    }
}
