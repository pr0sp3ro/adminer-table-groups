# Adminer Table Prefix Groups

Adminer plugin that groups sidebar tables by the prefix before a configurable
separator. The default separator is `_`.

For example:

- `CRM_users`
- `CRM_orders`
- `CRM_contacts`

will be shown under a collapsible `CRM` group in the left sidebar.

## Requirements

- Adminer 5.x
- PHP 8.1+

The plugin is namespaced for Adminer 5. It uses Adminer's documented plugin hook
behavior: methods can override Adminer behavior by returning a non-`null` value.

## What It Does

The plugin changes only Adminer's left navigation list. It keeps Adminer's native
link targets for table data and table structure, including active-link styling.
By default, the table-data link is shown as a compact eye icon with an
accessible text label.

Tables are grouped by the text before the configured separator. The default
separator is `_`:

| Object name | Prefix |
| --- | --- |
| `crm_contact` | `crm` |
| `crm_note` | `crm` |
| `plm_order` | `plm` |

A prefix becomes a collapsible `<details>` group when:

- at least two objects exist with the same prefix, including tables or views, or
- one prefixed object exists and a base object with the same prefix exists.

Objects that do not match a group are shown under a localized `Other` label by
default.

## Example

Given these tables:

```text
crm
crm_contact
crm_note
plm_order
plm_item
users
```

Adminer will render:

```text
crm        tables: 3
  crm
  crm_contact
  crm_note

plm        tables: 2
  plm_item
  plm_order

Other      tables: 1
  users
```

## Installation

Copy the plugin file next to `adminer.php`:

```text
table-prefix-groups.php
```

Optionally copy the CSS file next to `adminer.php`:

```text
adminer-prefix-groups.css
```

In this repository the CSS source file lives at
`assets/adminer-prefix-groups.css`. Adminer looks for
`adminer-prefix-groups.css` by default, so copy or mount it under that filename
next to `adminer.php`.

Create `adminer-plugins.php` next to `adminer.php`:

```php
<?php

require_once __DIR__ . '/table-prefix-groups.php';

use Adminer\AdminerTablePrefixGroups;

return [
    new AdminerTablePrefixGroups(),
];
```

## Installation With Docker

The official Docker image loads custom plugins from `plugins-enabled/`, so the
Docker examples use small wrapper files that return plugin instances.

Use the canonical Compose examples from the repository root:

- Adminer 5: [examples/docker-compose.adminer.yml](examples/docker-compose.adminer.yml)
- Legacy `adminer:4.8.1-standalone`: [examples/docker-compose.adminer4-legacy.yml](examples/docker-compose.adminer4-legacy.yml)

Then recreate the Adminer container.

## Configuration

For custom settings, pass constructor arguments in `adminer-plugins.php`:

```php
<?php

require_once __DIR__ . '/table-prefix-groups.php';

use Adminer\AdminerTablePrefixGroups;

return [
    new AdminerTablePrefixGroups(
        'adminer-prefix-groups.css',
        null,
        null,
        null,
        '_',
        true
    ),
];
```

Arguments:

| Argument | Meaning |
| --- | --- |
| `$cssFile` | CSS file path as seen by Adminer |
| `$otherLabel` | label for ungrouped objects, or `null` for automatic language detection |
| `$tableCountLabel` | short label for table counts, or `null` for automatic language detection |
| `$viewCountLabel` | short label for view counts, or `null` for automatic language detection |
| `$separator` | separator used to detect prefixes |
| `$useSelectIcon` | render the table-data link as an eye icon |

The plugin reads Adminer's active language from `Adminer\LANG`. A `get_lang()`
fallback is kept for legacy Adminer 4 smoke tests. If the plugin knows the
active language, it uses matching labels. Otherwise it falls back to English.

The built-in label map lives in `$labelsByLanguage` in
`table-prefix-groups.php`. Translations are best-effort and can be overridden
through constructor arguments.

To force Polish labels:

```php
<?php

require_once __DIR__ . '/table-prefix-groups.php';

use Adminer\AdminerTablePrefixGroups;

return [
    new AdminerTablePrefixGroups(
        'adminer-prefix-groups.css',
        'Pozostałe',
        'tab.',
        'widoki',
        '_',
        true
    ),
];
```

For a different separator:

```php
<?php

require_once __DIR__ . '/table-prefix-groups.php';

use Adminer\AdminerTablePrefixGroups;

return [
    new AdminerTablePrefixGroups(
        'adminer-prefix-groups.css',
        null,
        null,
        null,
        '-',
        true
    ),
];
```

To keep Adminer's text `select` link instead of the eye icon:

```php
<?php

require_once __DIR__ . '/table-prefix-groups.php';

use Adminer\AdminerTablePrefixGroups;

return [
    new AdminerTablePrefixGroups(
        'adminer-prefix-groups.css',
        null,
        null,
        null,
        '_',
        false
    ),
];
```

## Compatibility Notes

Target:

- Adminer 5.x
- PHP 8.1+
- MySQL/MariaDB/PostgreSQL table lists

A small legacy wrapper is provided for the official `adminer:4.8.1-standalone`
Docker image. Adminer 5.x remains the primary target.

## Limitations

- Prefix matching is case-sensitive.
- Only the first separator is used.
- The plugin replaces the sidebar table list, so it may conflict with other
  plugins that also override `tablesPrint()`.
- Built-in translations are intentionally small and best-effort; override labels
  through constructor arguments if exact wording matters.

## Smoke Test

```bash
php -l table-prefix-groups.php
php -l legacy/adminer4-plugin.php
php -l docker/adminer5-plugin.php
php tests/smoke.php
```

## Files

- `table-prefix-groups.php` - Adminer plugin
- `assets/adminer-prefix-groups.css` - optional sidebar styling
- `docker/adminer5-plugin.php` - wrapper for the official Docker image
- `examples/` - Docker Compose mount examples
- `legacy/adminer4-plugin.php` - wrapper for the legacy official Docker image
- `tests/smoke.php` - lightweight runtime smoke test with Adminer function mocks

## License

MIT

Author: [KeyffMS](https://github.com/KeyffMS) / [aiteracja.pl](https://aiteracja.pl)
