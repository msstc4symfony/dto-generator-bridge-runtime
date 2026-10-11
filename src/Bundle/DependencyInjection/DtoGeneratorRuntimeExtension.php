<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Bundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * @internal
 */
final class DtoGeneratorRuntimeExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // No options: a key under dto_generator_runtime is an error, not silently ignored.
        $this->processConfiguration(new Configuration(), $configs);
        Normalizers::register($container);
    }
}
