<?php

namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TimezoneCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        date_default_timezone_set('America/Argentina/Buenos_Aires');
    }
}
