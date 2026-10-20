const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const pluginRoot = process.env.COOKIE_PLUGIN_PATH || path.resolve(__dirname, '..');
const vendorRoot = process.env.CRAFT_VENDOR_PATH || path.join(pluginRoot, 'vendor');
const sourceMapPath = path.join(vendorRoot, 'craftcms/cms/src/web/assets/cp/dist/cp.js.map');
assert(fs.existsSync(sourceMapPath), 'Set CRAFT_VENDOR_PATH to a Craft 5 Composer vendor directory containing cp.js.map.');
const sourceMap = JSON.parse(fs.readFileSync(sourceMapPath, 'utf8'));
const craftSourceIndex = sourceMap.sources.findIndex(source => source.endsWith('/Craft.js'));
assert(craftSourceIndex !== -1 && sourceMap.sourcesContent[craftSourceIndex], 'Craft.js source is missing from the installed Craft source map.');
const craftSource = sourceMap.sourcesContent[craftSourceIndex];
const pluginScripts = ['src/resources/src/js/index.js', 'src/resources/dist/js/index.js']
  .map(filename => ({filename, source: fs.readFileSync(path.join(pluginRoot, filename), 'utf8')}));

// Import the actual framework URL helpers instead of substituting their behavior.
// Craft's source map retains its unminified object-method indentation.
function frameworkMethod(name) {
  const start = craftSource.search(new RegExp(`^  ${name}(?:: function\\b|\\()`, 'm'));
  assert(start !== -1, `Cannot find Craft.${name} in the installed Craft source map.`);
  const end = craftSource.indexOf('\n  },', start);
  assert(end !== -1, `Cannot extract Craft.${name} from the installed Craft source map.`);
  return craftSource.slice(start, end + '\n  },'.length);
}

const helpers = [
  'getUrl', 'setUrl', 'setQueryParam', 'getQueryParams', 'getQueryParam',
  'trim', 'ltrim', 'rtrim', 'escapeChars',
].map(frameworkMethod).join('\n');
const sites = [{id: 1, handle: 'first'}, {id: 2, handle: 'second'}];
const origin = 'https://cms.example.test';
const indexPath = '/admin/42cookie-consent/cookies';

function fixture(pluginScript, initialUrl, selectedSiteId, context = 'index') {
  const location = new URL(initialUrl, origin);
  // Cp::siteMenuItems() preserves existing query parameters and replaces `site`.
  const crumbs = sites.map(site => {
    const url = new URL(location.href);
    url.searchParams.set('site', site.handle);
    return {href: url.href};
  });
  const nav = [
    {href: `${origin}/admin/42cookie-consent/content?siteId=99&tab=copy`},
    {href: `${origin}/admin/42cookie-consent/settings/general?site=first`},
    {href: `${origin}/admin/entries?site=first`},
    {href: 'https://elsewhere.example.test/admin/42cookie-consent?site=first'},
  ];
  const document = {
    location,
    querySelectorAll(selector) {
      if (selector === '#site-crumb-menu a[href]') return crumbs;
      if (selector === '#nav a[href]') return nav;
      throw new Error(`Unexpected DOM selector: ${selector}`);
    },
  };
  const history = {
    replaceState(_state, _title, url) { location.href = new URL(url, location.href).href; },
  };
  // Only the scalar-query jQuery utilities and button surface used here are fixtures.
  const $ = () => ({addClass() { return this; }});
  $.isPlainObject = value => Object.prototype.toString.call(value) === '[object Object]';
  $.isEmptyObject = value => Object.keys(value).length === 0;
  $.extend = Object.assign;
  $.param = params => Object.entries(params)
    .map(([name, value]) => `${encodeURIComponent(name)}=${encodeURIComponent(value ?? '')}`)
    .join('&');

  const Craft = {
    sites,
    siteId: selectedSiteId,
    baseUrl: `${origin}/admin?site=${sites.find(site => site.id === selectedSiteId).handle}`,
    omitScriptNameInUrls: true,
    BaseElementIndex: {extend(definition) { return definition; }},
    registerElementIndexClass() {},
    t(_category, message) { return message; },
    ui: {
      createButton() {
        return {
          handlers: {},
          addClass() { return this; },
          on(event, callback) { this.handlers[event] = callback; return this; },
          remove() {},
        };
      },
    },
  };
  const runtime = vm.createContext({Craft, $, document, window: {location}, history, URL, URLSearchParams});
  vm.runInContext(`Object.assign(Craft, {\n${helpers}\n});`, runtime, {filename: 'installed-Craft-url-helpers.js'});
  vm.runInContext(pluginScript.source, runtime, {filename: pluginScript.filename});

  const index = Object.assign({}, Craft.CookieConsent.CookieIndex, {
    siteId: selectedSiteId,
    settings: {context},
    $source: {},
    events: {},
    on(event, callback) { this.events[event] = callback; },
    base() { this.events.selectSource(); },
    getSite() { return Craft.sites.find(site => site.id === this.siteId); },
    addButton(button) { this.button = button; },
  });
  return {Craft, index, location, crumbs, nav};
}

