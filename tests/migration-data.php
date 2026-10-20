<?php
require __DIR__ . '/fixtures.php';

use craft\db\Table;
use fortytwostudio\cookieconsent\CookieConsent;
use fortytwostudio\cookieconsent\migrations\m261020_130000_add_multisite;
use fortytwostudio\cookieconsent\migrations\m261020_150000_assign_legacy_to_primary;
use fortytwostudio\cookieconsent\models\SettingsModel;
use fortytwostudio\cookieconsent\services\KnownCookies;
use fortytwostudio\cookieconsent\services\PrimarySiteUpgrade;
use fortytwostudio\cookieconsent\services\SiteContext;
use fortytwostudio\cookieconsent\services\SiteData;

/**
 * Exercises the production migration's data/retry phase against real SQLite SQL.
 * Craft does not support SQLite as a site database: the final schema is prepared
 * here, and Element queries/saves use a small fixture. MySQL/PostgreSQL DDL and
 * Craft propagation must still be checked on a staging copy of the actual site.
 */
class MigrationSqliteSchema extends yii\db\sqlite\Schema
{
    public function findIndexes($table): array
    {
        $result = [];
        foreach ($this->getTableIndexes($table, true) as $index) {
            $result[$index->name] = ['columns' => $index->columnNames, 'unique' => $index->isUnique];
        }
        return $result;
    }
}

class MigrationCookie extends yii\base\Model
{
    public ?int $id = null;
    public ?int $siteId = null;
    public ?int $cookieSiteId = null;
    public ?string $uid = null;
    public bool $enabled = true;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;
    public ?DateTime $dateDeleted = null;
    public ?string $type = null;
    public ?string $cookieId = null;
    public ?string $domain = null;
    public ?string $duration = null;
    public ?string $description = null;
    public static function find(): MigrationCookieQuery { return new MigrationCookieQuery(); }
}

class MigrationCookieQuery
{
    private ?int $siteId = null;
    private ?int $id = null;
    private ?string $uid = null;
    private ?bool $trashed = false;
    public function siteId(int $siteId): self { $this->siteId = $siteId; return $this; }
    public function id(int $id): self { $this->id = $id; return $this; }
    public function uid(string $uid): self { $this->uid = $uid; return $this; }
    public function trashed(?bool $trashed): self { $this->trashed = $trashed; return $this; }
    public function status($status): self { return $this; }
    public function one(): ?MigrationCookie { return $this->all()[0] ?? null; }
    public function all(): array
    {
        $query = (new yii\db\Query())->select(['c.*', 'e.uid', 'e.enabled', 'e.dateCreated', 'e.dateUpdated', 'e.dateDeleted'])->from(['c' => '{{%forty_cookies_enabled}}'])
            ->innerJoin(['e' => Table::ELEMENTS], '[[e.id]] = [[c.id]]')->orderBy('c.id');
        if ($this->siteId !== null) $query->andWhere(['c.cookieSiteId' => $this->siteId]);
        if ($this->id !== null) $query->andWhere(['c.id' => $this->id]);
        if ($this->uid !== null) $query->andWhere(['e.uid' => $this->uid]);
        if ($this->trashed !== null) $query->andWhere($this->trashed ? ['not', ['e.dateDeleted' => null]] : ['e.dateDeleted' => null]);
        $rows = $query->all();
        return array_map(static function (array $row): MigrationCookie {
            $row['siteId'] = (int)$row['cookieSiteId'];
            foreach (['dateCreated', 'dateUpdated', 'dateDeleted'] as $attribute) {
                $row[$attribute] = $row[$attribute] === null ? null : new DateTime($row[$attribute], new DateTimeZone('UTC'));
            }
            return new MigrationCookie($row);
        }, $rows);
    }
}

// This stand-in is local to this PHP process; site-data.php separately loads and
// validates the real CookieElement class against the real Craft parent classes.
class_alias(MigrationCookie::class, 'fortytwostudio\\cookieconsent\\elements\\CookieElement');

