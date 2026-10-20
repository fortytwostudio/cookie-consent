<?php
require __DIR__ . '/fixtures.php';

use craft\web\Request;
use fortytwostudio\cookieconsent\controllers\CookiesController;
use fortytwostudio\cookieconsent\services\SiteContext;
use yii\web\HttpException;
use yii\web\Response;

class HarnessCookieIndexController extends CookiesController
{
    public ?string $redirected = null;
    public ?array $rendered = null;
    public function redirect($url, $statusCode = 302): Response
    {
        $this->redirected = $url;
        return new Response(['statusCode' => $statusCode]);
    }
    public function renderTemplate(string $template, array $variables = [], ?string $templateMode = null): Response
    {
        $this->rendered = compact('template', 'variables');
        return new Response();
    }
}

// Use the actual Craft Request getters. In Craft 5 getParam() prefers query
// values over body values; a simplified request double can hide this regression.
// Initialization is bypassed because routing/installed-site discovery is outside
// this test. The production resolver and request parameter parsing run normally.
$app = harnessApp();
Craft::setAlias('@web', 'https://example.test');
$originalMethod = $_SERVER['REQUEST_METHOD'] ?? null;
$select = static function (string $method, array $query = [], array $body = []) use ($app): int {
    $_SERVER['REQUEST_METHOD'] = $method;
    $request = (new ReflectionClass(Request::class))->newInstanceWithoutConstructor();
    $request->generalConfig = $app->getConfig()->getGeneral();
    $request->setIsConsoleRequest(false);
    $request->setIsCpRequest(true);
    $request->setHostInfo('https://example.test');
    $request->setScriptUrl('/index.php');
    $request->setQueryParams($query);
    $request->setBodyParams($body);
    $app->set('request', $request);
    return SiteContext::forCpRequest()->id;
};
$reject = static function (int $status, callable $operation, string $message): void {
    try {
        $operation();
    } catch (HttpException $exception) {
        check($exception->statusCode === $status, $message . ': wrong HTTP status ' . $exception->statusCode);
        return;
    }
    throw new RuntimeException($message);
};
$index = static function (array $query) use ($app, $select): HarnessCookieIndexController {
    $select('GET', $query);
    $controller = (new ReflectionClass(HarnessCookieIndexController::class))->newInstanceWithoutConstructor();
    $controller->request = $app->getRequest();
    $controller->actionIndex();
    return $controller;
};

