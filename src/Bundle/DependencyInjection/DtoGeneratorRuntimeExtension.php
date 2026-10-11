<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Bundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class DtoGeneratorRuntimeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        Normalizers::register($container);
    }
}