class MigrationElements
{
    public array $deletedIds = [];
    public string $now = '2026-10-20 12:00:00';
    private int $nextUid = 100;

    public function saveElement(MigrationCookie $cookie, bool $validate = true, bool $propagate = true): bool
    {
        $db = Craft::$app->getDb();
        $cookie->uid ??= sprintf('00000000-0000-4000-8000-%012d', $this->nextUid++);
        $cookie->dateCreated ??= new DateTime($this->now, new DateTimeZone('UTC'));
        $cookie->dateUpdated ??= clone $cookie->dateCreated;
        $elementRow = ['uid' => $cookie->uid, 'enabled' => $cookie->enabled, 'dateCreated' => $cookie->dateCreated->format('Y-m-d H:i:s'), 'dateUpdated' => $cookie->dateUpdated->format('Y-m-d H:i:s'), 'dateDeleted' => $cookie->dateDeleted?->format('Y-m-d H:i:s')];
        if ($cookie->id === null) {
            $db->createCommand()->insert(Table::ELEMENTS, $elementRow)->execute();
            $cookie->id = (int)$db->getLastInsertID();
        } else {
            $db->createCommand()->update(Table::ELEMENTS, $elementRow, ['id' => $cookie->id])->execute();
        }
        $row = array_intersect_key($cookie->getAttributes(), array_flip(['id', 'cookieSiteId', 'type', 'cookieId', 'domain', 'duration', 'description']));
        $db->createCommand()->upsert('{{%forty_cookies_enabled}}', $row)->execute();
        return true;
    }

    public function getElementById(int $id, ?string $type = null, ?int $siteId = null): ?MigrationCookie
    {
        $query = MigrationCookie::find()->id($id);
        if ($siteId !== null) $query->siteId($siteId);
        return $query->one();
    }

    public function deleteElement(MigrationCookie $cookie, bool $hardDelete = false): bool
    {
        check(!$hardDelete, 'Generated secondary copy was permanently deleted');
        $cookie->dateDeleted = new DateTime($this->now, new DateTimeZone('UTC'));
        Craft::$app->getDb()->createCommand()->update(Table::ELEMENTS, ['dateDeleted' => $cookie->dateDeleted->format('Y-m-d H:i:s')], ['id' => $cookie->id])->execute();
        $this->deletedIds[] = $cookie->id;
        return true;
    }
}

