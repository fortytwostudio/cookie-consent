<?php
require __DIR__ . '/fixtures.php';

use craft\helpers\ProjectConfig;
use fortytwostudio\cookieconsent\CookieConsent;
use fortytwostudio\cookieconsent\controllers\SettingsController;
use fortytwostudio\cookieconsent\models\SettingsModel;
use fortytwostudio\cookieconsent\services\SiteContext;

// Run the production action method. The application shell substitutes storage,
// authentication, and the redirect response; Craft's config packing is real.
class HarnessSettingsController extends SettingsController
{
    public string $redirected = '';
    public function redirect($url, $statusCode = 302): yii\web\Response
    {
        $this->redirected = $url;
        return new yii\web\Response(['statusCode' => $statusCode]);
    }
}

$app = harnessApp();
$a = $app->testSites->sites[1]->uid;
$b = $app->testSites->sites[2]->uid;
$initial = array_replace((new SettingsModel())->getAttributes(), [
    'siteDomain' => 'stored.example',
    'popupBackgroundColour' => 'abcdef',
    'sites' => [
        $a => ['isEnabled' => true, 'sendGtag' => true, 'popupBackgroundColour' => '112233', 'excludeIds' => [12]],
        $b => ['isEnabled' => false, 'siteDomain' => 'secondary.example', 'popupBackgroundColour' => '445566'],
    ],
]);
$project = new class {
    public array $data;
    public function get($path) { check($path === 'plugins.forty-cookieconsent.settings', 'Wrong project config path'); return $this->data; }
};
$project->data = ProjectConfig::packAssociativeArrays($initial);
check(isset($project->data['sites'][craft\services\ProjectConfig::ASSOC_KEY]), 'Fixture must exercise packed associative arrays');
$app->testProjectConfig = $project;
$plugin = (new ReflectionClass(CookieConsent::class))->newInstanceWithoutConstructor();
$plugin->id = 'forty-cookieconsent';
$plugin->setSettings($initial);
// Simulate config-file/environment overrides in the effective model.
$plugin->setSettings(['siteDomain' => 'environment.example', 'popupBackgroundColour' => 'eeeeee']);
CookieConsent::setInstance($plugin);
$app->testPlugins = new class($project) {
    public int $saves = 0;
    public function __construct(private object $project) {}
    public function savePluginSettings($plugin, array $settings): bool
    {
        $this->project->data = ProjectConfig::packAssociativeArrays($settings);
        $plugin->setSettings($settings);
        $this->saves++;
        return true;
    }
};
$controller = (new ReflectionClass(HarnessSettingsController::class))->newInstanceWithoutConstructor();
$controller->request = $app->getRequest();

$save = function (string $site, string $section, array $settings) use ($app, $controller): array {
    $app->getRequest()->body = compact('site', 'section', 'settings');
    $response = $controller->actionSave();
    check($response->statusCode === 302, 'Successful save did not redirect');
    check(str_contains($controller->redirected, 'site=' . $site), 'Redirect lost site selection');
    return ProjectConfig::unpackAssociativeArrays($app->testProjectConfig->data);
};

$stored = $save('a', 'google', ['sendGtag' => '0', 'siteDomain' => 'injected.example', 'sites' => []]);
check($stored['sites'][$a]['sendGtag'] === false, 'False switch was not persisted');
check($stored['sites'][$a]['excludeIds'] === [12], 'Saving Google tab lost exclusions');
check($stored['sites'][$b] === $initial['sites'][$b], 'Saving A changed B');
check($stored['siteDomain'] === 'stored.example' && $stored['popupBackgroundColour'] === 'abcdef', 'Environment overrides leaked into stored config');
check(!isset($stored['sites'][$a]['siteDomain']), 'Section allowlist accepted an unrelated field');

$afterA = $stored['sites'][$a];
$stored = $save('b', 'colours', ['popupBackgroundColour' => ' #778899 ', 'isEnabled' => '1']);
check($stored['sites'][$a] === $afterA, 'Saving B lost A settings');
check($stored['sites'][$b]['popupBackgroundColour'] === '778899', 'Colour normalization failed');
check($stored['sites'][$b]['isEnabled'] === false && $stored['sites'][$b]['siteDomain'] === 'secondary.example', 'Colour save replaced another B tab');

$afterB = $stored['sites'][$b];
$stored = $save('a', 'exclude', ['excludeIds' => []]);
check($stored['sites'][$a]['excludeIds'] === [], 'Empty exclusion override was lost');
check($stored['sites'][$a]['sendGtag'] === false, 'Third save reverted Google settings');
check($stored['sites'][$b] === $afterB && $app->testPlugins->saves === 3, 'Repeated saves altered another site');
check(SiteContext::cookieName(1) !== SiteContext::cookieName(2), 'Site cookie names collide');

// Conflicting site selectors must fail before a write.
$app->getRequest()->body = ['site' => 'a', 'siteId' => 2, 'section' => 'google', 'settings' => ['sendGtag' => true]];
try {
    $controller->actionSave();
    throw new RuntimeException('Conflicting site selectors were accepted');
} catch (yii\web\BadRequestHttpException) {
    check($app->testPlugins->saves === 3, 'Invalid selection wrote settings');
}

echo "Settings action: packed config, A/B/A saves, tab allowlists, false/empty values, environment isolation, redirects, and conflicting selectors passed.\n";