function assertSiteUrl(value, handle, expectedPath) {
  const url = new URL(value);
  assert.equal(url.searchParams.get('site'), handle, `Wrong selected site: ${url.href}`);
  assert.equal(url.searchParams.has('siteId'), false, `Stale numeric selector remains: ${url.href}`);
  if (expectedPath) assert.equal(url.pathname, expectedPath);
  return url;
}

for (const pluginScript of pluginScripts) {
  // Negative control: verify that these real Craft helpers reproduce the reported
  // mismatch when given the old plugin's initial URL updates.
  const broken = fixture(pluginScript, `${indexPath}?site=first&status=enabled`, 1);
  broken.Craft.setQueryParam('site', null);
  broken.Craft.setQueryParam('siteId', 1);
  assert.equal(new URL(broken.crumbs[1].href).searchParams.get('site'), 'second');
  assert.equal(new URL(broken.crumbs[1].href).searchParams.get('siteId'), '1');

  // The full Craft 5 index switches sites through breadcrumb GET navigation. Each
  // iteration represents a newly rendered page, without a selectSite JS event.
  let nextUrl = `${indexPath}?site=first&status=enabled&search=cookie`;
  for (const selectedSiteId of [1, 2, 1, 2]) {
    const page = fixture(pluginScript, nextUrl, selectedSiteId);
    const untouchedNav = page.nav.slice(2).map(link => link.href);
    page.index.init(null, null, null);
    const current = sites.find(site => site.id === selectedSiteId);
    const pageUrl = assertSiteUrl(page.location.href, current.handle, indexPath);
    assert.equal(pageUrl.searchParams.get('status'), 'enabled');
    assert.equal(pageUrl.searchParams.get('search'), 'cookie');
    for (const [i, link] of page.crumbs.entries()) {
      const target = assertSiteUrl(link.href, sites[i].handle, indexPath);
      assert.equal(target.searchParams.get('status'), 'enabled');
    }
    assertSiteUrl(page.nav[0].href, current.handle, '/admin/42cookie-consent/content');
    assert.equal(new URL(page.nav[0].href).searchParams.get('tab'), 'copy');
    assertSiteUrl(page.nav[1].href, current.handle, '/admin/42cookie-consent/settings/general');
    assert.deepEqual(page.nav.slice(2).map(link => link.href), untouchedNav);
    nextUrl = page.crumbs.find(link => new URL(link.href).searchParams.get('site') !== current.handle).href;
    page.index.button.handlers.click();
    assertSiteUrl(page.location.href, current.handle, '/admin/42cookie-consent/cookies/new');
  }

  // A stale link that reaches initialization is normalized too; the controller
  // separately redirects legacy numeric-only URLs before Craft renders the index.
  const stale = fixture(pluginScript, `${indexPath}?site=second&siteId=1&status=enabled`, 2);
  stale.index.init(null, null, null);
  assertSiteUrl(stale.location.href, 'second', indexPath);
  for (const [i, link] of stale.crumbs.entries()) assertSiteUrl(link.href, sites[i].handle, indexPath);

  // Keep support for embedded/older menus that do emit selectSite, and make the
  // create link follow the index even when Craft.baseUrl still has the old handle.
  const inline = fixture(pluginScript, `${indexPath}?site=first`, 1);
  inline.index.init(null, null, null);
  inline.index.siteId = 2;
  inline.index.events.selectSite();
  assertSiteUrl(inline.location.href, 'second', indexPath);
  inline.index.button.handlers.click();
  assertSiteUrl(inline.location.href, 'second', '/admin/42cookie-consent/cookies/new');

  const modal = fixture(pluginScript, `${indexPath}?site=first`, 1, 'modal');
  const beforeModal = [modal.location.href, ...modal.crumbs.map(link => link.href), ...modal.nav.map(link => link.href)];
  modal.index.syncSiteNavigation();
  assert.deepEqual([modal.location.href, ...modal.crumbs.map(link => link.href), ...modal.nav.map(link => link.href)], beforeModal);

  console.log(`Index navigation passed (${pluginScript.filename}): real Craft URL helpers, clean initialization, repeated site switches, breadcrumb targets, stale selector cleanup, and New Cookie links.`);
}