function migrationFixture(bool $withThirdSite = false): array
{
    $app = harnessApp(['class' => craft\db\Connection::class, 'schemaMap' => ['sqlite' => MigrationSqliteSchema::class]]);
    $app->generalOverrides = ['omitScriptNameInUrls' => true];
    $app->testSites->sites[2]->setBaseUrl('https://secondary.test/b/');
    if ($withThirdSite) {
        $app->testSites->sites[3] = new craft\models\Site(['id' => 3, 'uid' => 'cccccccc-3333-4333-8333-cccccccccccc', 'name' => 'C', 'handle' => 'c', 'language' => 'fr', 'hasUrls' => true, 'baseUrl' => 'https://third.test/c/']);
    }
    $app->testElements = new MigrationElements();
    $plugin = (new ReflectionClass(CookieConsent::class))->newInstanceWithoutConstructor();
    $plugin->id = 'forty-cookieconsent';
    $plugin->setSettings(['siteDomain' => 'example.test']);
    CookieConsent::setInstance($plugin);
    $db = $app->getDb();
    $db->createCommand()->createTable(Table::SITES, ['id' => 'pk'])->execute();
    foreach ($app->testSites->getAllSiteIds() as $id) $db->createCommand()->insert(Table::SITES, ['id' => $id])->execute();
    $db->createCommand()->createTable(Table::ELEMENTS, ['id' => 'pk', 'uid' => 'text NOT NULL UNIQUE', 'enabled' => 'integer NOT NULL', 'dateCreated' => 'text', 'dateUpdated' => 'text', 'dateDeleted' => 'text'])->execute();
    $contentColumns = ['id' => 'pk', 'siteId' => 'integer UNIQUE'];
    foreach (SiteData::defaults() as $key => $value) $contentColumns[$key] = 'text';
    $contentColumns[] = 'FOREIGN KEY ([[siteId]]) REFERENCES {{%sites}} ([[id]]) ON DELETE CASCADE';
    $db->createCommand()->createTable(SiteData::CONTENT_TABLE, $contentColumns)->execute();
    $db->createCommand()->createTable(SiteData::TRACKED_TABLE, [
        'id' => 'pk', 'siteId' => 'integer UNIQUE', 'accepted' => 'integer NOT NULL DEFAULT 0', 'rejected' => 'integer NOT NULL DEFAULT 0',
        'FOREIGN KEY ([[siteId]]) REFERENCES {{%sites}} ([[id]]) ON DELETE CASCADE',
    ])->execute();
    $db->createCommand()->createTable('{{%forty_cookies_enabled}}', [
        'id' => 'pk', 'cookieSiteId' => 'integer NOT NULL', 'type' => 'text', 'cookieId' => 'text', 'domain' => 'text', 'duration' => 'text', 'description' => 'text',
        'FOREIGN KEY ([[cookieSiteId]]) REFERENCES {{%sites}} ([[id]]) ON DELETE CASCADE',
        'FOREIGN KEY ([[id]]) REFERENCES {{%elements}} ([[id]]) ON DELETE CASCADE',
    ])->execute();
    $db->createCommand()->createIndex('cookie_owner', '{{%forty_cookies_enabled}}', ['cookieSiteId'])->execute();
    return [$app, $db, $plugin];
}

function seedLegacyOriginals(): array
{
    $definitions = [
        ['cccccccc-3333-4333-8333-cccccccccccc', '_ga', 'example.test', true],
        ['dddddddd-4444-4444-8444-dddddddddddd', '_ga', 'example.test', false],
        ['eeeeeeee-5555-4555-8555-eeeeeeeeeeee', 'cc_cookie', 'example.test', true],
        ['ffffffff-6666-4666-8666-ffffffffffff', '_GRECAPTCHA', '.google.com', true],
    ];
    $cookies = [];
    foreach ($definitions as [$uid, $name, $domain, $enabled]) {
        $cookie = new MigrationCookie([
            'uid' => $uid, 'siteId' => 1, 'cookieSiteId' => 1, 'enabled' => $enabled,
            'type' => 'necessary', 'cookieId' => $name, 'domain' => $domain,
            'duration' => 'Session', 'description' => 'Original description',
            'dateCreated' => new DateTime('2025-02-01 12:00:00', new DateTimeZone('UTC')),
        ]);
        Craft::$app->getElements()->saveElement($cookie);
        $cookies[] = $cookie;
    }
    return $cookies;
}

function legacyContentFixture(): array
{
    return array_replace(SiteData::defaults(), [
        'popupTitle' => 'Primary historical title',
        'requiredCookies' => 'Historical cookie explanation',
        'triggerIcon' => 42,
    ]);
}

function legacyCopyUid(string $sourceUid, string $siteUid): string
{
    // Generate 3.13.0 fixture provenance independently of production cleanup.
    return Symfony\Component\Uid\Uuid::v5(
        Symfony\Component\Uid\Uuid::fromString(Symfony\Component\Uid\Uuid::NAMESPACE_URL),
        'fortytwo-cookieconsent/multisite/' . $sourceUid . '/' . $siteUid,
    )->toRfc4122();
}

