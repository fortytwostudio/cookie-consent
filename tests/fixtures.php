<?php
require_once __DIR__ . '/bootstrap.php';

/** Site registry and application shell around real Craft models and Yii services. */
class HarnessSites
{
    public array $sites;
    public int $current = 1;

    public function __construct()
    {
        $this->sites = [
            1 => new craft\models\Site(['id' => 1, 'uid' => 'aaaaaaaa-1111-4111-8111-aaaaaaaaaaaa', 'name' => 'A', 'handle' => 'a', 'language' => 'en-GB', 'hasUrls' => true, 'baseUrl' => 'https://example.test/a/']),
            2 => new craft\models\Site(['id' => 2, 'uid' => 'bbbbbbbb-2222-4222-8222-bbbbbbbbbbbb', 'name' => 'B', 'handle' => 'b', 'language' => 'cy', 'hasUrls' => true, 'baseUrl' => 'https://example.test/b/']),
        ];
    }

    public function getCurrentSite() { return $this->sites[$this->current]; }
    public function setCurrentSite($site) { $this->current = $site instanceof craft\models\Site ? $site->id : $site; }
    public function getPrimarySite() { return $this->sites[1]; }
    public function getSiteById($id) { return $this->sites[$id] ?? null; }
    public function getSiteByUid($uid) { foreach ($this->sites as $site) { if ($site->uid === $uid) return $site; } return null; }
    public function getSiteByHandle($handle) { foreach ($this->sites as $site) { if ($site->handle === $handle) return $site; } return null; }
    public function getAllSites() { return array_values($this->sites); }
    public function getAllSiteIds() { return array_keys($this->sites); }
}

class HarnessRequest extends craft\console\Request
{
    public array $body = [];
    public array $query = [];
    public string $method = 'POST';
    public function getIsPost() { return $this->method === 'POST'; }
    public function getIsGet() { return $this->method === 'GET'; }
    public function getParam($name, $default = null) { return $this->query[$name] ?? $this->body[$name] ?? $default; }
    public function getBodyParam($name, $default = null) { return $this->body[$name] ?? $default; }
    public function getRequiredBodyParam($name) { if (!array_key_exists($name, $this->body)) throw new yii\web\BadRequestHttpException('Missing body parameter'); return $this->body[$name]; }
    public function getQueryParam($name, $default = null) { return $this->query[$name] ?? $default; }
    public function getHostName() { return 'example.test'; }
}

class HarnessIdentity
{
    public bool $admin = true;
    public array $permissions = [];
    public function can(string $permission): bool { return in_array($permission, $this->permissions, true); }
}

class HarnessApp extends yii\console\Application
{
    public HarnessSites $testSites;
    public mixed $testProjectConfig;
    public mixed $testPlugins;
    public mixed $testElements;
    public ?HarnessIdentity $testIdentity = null;
    public array $generalOverrides = [];
    public function getIsInstalled(): bool { return false; }
    public function getIsInitialized(): bool { return false; }
    public function getIsMultiSite(): bool { return true; }
    public function getSites() { return $this->testSites; }
    public function getProjectConfig() { return $this->testProjectConfig; }
    public function getPlugins() { return $this->testPlugins; }
    public function getElements() { return $this->testElements; }
    public function getUser() { return new class($this->testIdentity) { public function __construct(private ?HarnessIdentity $identity) {} public function getIdentity() { return $this->identity; } }; }
    public function getSession() { return new class { public function setError($message) { throw new RuntimeException($message); } public function setNotice($message) {} }; }
    public function getConfig() { return new class($this->generalOverrides) { public function __construct(private array $overrides) {} public function getLoadingConfigFile() { return null; } public function getGeneral() { return new craft\config\GeneralConfig($this->overrides); } }; }
}

function harnessApp(array $dbConfig = []): HarnessApp
{
    global $vendor;
    $app = new HarnessApp([
        'id' => 'cookie-consent-regressions',
        'basePath' => __DIR__,
        'components' => [
            'request' => ['class' => HarnessRequest::class, 'scriptFile' => __FILE__],
            'db' => array_replace(['class' => yii\db\Connection::class, 'dsn' => 'sqlite::memory:', 'tablePrefix' => 'test_'], $dbConfig),
            'i18n' => ['class' => yii\i18n\I18N::class, 'translations' => [
                'app' => ['class' => yii\i18n\PhpMessageSource::class, 'basePath' => $vendor . '/craftcms/cms/src/translations'],
                'forty-cookieconsent' => ['class' => yii\i18n\PhpMessageSource::class, 'basePath' => __DIR__],
            ]],
        ],
    ]);
    $app->testSites = new HarnessSites();
    $app->testIdentity = new HarnessIdentity();
    return $app;
}
