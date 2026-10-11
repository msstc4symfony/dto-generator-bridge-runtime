<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Integration\Bundle;

use Msstc4Symfony\DtoGeneratorBridge\Runtime\Bundle\DtoGeneratorRuntimeBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

/**
 * A Symfony application with FrameworkBundle and the runtime bundle, in a temporary project.
 */
final class TestKernel extends Kernel
{
    private string $project;

    /** @var array<string, array<string, bool>> */
    private array $frameworkConfig;

    /**
     * @param array<string, array<string, bool>> $frameworkConfig more of the framework section
     */
    public function __construct(string $project, array $frameworkConfig)
    {
        $this->project = $project;
        $this->frameworkConfig = $frameworkConfig;
        parent::__construct('test', true);
    }

    /**
     * @return iterable<FrameworkBundle|DtoGeneratorRuntimeBundle>
     */
    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new DtoGeneratorRuntimeBundle()];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', ['secret' => 'test', 'test' => true] + $this->frameworkConfig);
        });
    }

    public function getProjectDir(): string
    {
        return $this->project;
    }

    public function getCacheDir(): string
    {
        // One container per config: a kernel reuses the container compiled in its cache directory.
        return $this->project . '/var/cache/' . md5((string) json_encode($this->frameworkConfig));
    }

    public function getLogDir(): string
    {
        return $this->project . '/var/log';
    }
}
