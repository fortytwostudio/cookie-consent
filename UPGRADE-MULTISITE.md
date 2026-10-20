# Multisite upgrade — proposed 3.13.2

This package updates Cookie Consent for Craft CMS 5 so that settings, content, cookie declarations, browser consent and consent totals are resolved for a specific Craft site. **Original plugin data belongs to the primary site.** Other sites start from plugin defaults and retain their own explicit settings and edits. The package includes source, upgrade migrations, compiled assets and regression checks. Preparing this package has not published it to Composer or installed it in your project.

## Already applied 3.13.0 or 3.13.1?

**Run the new migration.** Version 3.13.2 uses schema version `3.13.2` and adds `m261020_150000_assign_legacy_to_primary`. Back up the project, apply the source, run `ddev craft up`, clear caches and hard-refresh the control panel using the steps below. Replacing the files and clearing caches alone does not apply this correction.

The follow-up preserves current primary-site content and adds the old unassigned totals to its existing counts once. It resets secondary content only when the entire editable row still matches the earlier generated copy, and trashes secondary cookie copies only when provenance, values and timestamps all match. Edited or uncertain records are retained; see the details below.

Consent-cookie names remain the same as in 3.13.0, so this update introduces no additional consent reset. The 3.13.1 Cookies site-switching fix is included: old numeric index URLs redirect to the canonical site handle, while submitted forms retain selector and permission validation.

## Apply the update

Use a local or staging copy of the real project first, with its current database and at least two configured sites.

1. Back up the current database and preserve the current plugin code and project config before replacing any files:

   ```sh
   ddev craft db/backup
   ```

2. Merge the contents of the archive's `cookie-consent/` directory into your plugin source repository or the local Composer path package used by the project. Keep your repository metadata. The archive intentionally omits `.git`, `node_modules` and operating-system metadata. Do not add a second installed plugin with a different handle: the existing handle remains `forty-cookieconsent` and the Composer package remains `fortytwo-studio/cookie-consent`.

   If the project currently installs the plugin from a remote Composer release, point your development project at the updated local source through your existing Composer development workflow. After validation, publish/tag it through your usual process and update the project's dependency and lock file. This archive alone does not make a `3.13.2` remote release available. A direct edit to `vendor/` will be overwritten by a subsequent Composer install/update.

3. Once Craft is loading the updated source, run the normal Craft upgrade command, then clear its published resources and other caches:

   ```sh
   ddev craft up
   ddev craft clear-caches/all
   ```

   Outside DDEV, the equivalents are `php craft db/backup`, `php craft up` and `php craft clear-caches/all`. `craft up` also applies other pending project-config changes and migrations; review those as part of your normal deployment. Also clear any full-page cache, such as Blitz or a CDN, so it cannot serve the old cookie configuration or JavaScript.

4. In the control panel, confirm the original content, cookie IDs and historical totals belong to the primary site. Select each secondary site and review its content, privacy links, first-party domain, cookie declarations, Enabled and Google settings, colours and excluded entries. Built-in settings can differ from the flat values previously inherited; save the intended choices for each site explicitly.

5. Complete the real-project checks below, then deploy the updated package, project config and migration using your normal deployment process. Keep the database backup until the upgrade is verified.

The plugin declares schema version `3.13.2`. Upgrades from before 3.13.0 apply the pending `m261020_130000_add_multisite` and `m261020_150000_assign_legacy_to_primary` migrations in order. Projects that already completed the earlier multisite migration apply the new follow-up. A fresh installation creates the final schema directly. The production assets are already built, so Node is only needed if you change the frontend source.

## What happens to existing data

| Data | Upgrade behavior |
| --- | --- |
| Plugin settings | Flat values apply only to the primary site, with its explicit overrides applied afterward. Secondary sites use built-in defaults plus their own `sites[siteUid]` overrides. Saving one section preserves other sites and sections. |
| Original content | On a first multisite upgrade, the original row is assigned to the primary site. If a primary-site row already exists, its current content is preserved. Secondary sites start with defaults; handling of earlier generated copies is described below. |
| Cookie declarations | Original elements remain on the primary site with their original IDs. The old `cc_cookie` declaration is updated to the primary site's scoped consent name. Secondary sites receive necessary Craft/consent declarations rather than copies of the primary catalog. |
| Default domain and privacy link | New default declarations use the site's domain. The stock `/privacy-policy` footer becomes a site-specific URL when the original content is adopted or default content is initialized. Custom footer HTML is retained. |
| Historical consent totals | Old unassigned accepted/rejected counts are added to the primary site's current totals once. The consumed source rows are removed in the same transaction. Existing secondary totals are unchanged; newly initialized secondary sites start at zero. |
| Browser consent | Upgrades from before 3.13.0 move from `cc_cookie` to a stable site-UID name and ask visitors again once. Upgrading from 3.13.0 or 3.13.1 retains the current consent-cookie names and choices. |

Every site uses its UID in the consent-cookie name, including single-site projects, so adding or removing other sites later does not change that site's cookie identity. The consent cookie uses the current hostname and `/` path; unique names distinguish sites sharing the same hostname. This isolates the plugin's consent choice. It does not change the domain/path rules of cookies created by your analytics providers or other scripts.

