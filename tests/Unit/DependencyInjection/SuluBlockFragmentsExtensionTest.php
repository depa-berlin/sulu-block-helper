<?php

declare(strict_types=1);

namespace Depa\SuluBlockFragmentsBundle\Tests\Unit\DependencyInjection;

use Depa\SuluBlockFragmentsBundle\DependencyInjection\SuluBlockFragmentsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class SuluBlockFragmentsExtensionTest extends TestCase
{
    private ContainerBuilder $container;
    private SuluBlockFragmentsExtension $extension;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->extension = new SuluBlockFragmentsExtension();
    }

    public function testLoadDoesNotThrow(): void
    {
        $this->extension->load([], $this->container);
        $this->addToAssertionCount(1);
    }

    public function testPrependRegistersTwigPathWhenTwigIsAvailable(): void
    {
        $twigExtension = $this->createMock(ExtensionInterface::class);
        $twigExtension->method('getAlias')->willReturn('twig');
        $this->container->registerExtension($twigExtension);

        $this->extension->prepend($this->container);

        $configs = $this->container->getExtensionConfig('twig');
        self::assertNotEmpty($configs);
        self::assertArrayHasKey('paths', $configs[0]);
    }

    public function testPrependDoesNotFailWithoutTwig(): void
    {
        $this->extension->prepend($this->container);
        $this->addToAssertionCount(1);
    }

    public function testTwigPathPointsToExistingDirectory(): void
    {
        $twigExtension = $this->createMock(ExtensionInterface::class);
        $twigExtension->method('getAlias')->willReturn('twig');
        $this->container->registerExtension($twigExtension);

        $this->extension->prepend($this->container);

        $configs = $this->container->getExtensionConfig('twig');
        $paths = $configs[0]['paths'] ?? [];
        foreach (array_keys($paths) as $path) {
            self::assertDirectoryExists((string) $path);
        }
    }
}
