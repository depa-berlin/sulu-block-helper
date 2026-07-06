<?php

declare(strict_types=1);

namespace Depa\SuluBlockHelperBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SuluBlockHelperExtension extends AbstractBlockExtension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        parent::load($configs, $container);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../Resources/config'));
        $loader->load('services.yaml');
    }
}
