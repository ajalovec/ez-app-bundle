<?php
/**
 * Copyright (c) 2018.
 */

namespace Origammi\Bundle\EzAppBundle\Service;

use eZ\Publish\Core\FieldType;

/**
 * Class ImageField
 *
 * @package   Origammi\Bundle\EzAppBundle\Service
 * @author    Andraž Jalovec <andraz.jalovec@origammi.co>
 * @copyright 2017 Origammi AG (http://origammi.co)
 */
class ImageField
{
    /**
     * @var FieldType\Image\Value
     */
    public $original;

    /**
     * @var \eZ\Publish\SPI\Variation\Values\ImageVariation
     */
    public $thumb;

    public function __construct($original, $thumb)
    {
        $this->original = $original;
        $this->thumb    = $thumb;
    }

    public function getValue()
    {
        return $this->original;
    }

    public function getUri()
    {
        return str_replace(' ', '%20', $this->thumb->uri);
//        return $this->thumb->uri;
    }

    public function getOriginalUri()
    {
        return str_replace(' ', '%20', $this->original->uri);
//        return $this->original->uri;
    }

    public function getFileName()
    {
        return $this->thumb->fileName;
    }

    public function getAltText()
    {
        return $this->original->alternativeText;
    }

    public function getFileSize()
    {
        return $this->thumb->fileSize ?: $this->original->fileSize;
    }

    public function getMimeType()
    {
        return $this->thumb->mimeType;
    }

    public function getWidth()
    {
        return $this->thumb->width ?: $this->original->width;
    }

    public function getHeight()
    {
        return $this->thumb->height ?: $this->original->height;
    }

    //id: "6/5/2/3/3256-1-ger-CH/time - 1.jpg"
    //alternativeText: "Content Marketing braucht einen Plan und Zeit"
    //fileName: "time - 1.jpg"
    //fileSize: 807200
    //uri: "/var/site/storage/images/6/5/2/3/3256-1-ger-CH/time - 1.jpg"
    //imageId: "314-3256-7"
    //inputUri: null
    //width: "1602"
    //height: "1067"
}
