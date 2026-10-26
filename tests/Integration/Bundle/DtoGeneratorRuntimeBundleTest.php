<?php

declare(strict_types=1);

namespace Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Integration\Bundle;

use FilesystemIterator;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\AdditionalPropertiesNormalizer;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Box;
use Msstc4Symfony\DtoGeneratorBridge\Runtime\Test\Fixture\Collection\Basket;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * The bundle in a real Symfony application. Booting a kernel outlasts Infection's timeout, so the unit suite covers
 * the same code on a bare container.
 *
 * @coversNothing
 */
final class DtoGeneratorRuntimeBundleTest extends TestCase
{
    private string $project;

    private bool $handlesErrors = false;

    /** @var list<TestKernel> */
    private array $kernels = [];

    protected function setUp(): void
    {
        // Symfony's own deprecations on a newer PHP (5.4's ErrorHandler and E_STRICT on 8.4) are not the bundle's.
        $vendor = DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
        $previous = null;
        $previous = set_error_handler(static function (int $level, string $message, string $file = '', int $line = 0) use (&$previous, $vendor): bool {
            if (strpos($file, $vendor) !== false) {
                return true;
            }

            return is_callable($previous) && (bool) $previous($level, $message, $file, $line);
        }, E_USER_DEPRECATED | E_DEPRECATED);
        $this->handlesErrors = true;

        if (!class_exists(FrameworkBundle::class)) {
            self::markTestSkipped('symfony/framework-bundle is not installed; the CI matrix installs it.');
        }

        $this->project = sys_get_temp_dir() . '/dto-bridge-runtime-' . bin2hex(random_bytes(4));
        mkdir($this->project);
        file_put_contents($this->project . '/composer.json', '{}');
    }

    protected function tearDown(): void
    {
        foreach ($this->kernels as $kernel) {
            $kernel->shutdown();
        }

        if ($this->handlesErrors) {
            restore_error_handler();
        }

        if (!isset($this->project) || !is_dir($this->project)) {
            return;
        }

        $entries = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->project, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($entries as $entry) {
            assert($entry instanceof SplFileInfo);
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }

        rmdir($this->project);
    }

    /**
     * @requires PHP 8.0
     */
    public function testSpreadsAdditionalPropertiesInTheApplicationsSerializer(): void
    {
        $serializer = $this->service($this->bootedKernel(['serializer' => ['enabled' => true], 'property_info' => ['enabled' => true]]), 'serializer');

        self::assertInstanceOf(NormalizerInterface::class, $serializer);
        self::assertInstanceOf(DenormalizerInterface::class, $serializer);
        self::assertSame(['pears' => 1], $serializer->normalize(new Box(['pears' => 1])));
        $box = $serializer->denormalize(['apples' => 2], Box::class);
        self::assertInstanceOf(Box::class, $box);
        self::assertSame(['apples' => 2], $box->getExtra());
    }

    /**
     * @requires PHP 8.0
     */
    public function testReadsCollectionsInTheApplicationsSerializer(): void
    {
        if (!class_exists(Context::class)) {
            self::markTestSkipped('The fixture needs the attributes of symfony/serializer 6.4 or newer.');
        }

        $serializer = $this->service($this->bootedKernel(['serializer' => ['enabled' => true], 'property_info' => ['enabled' => true]]), 'serializer');

        self::assertInstanceOf(DenormalizerInterface::class, $serializer);
        self::assertInstanceOf(NormalizerInterface::class, $serializer);
        $basket = $serializer->denormalize(['tags' => [['name' => 'red']], 'prices' => [2]], Basket::class);
        self::assertInstanceOf(Basket::class, $basket);
        self::assertSame(['tags' => [['name' => 'red']], 'prices' => [2.0]], $serializer->normalize($basket));
    }

    public function testRegistersNoNormalizerWithoutTheObjectNormalizer(): void
    {
        $kernel = $this->bootedKernel(['serializer' => ['enabled' => true], 'property_access' => ['enabled' => false]]);

        self::assertTrue($this->testContainer($kernel)->has('serializer'));
        self::assertFalse($this->testContainer($kernel)->has(AdditionalPropertiesNormalizer::class));
    }

    public function testRegistersNoNormalizerWithoutTheSerializer(): void
    {
        $kernel = $this->bootedKernel(['serializer' => ['enabled' => false]]);

        self::assertFalse($this->testContainer($kernel)->has('serializer'));
        self::assertFalse($this->testContainer($kernel)->has(AdditionalPropertiesNormalizer::class));
    }

    /**
     * @param array<string, array<string, bool>> $frameworkConfig
     */
    private function bootedKernel(array $frameworkConfig): TestKernel
    {
        $kernel = new TestKernel($this->project, $frameworkConfig);
        $this->kernels[] = $kernel;
        $kernel->boot();

        return $kernel;
    }

    private function service(TestKernel $kernel, string $id): object
    {
        $service = $this->testContainer($kernel)->get($id);
        self::assertIsObject($service);

        return $service;
    }

    private function testContainer(TestKernel $kernel): ContainerInterface
    {
        $container = $kernel->getContainer()->get('test.service_container');
        self::assertInstanceOf(ContainerInterface::class, $container);

        return $container;
    }
}
