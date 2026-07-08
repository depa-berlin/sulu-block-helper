<?php

declare(strict_types=1);

namespace Depa\SuluBlockHelperBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

class SuluBlockHelperBundle extends AbstractBlockBundle
{
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        parent::loadExtension($config, $configurator, $container);

        $configurator->import($this->getPath() . '/config/services.yaml');
    }
}