A blank first-party domain setting is derived from that site's configured base URL. For backward compatibility, an old flat `siteDomain` value applies to the primary site; other sites derive their own domain unless they have an explicit override. A domain setting describes cookie declarations; it is not a cross-site consent-sharing setting.

Generated default privacy links use Craft's native `siteUrl()` routing. Review them after a console migration, especially if `omitScriptNameInUrls` is disabled or the public entry-script path differs across sites. Replace the default footer with the actual privacy-page link for each site when necessary.

New sites created after the upgrade receive default content, zero totals and necessary Craft/consent declarations. Custom marketing/analytics declarations are not automatically copied from another site for later site creations. Review and add the declarations that apply to the new site.

Counts continue to represent consent events, including later changes. “Accepted” means accepting all categories; necessary-only and custom selections increment “rejected”, preserving the previous grouping. They are not unique-visitor counts.

## Cleanup of copies from 3.13.0 / 3.13.1

The follow-up migration preserves the primary site's current content and cookie elements. Historical totals are added to any newer primary-site counts; rerunning the migration does not add them a second time. Existing secondary-site counts are never reset.

For secondary content, the migration compares **every editable field** with the retained legacy snapshot, including the earlier transformation of the stock privacy footer to that site's URL. Only an exact match is replaced with default content. If any field differs, the entire row is retained. Without the legacy snapshot, the migration leaves existing secondary content alone.

A secondary cookie is moved to Craft's trash only when its UID matches the earlier deterministic source-cookie/site UID, every expected native field and enabled state matches, its creation and update timestamps are equal, and its primary source was not edited after the copy was created. Comparison includes the earlier domain and consent-name transformations. Missing sources, changed values or timestamps, uncertain history and independently created records are preserved. Necessary declarations missing after cleanup are seeded from that site's defaults.

To recover a declaration moved by this cleanup, select the secondary site in **Cookies**, open the **Trashed** status and use **Restore**. Its native cookie fields are retained while the element is in Craft's trash. Trash is subject to Craft's normal retention and permanent-deletion lifecycle; it is not a permanent archive. Keep the pre-upgrade database backup for recovery beyond that point. Content rows reset to defaults are recoverable from that backup, not from the element trash.

## Site-aware editing and integration

The dashboard, content and settings pages include a site selector, and the cookie list uses Craft's native site selector. Forms and editing URLs retain the selection. Access to content and cookies respects plugin access and site-edit permissions. Settings remain administrator-only and honor Craft's `allowAdminChanges` setting.

The existing Twig variable name is unchanged. Its methods use the current frontend site by default, or the selected site for control-panel requests:

```twig
{% set settings = fortycookie.getSettings() %}
{% set content = fortycookie.getContent() %}
{% set cookies = fortycookie.getCookiesByType('analytics') %}

{# Pass a numeric site ID when an integration needs another site explicitly. #}
{% set otherSiteContent = fortycookie.getContent(otherSite.id) %}
```

`getAllCookies()`, `getCookiesByType()`, `getSettings()`, `getContent()` and `getLogs()` accept an optional numeric site ID (the second argument for `getCookiesByType()`). In PHP, call `CookieConsent::getInstance()->getSiteSettings($siteId)` for effective settings; inherited `getSettings()` still returns the global model containing the primary site's legacy flat settings and all site overrides.

If your code directly queries `CookieElement`, specify `->siteId($siteId)` when you intend to read a particular site. Each cookie has one immutable owning `cookieSiteId`. Creating cookies programmatically requires assigning both `siteId` and `cookieSiteId`; ordinary editor-created records receive these automatically. A cookie cannot be moved between sites by changing a posted owner field.

The frontend logging request includes `siteId` and stays on the page's current origin. Integrations calling `forty-cookieconsent/cookies/log-consent` should send JSON with `siteId` and an `acceptType` of `all`, `necessary` or `custom`. Calls to `forty-cookieconsent/cookies/get-consent` can include a `siteId` query parameter. Public requests validate that the selected site belongs to the request hostname; same-host path-based sites remain supported. These public counters are aggregate interaction statistics, not authenticated audit records.

The plugin continues to inject through Craft's view hooks. Your layout needs the normal `{% head %}`, `{% beginBody %}` and `{% endBody %}` calls. Template-only routes now receive the banner as well as entry pages. Exclusion settings are evaluated against the matched element's normalized numeric ID for the current site.

## Languages

Edit the popup and preference content for each site in the control panel. Built-in controls and category labels are translated through Craft's `forty-cookieconsent` message category using the site's configured language. English remains the fallback; translated language packs are not bundled.

For example, a project can provide `translations/fr/forty-cookieconsent.php`:

```php
<?php

return [
    'Accept all' => 'Tout accepter',
    'Reject all' => 'Tout refuser',
    'Manage preferences' => 'Gérer les préférences',
    'Save preferences' => 'Enregistrer les préférences',
    'Necessary cookies' => 'Cookies nécessaires',
];
```