function seedOldSiteCopies(int $siteId, array $sources): array
{
    $site = SiteContext::resolve($siteId);
    $copies = [];
    foreach ($sources as $source) {
        $copy = new MigrationCookie([
            'uid' => legacyCopyUid($source->uid, $site->uid),
            'siteId' => $siteId, 'cookieSiteId' => $siteId, 'enabled' => $source->enabled,
            'dateCreated' => new DateTime('2026-10-20 12:00:00', new DateTimeZone('UTC')),
        ]);
        foreach (['type', 'cookieId', 'domain', 'duration', 'description'] as $attribute) $copy->{$attribute} = $source->{$attribute};
        if ($copy->cookieId === SiteContext::cookieName(1)) $copy->cookieId = SiteContext::cookieName($siteId);
        if (in_array($copy->domain, ['example.test', '.example.test'], true)) $copy->domain = KnownCookies::siteDomain($siteId);
        Craft::$app->getElements()->saveElement($copy, false, false);
        $copies[$source->uid] = $copy;
    }
    return $copies;
}

// Fresh pre-multisite upgrade: historical data belongs only to the primary site.
[$app, $db, $plugin] = migrationFixture();
$originals = seedLegacyOriginals();
$plugin->setSettings(['popupBackgroundColour' => '123abc', 'excludeIds' => [91], 'sendGtag' => false]);
$db->createCommand()->insert(SiteData::CONTENT_TABLE, ['siteId' => null] + legacyContentFixture())->execute();
$db->createCommand()->insert(SiteData::TRACKED_TABLE, ['siteId' => null, 'accepted' => 8, 'rejected' => 2])->execute();
$migration = new m261020_130000_add_multisite(['db' => $db, 'compact' => true]);
check($migration->safeUp(), 'Fresh primary-only migration failed');
$primary = MigrationCookie::find()->siteId(1)->all();
$secondary = MigrationCookie::find()->siteId(2)->all();
check(count($primary) === 6, 'Primary lost historical cookies or added duplicate defaults');
check($primary[0]->id === $originals[0]->id && $primary[1]->id === $originals[1]->id && $primary[1]->enabled === false, 'Original cookie IDs, same-name duplicates, or disabled state changed');
check($primary[2]->id === $originals[2]->id && $primary[2]->cookieId === SiteContext::cookieName(1), 'Primary consent declaration was replaced instead of renamed');
$defaultNames = array_column(KnownCookies::craftDefaults(2), 'cookieId');
$secondaryNames = array_map(static fn(MigrationCookie $cookie): ?string => $cookie->cookieId, $secondary);
sort($defaultNames); sort($secondaryNames);
check($secondaryNames === $defaultNames, 'Historical catalog leaked into the secondary site');
check(SiteData::getContent(1)['popupTitle'] === 'Primary historical title' && (int)SiteData::getContent(1)['triggerIcon'] === 42, 'Primary lost historical content');
check(SiteData::getContent(2)['popupTitle'] === SiteData::defaults(2)['popupTitle'] && SiteData::getContent(2)['triggerIcon'] === null, 'Secondary inherited historical content');
check(SiteData::getContent(2)['popupFooter'] === SiteData::defaults(2)['popupFooter'], 'Secondary default privacy URL is wrong');
check(SiteData::getLogs(1) === ['accepted' => 8, 'rejected' => 2, 'total' => 10], 'Historical counts were not assigned to primary');
check(SiteData::getLogs(2) === ['accepted' => 0, 'rejected' => 0, 'total' => 0], 'Historical counts leaked to secondary');
check(SiteData::getLegacyLogs()['total'] === 0, 'Consumed historical counts remain pending');
check($plugin->getSiteSettings(1)->popupBackgroundColour === '123abc' && $plugin->getSiteSettings(1)->excludeIds === [91] && $plugin->getSiteSettings(1)->sendGtag === false, 'Primary lost historical settings');
check($plugin->getSiteSettings(2)->popupBackgroundColour === (new SettingsModel())->popupBackgroundColour && $plugin->getSiteSettings(2)->excludeIds === [] && $plugin->getSiteSettings(2)->sendGtag === true, 'Secondary inherited historical settings');
SiteData::logConsent(1, true);
check($migration->safeUp(), 'Fresh migration could not be replayed');
check(SiteData::getLogs(1) === ['accepted' => 9, 'rejected' => 2, 'total' => 11] && count(MigrationCookie::find()->siteId(2)->all()) === 3, 'Replay duplicated historical data');
$followup = new m261020_150000_assign_legacy_to_primary(['db' => $db, 'compact' => true]);
check($followup->safeUp() && SiteData::getLogs(1)['accepted'] === 9, 'Follow-up reimported an already-consumed fresh upgrade');

