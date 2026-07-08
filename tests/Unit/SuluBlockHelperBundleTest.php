<?php

declare(strict_types=1);

namespace Depa\SuluBlockHelperBundle\Tests\Unit;

use Depa\SuluBlockHelperBundle\SuluBlockHelperBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

class SuluBlockHelperBundleTest extends TestCase
{
    private ContainerBuilder $container;
    private SuluBlockHelperBundle $bundle;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        // AbstractBundle's internal BundleExtension needs these to build the
        // ContainerConfigurator passed to prependExtension()/loadExtension().
        $this->container->setParameter('kernel.environment', 'test');
        $this->container->setParameter('kernel.build_dir', sys_get_temp_dir());
        $this->bundle = new SuluBlockHelperBundle();
    }

    private function getExtension(): ExtensionInterface&PrependExtensionInterface
    {
        $extension = $this->bundle->getContainerExtension();
        self::assertInstanceOf(ExtensionInterface::class, $extension);
        self::assertInstanceOf(PrependExtensionInterface::class, $extension);

        return $extension;
    }

    public function testLoadExtensionExposesBlockMetadata(): void
    {
        $this->getExtension()->load([], $this->container);

        self::assertTrue($this->container->hasParameter('sulu_block_helper.bundle_metadata'));
        self::assertTrue($this->container->hasParameter('sulu_block_helper.blocks_dir'));
    }

    public function testPrependRegistersTwigPathWhenTwigIsAvailable(): void
    {
        $twigExtension = $this->createMock(ExtensionInterface::class);
        $twigExtension->method('getAlias')->willReturn('twig');
        $this->container->registerExtension($twigExtension);

        $this->getExtension()->prepend($this->container);

        $configs = $this->container->getExtensionConfig('twig');
        self::assertNotEmpty($configs);
        self::assertArrayHasKey('paths', $configs[0]);
    }

    public function testPrependDoesNotFailWithoutTwig(): void
    {
        $this->getExtension()->prepend($this->container);
        $this->addToAssertionCount(1);
    }

    public function testTwigPathPointsToExistingDirectory(): void
    {
        $twigExtension = $this->createMock(ExtensionInterface::class);
        $twigExtension->method('getAlias')->willReturn('twig');
        $this->container->registerExtension($twigExtension);

        $this->getExtension()->prepend($this->container);

        $configs = $this->container->getExtensionConfig('twig');
        $paths = $configs[0]['paths'] ?? [];
        foreach (array_keys($paths) as $path) {
            self::assertDirectoryExists((string) $path);
        }
    }
}
