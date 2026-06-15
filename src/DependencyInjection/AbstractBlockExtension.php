<?php

declare(strict_types=1);

namespace Depa\SuluBlockHelperBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

abstract class AbstractBlockExtension extends Extension implements PrependExtensionInterface
{
    use BlockMetadataLoaderTrait;

    public function load(array $configs, ContainerBuilder $container): void
    {
        $blocksDir = $this->getBlocksDir();
        $metadata = $this->loadMetadataFromXml($blocksDir);

        $container->setParameter($this->getMetadataParameterName(), [
            'bundle'   => $this->getBundleName(),
            'package'  => $this->getPackageName(),
            'blocks'   => $metadata['blocks'],
            'children' => $metadata['children'],
        ]);

        $container->setParameter($this->getAlias() . '.blocks_dir', $blocksDir);
    }

    public function prepend(ContainerBuilder $container): void
    {
        if ($container->hasExtension('twig')) {
            $container->prependExtensionConfig('twig', [
                'paths' => [
                    $this->getViewsDir() => null,
                ],
            ]);
        }

        if ($container->hasExtension('sulu_admin')) {
            $container->prependExtensionConfig('sulu_admin', [
                'templates' => [
                    'block' => [
                        'directories' => [
                            $this->getSuluAdminTemplateKey() => $this->getBlocksDir(),
                        ],
                    ],
                ],
            ]);
        }
    }

    protected function getBlocksDir(): string
    {
        return $this->getReflectionDir() . '/../../Resources/config/blocks';
    }

    protected function getViewsDir(): string
    {
        return $this->getReflectionDir() . '/../../Resources/views';
    }

    private function getReflectionDir(): string
    {
        $reflection = new \ReflectionClass($this);
        $fileName = $reflection->getFileName();
        \assert(\is_string($fileName));

        return dirname($fileName);
    }

    abstract protected function getBundleName(): string;

    abstract protected function getPackageName(): string;

    abstract protected function getMetadataParameterName(): string;

    abstract protected function getSuluAdminTemplateKey(): string;
}
