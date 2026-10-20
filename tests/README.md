# Multisite regression checks

These are standalone checks for the multisite changes. They use a Composer vendor directory containing Craft 5, Yii, and Twig, but they **do not bootstrap a Craft site or connect to its database**. SQLite fixtures run entirely in memory. Run each PHP script as a separate process; the migration fixture deliberately substitutes a minimal element implementation.

The checks were run with PHP 8.3.6, Craft 5.11.5.1, Yii 2.0.55, Twig 3.28.0, Node 24.19.0, and Chromium 149. The package's existing JavaScript lockfile was used for the build.

## PHP and Twig

Point `CRAFT_VENDOR_PATH` at the `vendor` directory of a compatible Craft 5 Composer installation. The scripts resolve the plugin from their parent directory; set `COOKIE_PLUGIN_PATH` if testing a different checkout. Use PHP with Craft's normal required extensions and `pdo_sqlite` for the data fixtures.

From the plugin directory:

```sh
export CRAFT_VENDOR_PATH=/absolute/path/to/craft-project/vendor
php tests/class-and-twig.php
php tests/site-data.php
php tests/settings-save.php
php tests/cp-site-selection.php
php tests/migration-data.php
find src tests -name '*.php' -exec php -l {} \;
```

| Check | What it verifies |
| --- | --- |
| `class-and-twig.php` | All plugin PHP classes load against actual Craft parent classes; settings remain isolated by site UID; false and empty overrides are preserved; all Twig templates compile with Craft tags and functions. |
| `site-data.php` | Actual CookieElement validation, safe native attributes, protected owner, and site mismatch rejection; real Yii queries isolate content and counters, ignore posted identities, and preserve historical totals pending migration. |
| `settings-save.php` | Actual SettingsController save action with real Craft associative-array packing: save A's Google settings, B's colours, then A's exclusions. Other sites/tabs and stored defaults survive, configuration-file overrides are not persisted, and conflicting selectors reject before a write. Authentication and redirect transport use fixtures. |
| `cp-site-selection.php` | Actual Craft Request getters, SiteContext, and CookiesController index action: stale GET selectors recover in both directions; legacy URLs redirect to the canonical handle before rendering, preserve unrelated query parameters, and avoid redirect loops; posted selectors take precedence as a complete pair; contradictory writes, malformed selectors, and unauthorized sites still fail. Identity, site registry, and response transport use fixtures. |
| `migration-data.php` | Fresh legacy upgrade assigns original cookie IDs, content, settings, and counts only to primary; secondary sites receive defaults. A prior 3.13.0 fixture tests one-time merging of historical/current primary counts, exact-copy content reset, conservative soft deletion of generated cookies, retention of edited/uncertain/manual records and native fields, explicit secondary settings, and replay. Failure injection verifies rollback both normally and with Yii active/PDO inactive after simulated DDL auto-commit. |

The migration fixture uses real SQL and both production migration data phases, including PrimarySiteUpgrade, SiteData, KnownCookies, and SiteContext. The failure trigger interrupts a primary counter increment after its historical source has been claimed, and the assertions require both the counter source and earlier content changes to roll back. A second run deliberately commits PDO while leaving Yii's outer transaction bookkeeping active to emulate the state MySQL DDL can create. The fixture also checks successful recovery, repeat execution, and completion of the outer bookkeeping.

Its small element query/save/trash implementation and SQLite schema adapter are test doubles. It does **not** execute MySQL/PostgreSQL DDL, verify Craft's full element propagation/trash lifecycle, or exercise the control panel's authenticated request pipeline. Those require a staging copy of the actual Craft site. The template check compiles templates; it does not render every control panel page.

## Cookies index navigation

With `CRAFT_VENDOR_PATH` set as above, run:

```sh
node tests/index-navigation.cjs
```

This regression executes both the production source and the compiled Cookies index script against the actual Craft URL helpers extracted from the installed `cp.js.map`. It first reproduces the old `siteId` pollution of the breadcrumb links, then verifies clean initial URLs, repeated A → B → A site navigation, each breadcrumb's destination, stale ID removal, retained unrelated query parameters, and the New Cookie link. It also checks the older `selectSite` event path and that modal indexes leave page navigation alone.

The DOM, jQuery utilities, buttons, and element-index initialization are small fixtures. This test requires Node and Craft's distributed source map, but no browser or running Craft site. It specifically checks the interaction with Craft's real `setUrl()` breadcrumb rewriting; it does not claim to exercise a live authenticated control panel. Rebuild with `npm run build` after changing the index source so both copies pass.

## Build and browser

```sh
npm ci --ignore-scripts
npm run build
npx playwright install chromium
node tests/browser.cjs
```

If Chromium is already installed elsewhere, set `CHROMIUM_PATH` to its executable instead of downloading another copy:

```sh
CHROMIUM_PATH=/absolute/path/to/chromium node tests/browser.cjs
```

The browser test starts a temporary server on an available loopback port and serves the real compiled plugin bundle on two paths sharing one hostname. It checks independent consent cookies, remembered choices when returning to the first site, site-specific text/colours/catalogs, quoted and multiline text, logging payload site IDs, no JavaScript errors, and disabled Google integration. The page configuration and logging endpoint are fixtures; this check does not run the PHP logging controller.

## Staging acceptance before deployment

1. Back up the database and project config, then apply the plugin update and its migrations on a staging copy using Craft's normal migration workflow.
2. For a fresh upgrade, confirm the primary site retains its original cookie element IDs, content, settings, and historical totals; additional sites start with plugin defaults. For a previously applied 3.13.0/3.13.1 upgrade, confirm historical totals are added to the primary site's newer counts once, secondary counts remain unchanged, proven untouched generated copies are reset or trashed, and edited or uncertain records remain available.
3. Switch sites in Content, Cookies, Settings, Exclusions, and Logs. Save different values on each site and confirm the other site's values remain unchanged, including after reloading project config.
4. Use an editor with permission for only one site. Confirm other sites cannot be selected or submitted manually, and that plugin settings still require an administrator.
5. Test actual site hostnames and any path-based sites in a clean browser session. Accept on one site, verify the other still asks, and check actual consent POST responses and per-site counts.
6. Verify translated copy, privacy links, entry exclusions, script blocking/activation, and any Google consent integration with the site's real tags. Upgrades from before 3.13.0 ask existing visitors again because consent cookie names now include the site UID. Upgrades from 3.13.0 or 3.13.1 to 3.13.2 keep existing consent.

Do not use the SQLite fixture as a substitute for the staging migration. It is a repeatable regression for preservation and retry behavior, not a supported Craft database configuration.
