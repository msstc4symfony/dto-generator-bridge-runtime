<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Unit\Bundle;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\AdditionalPropertiesNormalizer;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Bundle\DependencyInjection\CompilerPass\DropOrphanNormalizerPass;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Bundle\DependencyInjection\DtoGeneratorRuntimeExtension;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Bundle\DtoGeneratorRuntimeBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class DtoGeneratorRuntimeBundleTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(Bundle::class)) {
            self::markTestSkipped('symfony/http-kernel is not installed; the CI matrix installs it.');
        }
    }

    /**
     * @requires PHP 8.0
     */
    public function testRegistersTheNormalizerAroundTheObjectNormalizer(): void
    {
        $container = new ContainerBuilder();
        (new DtoGeneratorRuntimeExtension())->load([], $container);

        $definition = $container->getDefinition(AdditionalPropertiesNormalizer::class);
        self::assertSame(AdditionalPropertiesNormalizer::class, $definition->getClass());
        self::assertEquals([
            new Reference('serializer.normalizer.object'),
            new Reference('serializer.mapping.class_metadata_factory'),
            new Reference('serializer.name_converter.metadata_aware'),
        ], $definition->getArguments());
        self::assertSame(['serializer.normalizer' => [[]]], $definition->getTags());
    }

    public function testDropsTheNormalizerWithoutTheObjectNormalizer(): void
    {
        $container = new ContainerBuilder();
        $container->register(AdditionalPropertiesNormalizer::class);

        (new DropOrphanNormalizerPass())->process($container);

        self::assertFalse($container->hasDefinition(AdditionalPropertiesNormalizer::class));
    }

    public function testKeepsTheNormalizerBesideTheObjectNormalizer(): void
    {
        $container = new ContainerBuilder();
        $container->register(AdditionalPropertiesNormalizer::class);
        $container->register('serializer.normalizer.object');

        (new DropOrphanNormalizerPass())->process($container);

        self::assertTrue($container->hasDefinition(AdditionalPropertiesNormalizer::class));
    }

    public function testDropsTheNormalizerJustAheadOfTheSerializerPass(): void
    {
        $container = new ContainerBuilder();
        $serializerPass = new DropOrphanNormalizerPass();
        $earlier = new DropOrphanNormalizerPass();
        $container->addCompilerPass($serializerPass, PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass($earlier, PassConfig::TYPE_BEFORE_OPTIMIZATION, 2);
        (new DtoGeneratorRuntimeBundle())->build($container);

        $passes = array_values(array_filter(
            $container->getCompilerPassConfig()->getBeforeOptimizationPasses(),
            static fn (object $pass): bool => $pass instanceof DropOrphanNormalizerPass,
        ));
        self::assertCount(3, $passes);
        self::assertSame($earlier, $passes[0]);
        self::assertNotSame($serializerPass, $passes[1]);
        self::assertSame($serializerPass, $passes[2]);
    }
}
