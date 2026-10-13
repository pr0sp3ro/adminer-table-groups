# Adminer Table Groups

User-defined sidebar groups for **Adminer 6.1.2**, stored in your browser's
localStorage. Create your own groups and assign tables and views directly in
Adminer's sidebar.

## Requirements

- Adminer 6.1.2
- PHP 8.1+
- A modern browser with JavaScript and localStorage enabled

## Installation

Copy the plugin and assets alongside Adminer using this layout:

```text
adminer.php
adminer-plugins/
  table-prefix-groups.php
  assets/
    adminer-prefix-groups.css
    adminer-prefix-groups.js
```

Adminer 6.1.2 automatically discovers the plugin in `adminer-plugins/`.
See [Adminer's plugin installation documentation](https://www.adminer.org/en/plugins/).
Both asset files are required. Load the plugin only once.

## Configuration

To customize settings, create `adminer-plugins.php` beside `adminer.php`:

```php
<?php

use Adminer\AdminerTablePrefixGroups;

return [
    new AdminerTablePrefixGroups(
        otherLabel: 'Pozostałe',
        tableCountLabel: 'tab.',
        viewCountLabel: 'widoki',
        useSelectIcon: true,
    ),
];
```

Adminer loads the class from `adminer-plugins/` before reading this configuration.
Optional `cssFile` and `jsFile` arguments override the browser URLs, with defaults
`adminer-plugins/assets/adminer-prefix-groups.css` and
`adminer-plugins/assets/adminer-prefix-groups.js`. Keep the on-disk `assets/`
directory beside the plugin even when overriding URLs.

| Argument | Default | Purpose |
| --- | --- | --- |
| `cssFile` | `adminer-plugins/assets/adminer-prefix-groups.css` | Stylesheet URL relative to the Adminer page |
| `otherLabel` | Automatic translation | Label for unassigned objects |
| `tableCountLabel` | Automatic translation | Label for table counts |
| `viewCountLabel` | Automatic translation | Label for view counts |
| `useSelectIcon` | `true` | Use an eye icon for the data link. Set to `false` for a text link |
| `jsFile` | `adminer-plugins/assets/adminer-prefix-groups.js` | JavaScript URL relative to the Adminer page |

## Usage

Open **Manage groups** above the sidebar list. Create a group, then select its
name beside each table or view in the panel. Changes are saved immediately.
Rename and delete groups in the same panel. Deleting a group requires confirmation
and moves its objects to **Other**. Objects belong to at most one group.

Groups are sorted alphabetically, with natural sorting of tables followed by
views inside each group. Empty groups remain available. Other and the group of
the active object open automatically. Expansion state is not saved.

Each object has links to its data and structure, with active-link styling.
Groups display separate table/view counts. Without JavaScript, tables are
accessible as a flat list. PL, EN and UK interface labels follow Adminer's
language, with English fallback.

## Storage

Settings are isolated by browser origin, Adminer URL path, driver, server,
username, database and schema. No password or session identifier is stored.

New objects appear in Other. Assignments for missing objects remain stored but
are not rendered. Renamed database objects are treated as new objects.
Empty or duplicate group names are rejected, ignoring case and outer whitespace.
Names are rendered as text.

Invalid saved data displays a warning and an unassigned list. The next edit
replaces the invalid configuration. If localStorage is unavailable or full,
changes work in memory with a warning that they will be lost after reloading.
Settings are local to this browser profile and are lost when site storage is
cleared. Settings are not synchronized between browsers.

The plugin replaces `tablesPrint()` and can conflict with other plugins that
replace that hook. Third-party sidebar filters should be checked manually.

## Docker

Use [examples/docker-compose.adminer.yml](examples/docker-compose.adminer.yml),
which pins version 6.1.2 and loads the plugin through a small Docker wrapper.
The [official image's loader](https://github.com/TimWolla/docker-adminer/blob/master/6/index.php)
uses `plugins-enabled/` rather than standalone Adminer's automatic discovery:

```bash
docker compose -f examples/docker-compose.adminer.yml up
```

## Verification

Run locally:

```bash
php -l table-prefix-groups.php
php -l tests/smoke.php
php -l docker/adminer6-plugin.php
node --check assets/adminer-prefix-groups.js
php tests/smoke.php
```

The smoke test checks rendering, active links, escaping and hidden objects.
It does not run the browser or prove localStorage behavior.

Manual checks in Adminer 6.1.2:

- Create, rename and delete groups, including empty groups and invalid names.
- Assign tables and views, remove assignments, reload and follow sidebar links.
- Check natural ordering, counts, the eye icon and automatic active-group opening.
- Switch accounts, servers, databases and schemas to check storage isolation.
- Check special characters in names, new/missing objects and retained assignments.
- Corrupt the saved JSON or block localStorage and check warnings and usable links.
- Check the sidebar with your theme and any other sidebar plugins.

## License

MIT. Author: [KeyffMS](https://github.com/KeyffMS) / [aiteracja.pl](https://aiteracja.pl).
