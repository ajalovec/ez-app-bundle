<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle\Twig\Extension;

use Origammi\Bundle\EzAppBundle\Repository\ContentApiService;
use Twig_Extension;
use Twig_Extension_GlobalsInterface;

class CoreExtension extends Twig_Extension implements Twig_Extension_GlobalsInterface
{
    /**
     * @var ContentApiService
     */
    private $contentService;

    public function __construct(ContentApiService $globalHelper)
    {
        $this->contentService = $globalHelper;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'origammi_ezapp_core';
    }

    /**
     * @return array
     */
    public function getGlobals()
    {
        return array('origammiez_content' => $this->contentService);
    }
}
