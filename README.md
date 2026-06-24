# sulu-block-helper

Shared base classes, XML fragments and Twig partials for Sulu CMS block bundles.

This bundle provides the common foundation that all other `depa/sulu-block-*` bundles depend on.

## Contents

### Base Classes (`src/DependencyInjection/`)

| Class | Purpose |
|---|---|
| `AbstractBlockExtension` | Base Extension for all block bundles — registers blocks dir, Twig paths and metadata parameter |
| `BlockMetadataLoaderTrait` | Reads block XML files and extracts block/child metadata |

### XML Fragments (`Resources/config/_fragments/`)

Reusable XML include files for Sulu block templates:

| Fragment | Purpose |
|---|---|
| `attr_class.xml` | CSS class input field |
| `attr_id.xml` | HTML ID input field |
| `config_image.xml` | Image configuration (retina, loading, priority) |

### Twig Partials (`Resources/views/includes/_partials/`)

| Partial | Purpose |
|---|---|
| `aria_attributes.html.twig` | Renders ARIA attributes from block config |
| `aria/aria-attr--aria-label.html.twig` | `aria-label` attribute |
| `aria/aria-block--heading.html.twig` | `role="heading" aria-level="…"` |

## Requirements

- PHP 8.2+
- Symfony 7.0+

## Installation

```bash
composer require depa/sulu-block-helper
```

If your project uses **Symfony Flex** (the default in the Sulu/Symfony
skeleton), the bundle is registered in `config/bundles.php` automatically —
skip the next step. Adding it manually on top would create a duplicate
registration.

Without Symfony Flex, register the bundle manually in `config/bundles.php`:

```php
Depa\SuluBlockHelperBundle\SuluBlockHelperBundle::class => ['all' => true],
```

## License

Proprietary — Copyright (c) [designpark Internet GmbH](https://www.designpark.de). All rights reserved.
See [LICENSE](LICENSE) for details.
