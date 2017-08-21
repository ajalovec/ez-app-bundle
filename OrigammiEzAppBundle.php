<?php
/**
 * Copyright (c) 2017.
 */

namespace Origammi\Bundle\EzAppBundle;

use Origammi\Bundle\EzAppBundle\DependencyInjection\Compiler\DefaultMainLanguageCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OrigammiEzAppBundle extends Bundle
{
    public function build(ContainerBuilder $builder)
    {
        parent::build($builder);

        $builder->addCompilerPass(new DefaultMainLanguageCompilerPass());
    }
}
