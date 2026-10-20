# Cookie Consent Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 3.13.2 - 2026-10-20

### Changed

- Original settings, content, cookie declarations and historical consent totals belong to the primary site. Original cookie element IDs are retained.
- Secondary sites use built-in settings plus their own explicit UID overrides, default content and necessary Craft/consent declarations. Existing site-specific edits and counts are preserved.
- Historical unassigned totals are added to the primary site's existing counts once, with source-row consumption and the increment committed together.

### Fixed

- A follow-up migration corrects installations that already ran 3.13.0 or 3.13.1 without overwriting current primary content or resetting secondary counts.
- Earlier generated secondary content is reset only when its complete editable row still matches the retained legacy snapshot and expected footer transformation. A differing field preserves the entire row.
- Earlier generated secondary cookie copies are moved to Craft's trash only when their deterministic UID, expected fields, enabled state and timestamps match, and the source has no later edit. Missing, edited, independently created and uncertain records are retained.

### Added

- A Restore action for cookie elements in Craft's trash. Native cookie fields remain available until Craft permanently deletes the element; trash is not a permanent archive.

### Upgrade notes

- Schema version is now `3.13.2`. Back up the project, apply the source, run `craft up` and clear caches. The new `m261020_150000_assign_legacy_to_primary` migration is required even after 3.13.0 or 3.13.1.
- Consent-cookie names are unchanged from 3.13.0, so this correction introduces no additional consent reset. The 3.13.1 Cookies site-switching fix remains included.
- Review each secondary site's settings because inherited flat values now apply only to the primary site. See `UPGRADE-MULTISITE.md` for cleanup criteria and recovery instructions.

## 3.13.1 - 2026-10-20

### Fixed

- Switching sites in the Cookies index no longer combines Craft's new site handle with an old numeric site ID.
- Cookie-index URLs, navigation and the New Cookie button use the native `site` handle; older numeric URLs redirect to the matching handle before the index loads.
- GET requests tolerate a stale secondary site selector. Submitted forms resolve their own posted selection without mixing in old URL parameters; conflicting posted selectors and unauthorized sites remain rejected.

### Upgrade notes

- Apply the updated source and clear Craft's published resources/browser cache. No additional database migration is required after 3.13.0.

## 3.13.0 - 2026-10-20

### Added

- Independent site settings, content, cookie catalogs and consent totals for Craft multisite.
- Site selectors and site-aware forms, editing URLs, permissions and entry exclusions.
- Stable site-UID consent cookie names, including when sites are added or removed.
- An upgrade migration that preserves the original cookie IDs on the primary site, copies content and cookies to existing sites, keeps unallocated historical totals, and can resume an interrupted upgrade.
- Default content and Craft/consent cookie declarations for sites created after installation.
- Site-language translation hooks for banner controls and category labels.

### Fixed

- Template-only pages now receive the consent UI.
- Entry exclusions compare normalized integer IDs.
- Frontend configuration is encoded once as safe JSON; quotes and newlines remain valid.
- Consent requests stay on the current origin and identify the site explicitly.
- Concurrent consent counts use atomic increments.
- Saving a cookie no longer overwrites programmatically assigned fields with unrelated POST data.
- Known-cookie duplicate detection is scoped to the selected site.
- Existing button weight/flip settings are passed through and disabling Google signals suppresses both default and update calls.

### Upgrade notes

- Visitors are asked for consent once after the cookie name change.
- Follow `UPGRADE-MULTISITE.md` before applying to a Craft project.

## 3.5.0

### Fixed

- Updated Colours to work on hover

## 3.3.1

### Added

- Added option to inset a default row on install

## 3.3.0

### Added

- Exclude the Website Docs from the cookies consent
- Add option to settings to exclude certain pages

## 3.2.0

### Added

- Settings to exclude certain pages from having the cookie consent form

## 1.0.0

### Added

- Initial release
# 3.8.0 - 2026-07-20

- Added Craft session, CSRF and consent cookies automatically during installation.
- Added a client-friendly service picker when creating cookies.
- Added a Google Analytics 4 preset for `_ga` and `_ga_*` with exact duplicate prevention.
- Added a reusable first-party cookie domain setting.
