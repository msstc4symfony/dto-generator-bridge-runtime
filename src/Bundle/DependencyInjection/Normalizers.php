<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Bundle\DependencyInjection;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\AdditionalPropertiesNormalizer;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\CollectionNormalizer;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use WeakMap;

/**
 * The normalizers' services, which DtoGeneratorRuntimeBundle and the bridge's bundle register alike: one id, one
 * definition, so either bundle or both give the same container. Needs DropOrphanNormalizerPass beside it.
 *
 * @api
 */
final class Normalizers
{
    private function __construct()
    {
    }

    public static function register(ContainerBuilder $container): void
    {
        // PHP 7.4 cannot load the normalizer, whose signatures use "mixed". WeakMap came with PHP 8.0 and no polyfill
        // declares it (symfony/polyfill-php80 does declare Attribute); a version comparison Rector's PHP 7.4 set would
        // fold to false, and Infection would mutate its number. Without the serializer nothing uses the private service
        // and the container drops it with its references.
        if (!class_exists(WeakMap::class)) {
            return;
        }

        $container->register(AdditionalPropertiesNormalizer::class, AdditionalPropertiesNormalizer::class)
            ->setArguments([
                new Reference('serializer.normalizer.object'),
                new Reference('serializer.mapping.class_metadata_factory'),
                new Reference('serializer.name_converter.metadata_aware'),
            ])
            ->addTag('serializer.normalizer')
        ;
        $container->register(CollectionNormalizer::class, CollectionNormalizer::class)->addTag('serializer.normalizer');
    }
}