See the complete label map in `src/services/FrontendConfig.php` for the remaining keys. Cookie descriptions are independent per site and can be edited in that site's cookie list.

## Check published asset URLs on secondary domains

Craft publishes plugin JavaScript and CSS through its asset manager. If your project fixes `@web` to the primary hostname, the default `resourceBaseUrl` (`@web/cpresources`) can send secondary sites' module requests to the primary hostname.

For sites that share the same public web root, with published assets served from `/cpresources` on every hostname, an appropriate setting in your existing `config/general.php` configuration is:

```php
->resourceBaseUrl('/cpresources')
```

Use the actual public path for your installation. Do not use this example unchanged if `/cpresources` is not available on every site. Keep `resourceBasePath` and web-server routing consistent with the URL. If an intentional CDN serves the assets, retain it and configure the CDN to allow module requests from your sites. The plugin does not rewrite an explicitly configured asset host.

## Checks in your actual Craft project

1. In a clean browser session, open two sites, ideally including two paths on one hostname if your project uses that topology. Give them different banner text or colours. Confirm each renders its own settings, content and cookie list.
2. Accept all on Site A. Open Site B: it should still show its own consent prompt. Choose necessary-only there. Return to Site A: its consent should be remembered. In browser storage, verify the two `cc_cookie_<UID>` names.
3. Change content, a cookie description, a colour and the exclusions for Site B. Switch back to Site A in the control panel and confirm its values are unchanged. Verify a user restricted to one site cannot edit the other site's cookie data.
4. Confirm each consent request succeeds on the current origin and that only the selected site's totals increase. The primary total should include its pre-upgrade counts, the old unassigned counts and any new test events. Existing secondary counts should have been preserved and increased only by their own new events.
5. Check an excluded entry, a normal entry, a template-only page and a secondary-site SVG trigger icon if one is configured. Confirm Google consent commands are absent when signals are disabled for that site.
6. On a staging copy, add a site and confirm its default content and necessary cookie declarations are initialized. Verify the migration on the same database engine used in production.

The update was checked with PHP 8.3 and real Craft 5.11.5.1/Yii/Twig classes, isolated persistence fixtures and a headless Chromium run of the compiled bundle on two paths of one hostname. Checks cover site-isolated settings/content/totals, safe field assignment, consent-cookie isolation, payload site IDs, JSON escaping, Google-off behavior and PHP/Twig loading. See `tests/README.md` for the portable regression checks and their exact scope.

These checks are not a full installation/upgrade of your project. The control panel has not been rendered inside your running Craft installation, and the complete migration has not been run against a live MySQL or PostgreSQL Craft database. The real-project checks above are the remaining deployment gate.

## Interrupted upgrades and rollback

The schema migration checks columns, indexes and foreign keys separately so an interrupted schema change can resume. The primary-attribution data phase preserves an existing primary content row, transfers historical totals transactionally and consumes each source count row once. The follow-up recognizes earlier generated cookie copies by their deterministic UID and checks values and timestamps before moving them to trash. A retry preserves independent records and does not transfer the same historical counts twice.

If a legacy custom cookie row has no corresponding Craft element, the migration stops with the affected IDs instead of deleting the row to add a foreign key. Repair those specific records or restore a valid backup, then rerun the normal upgrade command.

There is no automatic down-migration: independent site records cannot be safely merged back into one shared row. To roll back, restore the pre-upgrade database together with the matching plugin code and project config, then clear caches.

## Main implementation files

| Area | Files |
| --- | --- |
| Site resolution and consent identity | `src/services/SiteContext.php` |
| Settings and save isolation | `src/models/SettingsModel.php`, `src/controllers/SettingsController.php`, `src/CookieConsent.php` |
| Per-site content and atomic counters | `src/services/SiteData.php`, content/dashboard/cookie controllers |
| Cookie ownership and queries | `src/elements/CookieElement.php`, `src/elements/db/CookieQuery.php`, `src/services/KnownCookies.php` |
| Safe frontend configuration | `src/services/FrontendConfig.php`, `src/templates/_variables.twig`, `src/resources/src/js/plugins/cookieconsent-config.js` |
| Existing-data upgrade | `src/migrations/m261020_130000_add_multisite.php`, `src/migrations/m261020_150000_assign_legacy_to_primary.php`, `src/services/PrimarySiteUpgrade.php` |
| Fresh installation | `src/migrations/Install.php` |
| Control-panel selection | `src/templates/_includes/site-switcher.twig`, site-aware forms, `src/resources/src/js/index.js` |

## References

- [Craft 5 element types](https://craftcms.com/docs/5.x/extend/element-types.html): localization and supported-site behavior.
- [Craft 5 plugin settings](https://craftcms.com/docs/5.x/extend/plugin-settings.html): settings models and configuration overrides.
- [Craft 5 console commands](https://craftcms.com/docs/5.x/reference/cli.html): database backups, `up` and cache clearing.
- [Craft 5 general configuration](https://craftcms.com/docs/5.x/reference/config/general.html#resourcebaseurl): published resource URLs.
- [CookieConsent configuration reference](https://cookieconsent.orestbida.com/reference/configuration-reference.html): consent-cookie name, domain and path options.
