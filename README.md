# Cookie Consent plugin for Craft CMS 5.x

Manage cookie declarations, consent content, appearance and consent totals in Craft CMS. Each Craft site has its own configuration and consent state, including sites that share a hostname and use different URL paths.

## Updating an existing installation

This source package contains the proposed **3.13.2 multisite update**. Original settings, content, cookie declarations and historical consent totals belong to the **primary site**. Other sites start with plugin defaults and keep their own explicit settings and edits.

Read [UPGRADE-MULTISITE.md](UPGRADE-MULTISITE.md) before applying it. Back up the project, apply the source, run `craft up` and clear caches. **A new data migration is required even if you already applied 3.13.0 or 3.13.1.** It preserves current primary-site edits and counts and conservatively cleans up unchanged secondary copies from those versions. The package includes compiled assets and the earlier Cookies site-switching fix. Consent-cookie names are unchanged from 3.13.0; only upgrades from an earlier version introduce the one-time consent reset.

The version in this source package does not imply that a corresponding Composer release has been published. Apply it to your plugin repository or local Composer path package, then use your normal release process.

## Requirements

This plugin requires Craft CMS 5.x.

## Installation from a published release

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

```
cd /path/to/project/craft
```

2. Then tell Composer to load the plugin:

```
composer require fortytwo-studio/cookie-consent
```

## Using multisite

Open **Cookie Consent** in the control panel and select a site. The site selector is available on the dashboard, content and settings screens; the cookie list uses Craft's element-index site selector. Content edits, cookie declarations and consent totals belong to the selected site. Settings changes require an administrator and an environment where Craft allows administrative changes.

Existing flat plugin settings apply to the primary site. Site-specific overrides are stored under `sites`, keyed by Craft site UID. Secondary sites use the plugin's built-in defaults plus their own explicit overrides. Review each site's Enabled and Google settings, appearance and exclusions when upgrading.

Original content and cookie declarations stay on the primary site, with the original cookie element IDs retained. Historical consent totals are added to the primary site's totals once. Other sites receive default content and necessary Craft/consent declarations; their existing counts and independently edited records are preserved. The upgrade guide explains how 3.13.2 handles copies created by an earlier multisite update and how to restore eligible trashed declarations.

On the frontend, the plugin resolves Craft's current site at render time. It uses a consent cookie named `cc_cookie_<site UID without hyphens>`, so a choice on one site does not dismiss the banner on another site sharing the same hostname.

Your layout must include Craft's normal `{% head %}`, `{% beginBody %}` and `{% endBody %}` hooks. Review any separately configured analytics/tag-manager scripts as part of your site's consent integration; adding a declaration describes a cookie and does not automatically connect an external script to a consent category.

## Development

The production frontend build is included in `src/resources/dist/`. To rebuild after changing JavaScript or CSS:

```sh
npm ci
npm run build
```

Regression checks and their prerequisites are described in [tests/README.md](tests/README.md). The upgrade guide includes a short verification procedure for a real Craft project.

Brought to you by [Forty Two](https://fortytwo.studio)