$db->createCommand()->insert('{{%forty_cookies_enabled}}', ['id' => 9999, 'cookieSiteId' => 1, 'cookieId' => 'orphan'])->execute();
try {
    $migration->safeUp();
    throw new RuntimeException('Orphan was silently accepted');
} catch (RuntimeException $exception) {
    check(str_contains($exception->getMessage(), '9999') && str_contains($exception->getMessage(), 'no records have been deleted'), 'Orphan failure was not actionable');
}
check((new yii\db\Query())->from('{{%forty_cookies_enabled}}')->where(['id' => 9999])->exists(), 'Orphan record was deleted');
echo "Fresh migration: primary-only content/catalog/settings/counts, original IDs, secondary defaults, replay, and non-destructive orphan detection passed.\n";

// Already-applied 3.13.0 fixture, including subsequently edited site data.
[$app, $db, $plugin] = migrationFixture(true);
$originals = seedLegacyOriginals();
$db->createCommand()->update('{{%forty_cookies_enabled}}', ['cookieId' => SiteContext::cookieName(1)], ['id' => $originals[2]->id])->execute();
KnownCookies::seedCraftDefaults(1);
$sources = MigrationCookie::find()->siteId(1)->all();
$copiesB = seedOldSiteCopies(2, $sources);
$copiesC = seedOldSiteCopies(3, $sources);
$firstUid = $originals[0]->uid;
$secondUid = $originals[1]->uid;
$thirdPartyUid = $originals[3]->uid;

// Independently verify that cleanup recognizes the exact 3.13.0 UUID provenance.
$copyUid = new ReflectionMethod(PrimarySiteUpgrade::class, 'copyUid');
check($copyUid->invoke(null, 'source', 'site') === 'a1c3edf1-d84b-5cdb-aff6-7f8e938ef54f', 'Cleanup UUID v5 known vector changed');
check($copyUid->invoke(null, $firstUid, $app->testSites->sites[2]->uid) === $copiesB[$firstUid]->uid, 'Cleanup UID does not match old copies');
check($copiesB[$firstUid]->uid !== $copiesB[$secondUid]->uid && $copiesB[$firstUid]->uid !== $copiesC[$firstUid]->uid, 'Source/site provenance collided');

$legacy = legacyContentFixture();
$db->createCommand()->insert(SiteData::CONTENT_TABLE, ['siteId' => null] + $legacy)->execute();
foreach ([1, 2, 3] as $siteId) {
    $copied = array_replace($legacy, ['popupFooter' => SiteData::defaults($siteId)['popupFooter']]);
    $db->createCommand()->insert(SiteData::CONTENT_TABLE, ['siteId' => $siteId] + $copied)->execute();
}
SiteData::saveContent(1, ['popupTitle' => 'Primary subsequently edited']);
SiteData::saveContent(2, ['popupTitle' => 'B subsequently edited']);
$db->createCommand()->insert(SiteData::TRACKED_TABLE, ['siteId' => null, 'accepted' => 8, 'rejected' => 2])->execute();
$db->createCommand()->insert(SiteData::TRACKED_TABLE, ['siteId' => 1, 'accepted' => 3, 'rejected' => 1])->execute();
$db->createCommand()->insert(SiteData::TRACKED_TABLE, ['siteId' => 2, 'accepted' => 4, 'rejected' => 2])->execute();
$plugin->setSettings([
    'popupBackgroundColour' => '123abc', 'excludeIds' => [91], 'sendGtag' => false,
    'sites' => [$app->testSites->sites[2]->uid => ['popupBackgroundColour' => '445566', 'excludeIds' => [27]]],
]);

