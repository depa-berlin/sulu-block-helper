<?php

declare(strict_types=1);

namespace Depa\SuluBlockHelperBundle;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Base bundle for the depa/sulu-block-* family.
 *
 * Registers the bundle's block-template directory with sulu_admin and its Twig
 * templates, and exposes the block metadata as container parameters. Paths are
 * derived from getPath() (the repo root, provided by AbstractBundle's modern
 * directory layout), so the flat `config/` + `templates/` structure is used.
 */
abstract class AbstractBlockBundle extends AbstractBundle
{
    use BlockMetadataLoaderTrait;

    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $blocksDir = $this->getBlocksDir();
        $metadata = $this->loadMetadataFromXml($blocksDir);

        $container->setParameter($this->getMetadataParameterName(), [
            'bundle' => $this->getName(),
            'package' => $this->getPackageName(),
            'blocks' => $metadata['blocks'],
            'children' => $metadata['children'],
        ]);

        $container->setParameter($this->getBlockAlias() . '.blocks_dir', $blocksDir);
    }

    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        if ($container->hasExtension('twig') && \is_dir($views = $this->getViewsDir())) {
            $container->prependExtensionConfig('twig', [
                'paths' => [
                    $views => null,
                ],
            ]);
        }

        if ($container->hasExtension('sulu_admin') && \is_dir($blocks = $this->getBlocksDir())) {
            $container->prependExtensionConfig('sulu_admin', [
                'templates' => [
                    'block' => [
                        'directories' => [
                            $this->getBlockAlias() => $blocks,
                        ],
                    ],
                ],
            ]);
        }
    }

    protected function getBlocksDir(): string
    {
        return $this->getPath() . '/config/blocks';
    }

    protected function getViewsDir(): string
    {
        return $this->getPath() . '/templates';
    }

    /**
     * Underscored bundle name without the "Bundle" suffix, e.g.
     * SuluBlockContentBundle -> "sulu_block_content". Used as the sulu_admin
     * block-directory key and as the container-parameter prefix.
     */
    protected function getBlockAlias(): string
    {
        return Container::underscore((string) \preg_replace('/Bundle$/', '', $this->getName()));
    }

    protected function getMetadataParameterName(): string
    {
        return $this->getBlockAlias() . '.bundle_metadata';
    }

    protected function getPackageName(): string
    {
        $composerFile = $this->getPath() . '/composer.json';

        if (\is_file($composerFile)) {
            $data = \json_decode((string) \file_get_contents($composerFile), true);

            if (\is_array($data) && isset($data['name']) && \is_string($data['name'])) {
                return $data['name'];
            }
        }

        return 'depa/' . \str_replace('_', '-', $this->getBlockAlias());
    }
}
