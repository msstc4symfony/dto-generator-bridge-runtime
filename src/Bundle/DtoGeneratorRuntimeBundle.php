<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Bundle;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\Bundle\DependencyInjection\CompilerPass\DropOrphanNormalizerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The normalizer of `$additionalProperties` in a Symfony application, without the generator: the bridge and the
 * generator can stay development dependencies.
 *
 * @api
 */
final class DtoGeneratorRuntimeBundle extends Bundle
{
    /** Ahead of SerializerPass, which FrameworkBundle adds at the default priority 0. */
    private const BEFORE_SERIALIZER_PASS = 1;

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DropOrphanNormalizerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, self::BEFORE_SERIALIZER_PASS);
    }
}
