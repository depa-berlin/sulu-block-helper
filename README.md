# sulu-block-fragments

Shared XML fragments and Twig partials for Sulu CMS block bundles.

This bundle provides the common building blocks that all other `depa-berlin/sulu-block-*` bundles depend on.

## Contents

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
composer require depa-berlin/sulu-block-fragments
```

Register the bundle in `config/bundles.php`:

```php
Depa\SuluBlockFragmentsBundle\SuluBlockFragmentsBundle::class => ['all' => true],
```

## License

Proprietary — Copyright (c) designpark Internet GmbH. All rights reserved.
See [LICENSE](LICENSE) for details.