try {
    // Craft's full-page element-index breadcrumb replaces `site` in the query
    // while retaining unrelated keys, including the old plugin `siteId` key.
    check($select('GET', ['siteId' => '1', 'site' => 'b']) === 2, 'Stale siteId blocked switching the Cookies index to B');
    check($select('GET', ['siteId' => '2', 'site' => 'a']) === 1, 'Stale siteId blocked switching back to A');
    check($select('GET', ['site' => 'b']) === 2, 'Canonical site-handle URL failed');
    check($select('GET', ['siteId' => '2']) === 2, 'Existing siteId-only link stopped working');
    check($select('GET', ['site' => 'b', 'siteId' => '2']) === 2, 'Matching selectors were rejected');
    $reject(400, fn() => $select('GET', ['site' => ['b'], 'siteId' => '2']), 'Array site selector was accepted');
    $reject(400, fn() => $select('GET', ['site' => 'b', 'siteId' => ['1']]), 'Malformed stale siteId was accepted');
    $reject(400, fn() => $select('GET', ['site' => 'unknown', 'siteId' => '2']), 'Unknown canonical site fell back to the legacy ID');

    // Normalize before Craft renders its CP assets and site breadcrumbs, whose
    // selected site is read from the `site` handle in the URL.
    foreach ([['siteId' => '1', 'site' => 'b'], ['siteId' => '2']] as $query) {
        $controller = $index($query + ['search' => 'cookie', 'p' => 'admin/42cookie-consent/cookies']);
        check($controller->redirected !== null && $controller->rendered === null, 'Legacy index URL rendered before normalization');
        parse_str(parse_url($controller->redirected, PHP_URL_QUERY), $params);
        check(($params['site'] ?? null) === 'b' && !array_key_exists('siteId', $params), 'Index redirect did not remove stale numeric selection');
        check(($params['search'] ?? null) === 'cookie', 'Index redirect lost unrelated query parameters');
    }
    $controller = $index(['site' => 'b']);
    check($controller->redirected === null && $controller->rendered['template'] === 'forty-cookieconsent/cookies/index', 'Canonical index URL entered a redirect loop');
    check($controller->rendered['variables']['cookieSite']->id === 2 && $controller->rendered['variables']['selectedSite']->id === 2, 'Canonical index rendered the wrong site');

    // Forged write selectors still conflict; GET compatibility must not disable
    // server-side validation of submitted site ownership.
    $reject(400, fn() => $select('POST', [], ['site' => 'a', 'siteId' => '2']), 'Contradictory posted site selectors were accepted');
    $reject(400, fn() => $select('POST', ['site' => 'a', 'siteId' => '2']), 'Contradictory write query selectors were accepted');
    check($select('POST', [], ['site' => 'b', 'siteId' => '2']) === 2, 'Matching posted selectors failed');

    // Content/Settings post only `site`; New Cookie posts only `siteId`.
    // Select the posted pair as a unit so stale query values cannot redirect a
    // valid write to another site or supply the absent half of that pair.
    check($select('POST', ['site' => 'a', 'siteId' => '1'], ['site' => 'b']) === 2, 'Stale query changed the site posted by Content/Settings');
    check($select('POST', ['site' => 'a', 'siteId' => '1'], ['siteId' => '2']) === 2, 'Stale query changed the site posted by New Cookie');
    check($select('POST', ['site' => 'a', 'siteId' => '1'], ['site' => 'b', 'siteId' => '2']) === 2, 'Complete posted selection was overridden by query');
    $reject(400, fn() => $select('POST', ['site' => 'b', 'siteId' => '2'], ['site' => 'a', 'siteId' => '2']), 'Matching query concealed contradictory posted selectors');
    $reject(400, fn() => $select('POST', ['site' => 'b'], ['siteId' => ['2']]), 'Malformed body selector fell back to query');
    $reject(400, fn() => $select('POST', ['site' => 'b'], ['site' => 'unknown']), 'Unknown posted site fell back to query');

    $app->testIdentity->admin = false;
    $app->testIdentity->permissions = ['accessPlugin-forty-cookieconsent', 'editSite:' . $app->testSites->sites[2]->uid];
    check($select('GET', ['site' => 'b', 'siteId' => '1']) === 2, 'Permitted secondary site was blocked by a stale ID');
    $reject(403, fn() => $select('GET', ['site' => 'a', 'siteId' => '2']), 'Canonical GET selector bypassed site permission');
    $reject(403, fn() => $select('POST', [], ['siteId' => '1']), 'Posted site ID bypassed site permission');
    $reject(403, fn() => $select('POST', ['site' => 'b'], ['siteId' => '1']), 'Permitted query concealed an unauthorized posted site');
    check($select('GET') === 2, 'Restricted editor did not default to an editable site');

    $app->testIdentity->permissions = ['accessPlugin-forty-cookieconsent'];
    $reject(403, fn() => $select('GET', ['site' => 'b']), 'User with no editable sites was accepted');
    $app->testIdentity->permissions = ['editSite:' . $app->testSites->sites[2]->uid];
    $reject(403, fn() => $select('GET', ['site' => 'b']), 'Site permission bypassed missing plugin access');
    $app->testIdentity = null;
    $reject(403, fn() => $select('GET', ['site' => 'b']), 'Anonymous user accessed cookie consent management');
} finally {
    if ($originalMethod === null) {
        unset($_SERVER['REQUEST_METHOD']);
    } else {
        $_SERVER['REQUEST_METHOD'] = $originalMethod;
    }
}

echo "CP site selection (real Craft Request): stale GET IDs, index URL normalization, authoritative posted selections, write conflicts, malformed selectors, site/plugin permissions, and restricted defaults passed.\n";
