<?php

declare(strict_types=1);

namespace Depa\SuluBlockHelperBundle;

use Symfony\Component\Finder\Finder;

trait BlockMetadataLoaderTrait
{
    /**
     * @return array{blocks: list<string>, children: array<string, list<string>>}
     */
    private function loadMetadataFromXml(string $blocksDir): array
    {
        if (!is_dir($blocksDir)) {
            return ['blocks' => [], 'children' => []];
        }

        $blocks = [];
        $children = [];

        $finder = (new Finder())->files()->in($blocksDir)->name('*.xml');

        foreach ($finder as $file) {
            \libxml_use_internal_errors(true);
            $xml = \simplexml_load_file($file->getRealPath());
            $errors = \libxml_get_errors();
            \libxml_clear_errors();
            \libxml_use_internal_errors(false);

            if ($xml === false) {
                $messages = \implode('; ', \array_map(static fn(\LibXMLError $e) => \trim($e->message), $errors));
                throw new \RuntimeException(\sprintf(
                    'Failed to parse block XML "%s": %s',
                    $file->getRealPath(),
                    $messages ?: 'unknown error',
                ));
            }

            $xml->registerXPathNamespace('s', 'http://schemas.sulu.io/template/template');

            $keyNodes = $xml->xpath('//s:key');
            if (empty($keyNodes)) {
                continue;
            }

            $blockName = (string) $keyNodes[0];
            $blocks[] = $blockName;

            $refs = $xml->xpath('//s:block//s:type/@ref');
            if (!empty($refs)) {
                $mapped = array_map(static fn($r) => (string) $r, $refs);
                $children[$blockName] = array_values(array_unique($mapped));
            }
        }

        sort($blocks);

        return ['blocks' => $blocks, 'children' => $children];
    }
}