// Exact field comparisons must protect edits even if another integration did
// not update Craft's timestamp, and timestamp evidence must be conservative.
$db->createCommand()->update('{{%forty_cookies_enabled}}', ['description' => 'B edited copy'], ['id' => $copiesB[$firstUid]->id])->execute();
$db->createCommand()->update(Table::ELEMENTS, ['enabled' => true], ['id' => $copiesB[$secondUid]->id])->execute();
$db->createCommand()->update(Table::ELEMENTS, ['dateUpdated' => '2026-10-21 12:00:00'], ['id' => $copiesC[$secondUid]->id])->execute();
$db->createCommand()->update(Table::ELEMENTS, ['dateUpdated' => '2026-10-22 12:00:00'], ['id' => $originals[3]->id])->execute();
$manual = new MigrationCookie(['uid' => '99999999-7777-4777-8777-999999999999', 'siteId' => 2, 'cookieSiteId' => 2, 'enabled' => true, 'type' => 'necessary', 'cookieId' => '_ga', 'domain' => 'secondary.test', 'duration' => 'Session', 'description' => 'Original description']);
$app->testElements->saveElement($manual);

// Fail the increment after the source row has been claimed/deleted. The
// transaction must restore that source and all earlier updates before retry.
// Yii's SQLite command splitter treats the trigger's inner semicolon as a
// statement separator, so install this fixture trigger through the same PDO.
$db->getMasterPdo()->exec($db->quoteSql("CREATE TRIGGER fail_legacy_increment BEFORE UPDATE ON {{%forty_cookies_tracked}} WHEN OLD.siteId = 1 AND NEW.accepted > OLD.accepted BEGIN SELECT RAISE(ABORT, 'fixture legacy increment failure'); END"));
$followup = new m261020_150000_assign_legacy_to_primary(['db' => $db, 'compact' => true]);
try {
    $followup->safeUp();
    throw new RuntimeException('Injected legacy counter failure did not occur');
} catch (Throwable $exception) {
    check(str_contains($exception->getMessage(), 'fixture legacy increment failure'), 'Unexpected follow-up error: ' . $exception->getMessage());
}
check(SiteData::getLogs(1) === ['accepted' => 3, 'rejected' => 1, 'total' => 4] && SiteData::getLegacyLogs()['total'] === 10, 'Failed adoption partially committed historical counts');
check(SiteData::getContent(3)['popupTitle'] === 'Primary historical title', 'Failed adoption partially committed content cleanup');
check(MigrationCookie::find()->id($copiesC[$firstUid]->id)->one() !== null, 'Failed adoption partially committed cookie cleanup');

// MySQL DDL can commit PDO while Yii's outer migration transaction remains
// logically active. Reproduce that bookkeeping state without a MySQL server.
$outerTransaction = $db->beginTransaction();
$db->getMasterPdo()->commit();
check($db->getTransaction()?->getIsActive() === true && !$db->getMasterPdo()->inTransaction(), 'DDL auto-commit bookkeeping fixture was not established');
try {
    $followup->safeUp();
    throw new RuntimeException('Injected increment failure did not occur after simulated DDL');
} catch (Throwable $exception) {
    check(str_contains($exception->getMessage(), 'fixture legacy increment failure'), 'Unexpected post-DDL follow-up error: ' . $exception->getMessage());
}
check(SiteData::getLogs(1) === ['accepted' => 3, 'rejected' => 1, 'total' => 4] && SiteData::getLegacyLogs()['total'] === 10, 'Post-DDL failure committed a partial historical count transfer');
check(SiteData::getContent(3)['popupTitle'] === 'Primary historical title', 'Post-DDL failure committed content cleanup');
check($db->getTransaction()?->getIsActive() === true && !$db->getMasterPdo()->inTransaction(), 'Recovery corrupted the caller transaction bookkeeping');
$db->createCommand('DROP TRIGGER fail_legacy_increment')->execute();

