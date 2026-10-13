# Changelog

## Unreleased

- Replace automatic prefix groups with user-defined groups stored in localStorage.
- Add a compact management panel with group creation, renaming, deletion and assignments.
- Target Adminer 6.1.2 and remove legacy wrappers and separator configuration.
- Consolidate sidebar CSS in assets, preserving custom margins, and add browser JavaScript.
- Add PL, EN and UK management labels and update installation and Docker examples.

## 0.1.0 - 2026-06-28

### Added

- Initial release of Adminer Table Prefix Groups.
- Added grouping of Adminer sidebar tables and views by configurable prefix separator.
- Added Adminer 5 plugin support.
- Added Docker wrapper for the official Adminer Docker image.
- Added optional CSS for grouped sidebar styling.
- Added legacy Adminer 4 wrapper.
- Added smoke tests.
- Added automatic labels based on Adminer's active language.
- Added best-effort labels for major world languages plus Polish, Czech, Slovak, Finnish, and Swedish.
- Added support for grouped views.
- Added an accessible eye icon for table-data links.
- Added a constructor option to keep Adminer's text table-data links instead of the eye icon.

### Fixed

- Kept Adminer's native `active select` classes on active table-data links.
- Avoided private helper name collisions with Adminer plugin hooks.
- Filtered hidden, partition, and inherited table statuses before grouping.
