# sulu-block-helper

Shared base classes, XML fragments and Twig partials for Sulu CMS block bundles.

This bundle provides the common foundation that all other `depa/sulu-block-*` bundles depend on.

## Contents

### Base Classes (`src/`)

| Class | Purpose |
|---|---|
| `AbstractBlockBundle` | Base bundle (extends Symfony `AbstractBundle`) for all block bundles — registers the block directory with `sulu_admin`, the Twig templates path and block-metadata parameters |
| `BlockMetadataLoaderTrait` | Reads block XML files and extracts block/child metadata |

### XML Fragments (`config/_fragments/`)

Reusable XML include files for Sulu block templates:

| Fragment | Purpose |
|---|---|
| `attr_class.xml` | CSS class input field |
| `attr_id.xml` | HTML ID input field |
| `config_image.xml` | Image configuration (retina, loading, priority) |

### Twig Partials (`templates/includes/_partials/`)

| Partial | Purpose |
|---|---|
| `aria_attributes.html.twig` | Renders ARIA attributes from block config |
| `aria/aria-attr--aria-label.html.twig` | `aria-label` attribute |
| `aria/aria-attr--heading.html.twig` | `role="heading" aria-level="…"` |

### Twig extensions (`src/Twig/`)

Registered automatically as services (no configuration needed), available in
both the admin and website contexts:

| Function / filter | Purpose |
|---|---|
| `country(code)` | ISO 3166-1 alpha-2 code → localised country name (via `symfony/intl`) |
| `obfuscate(mail[, text])` | ROT13 spam-protected `mailto:` link — **requires the website JS**, see below |

### Admin field type (`assets/`)

Registers the `config_line` field type (a plain single-line input) used by the
`attr_class` / `attr_id` fragments. This must be compiled into the Sulu admin —
see the installation step below.

### Website script (`assets/website/`)

Decodes the ROT13 links produced by the `obfuscate` Twig function on click.
Must be imported into your project's **website** asset build — see below.

## Requirements

- PHP 8.2+
- Symfony 7.0+
- `symfony/intl` — pulled in automatically; backs the `country` Twig function.
- `lubomirfiala/sulu-preview-nav` — pulled in automatically; provides the
  `sulu_block_preview` Twig function used by the block render partials.

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

### Admin build (required)

The bundle ships an admin field type (`config_line`). It is **not** active until
its JavaScript is imported into your project's admin entry and the admin is
rebuilt — without this, blocks using `attr_class` / `attr_id` will fail to render
in the admin with *"There is no field with key 'config_line' registered"*.

1. Add the import to `assets/admin/app.js`:

   ```js
   import '../../vendor/depa/sulu-block-helper/assets';
   ```

2. Rebuild the admin:

   ```bash
   cd assets/admin && npm install && npm run build
   ```

   After rebuilding, hard-reload the admin (the JS filename is content-hashed,
   so a cached browser may still load the old bundle).

### Website build (required for `obfuscate`)

The `obfuscate` Twig function renders email links ROT13-encoded; a small
frontend script reverses this on click. Without it, obfuscated `mailto:` links
point at the scrambled address and do not work. Import it once into your
project's **website** asset entry (the file self-initialises on
`DOMContentLoaded`):

```js
import '../../vendor/depa/sulu-block-helper/assets/website';
```

Then rebuild your website assets. This is only needed if you actually use the
`obfuscate` function (e.g. via the `block--content-account-address` block).

## License

Proprietary — Copyright (c) [designpark Internet GmbH](https://www.designpark.de). All rights reserved.
See [LICENSE](LICENSE) for details.