check($followup->safeUp(), '3.13.0 follow-up failed');
check(SiteData::getLogs(1) === ['accepted' => 11, 'rejected' => 3, 'total' => 14], 'Historical and current primary counts were not added correctly');
check(SiteData::getLegacyLogs()['total'] === 0 && SiteData::getLogs(2) === ['accepted' => 4, 'rejected' => 2, 'total' => 6], 'Legacy source was not consumed or secondary counts changed');
check(SiteData::getContent(1)['popupTitle'] === 'Primary subsequently edited' && SiteData::getContent(2)['popupTitle'] === 'B subsequently edited', 'User-edited primary or secondary content was overwritten');
check(SiteData::getContent(3)['popupTitle'] === SiteData::defaults(3)['popupTitle'] && SiteData::getContent(3)['triggerIcon'] === null, 'Untouched generated content did not reset to site defaults');
check(MigrationCookie::find()->id($copiesC[$firstUid]->id)->one() === null && MigrationCookie::find()->id($copiesC[$firstUid]->id)->trashed(true)->one() !== null, 'Proven untouched generated cookie was not soft-deleted');
check((new yii\db\Query())->from('{{%forty_cookies_enabled}}')->where(['id' => $copiesC[$firstUid]->id])->exists(), 'Soft deletion discarded the recoverable native cookie fields');
check(MigrationCookie::find()->id($copiesB[$firstUid]->id)->one()?->description === 'B edited copy', 'Edited generated cookie was deleted');
check(MigrationCookie::find()->id($copiesB[$secondUid]->id)->one()?->enabled === true, 'Enabled-state edit was deleted');
check(MigrationCookie::find()->id($copiesC[$secondUid]->id)->one() !== null, 'Timestamp-edited cookie was deleted');
check(MigrationCookie::find()->id($copiesB[$thirdPartyUid]->id)->one() !== null && MigrationCookie::find()->id($copiesC[$thirdPartyUid]->id)->one() !== null, 'Copy with uncertain source history was deleted');
check(MigrationCookie::find()->id($manual->id)->one() !== null, 'Manual same-name cookie was deleted');
foreach ([2, 3] as $siteId) {
    $names = array_map(static fn(MigrationCookie $cookie): ?string => $cookie->cookieId, MigrationCookie::find()->siteId($siteId)->all());
    foreach (KnownCookies::craftDefaults($siteId) as $definition) check(in_array($definition['cookieId'], $names, true), 'Secondary default declaration missing after cleanup');
}
check($plugin->getSiteSettings(2)->popupBackgroundColour === '445566' && $plugin->getSiteSettings(2)->excludeIds === [27] && $plugin->getSiteSettings(2)->sendGtag === true, 'Explicit secondary settings were lost or old flat defaults leaked');
check($plugin->getSiteSettings(3)->popupBackgroundColour === (new SettingsModel())->popupBackgroundColour && $plugin->getSiteSettings(3)->excludeIds === [], 'Untouched secondary site inherited legacy settings');
$activeIds = array_map(static fn(MigrationCookie $cookie): ?int => $cookie->id, MigrationCookie::find()->all());
SiteData::logConsent(1, true);
check($followup->safeUp(), 'Follow-up replay failed');
check(SiteData::getLogs(1) === ['accepted' => 12, 'rejected' => 3, 'total' => 15], 'Follow-up replay added historical counts twice');
check($activeIds === array_map(static fn(MigrationCookie $cookie): ?int => $cookie->id, MigrationCookie::find()->all()), 'Follow-up replay duplicated defaults or changed the catalog again');
$outerTransaction->commit();
check(!$db->getMasterPdo()->inTransaction() && $db->getTransaction() === null, 'Outer migration bookkeeping could not complete after recovery');
echo "3.13.0 follow-up: normal/post-DDL atomic rollback and retry, one-time primary count merge, exact-copy reset, conservative soft deletion, edited/manual data preservation, explicit settings, and replay passed.\n";

