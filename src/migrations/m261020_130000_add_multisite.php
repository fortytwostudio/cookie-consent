<?php
namespace fortytwostudio\cookieconsent\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;
use fortytwostudio\cookieconsent\elements\CookieElement;
use fortytwostudio\cookieconsent\services\KnownCookies;
use fortytwostudio\cookieconsent\services\PrimarySiteUpgrade;
use fortytwostudio\cookieconsent\services\SiteContext;
use fortytwostudio\cookieconsent\services\SiteData;
use RuntimeException;

/** Preserve original data on the primary site and make schema upgrades resumable. */
class m261020_130000_add_multisite extends Migration
{
    public function safeUp(): bool
    {
        $sites = Craft::$app->getSites()->getAllSites();
        $primary = Craft::$app->getSites()->getPrimarySite();
        $content = SiteData::CONTENT_TABLE;
        $tracked = SiteData::TRACKED_TABLE;
        $cookies = '{{%forty_cookies_enabled}}';

        // Do not discard corrupt historic records merely to add a foreign key.
        $orphans = (new Query())->select('c.id')->from(['c' => $cookies])
            ->leftJoin(['e' => Table::ELEMENTS], '[[e.id]] = [[c.id]]')
            ->where(['e.id' => null])->limit(10)->column();
        if ($orphans) {
            throw new RuntimeException('Cookie records have no corresponding Craft element: ' . implode(', ', $orphans) . '. Repair these records or restore a valid backup before retrying; no records have been deleted.');
        }

        $oldCookieSchema = !$this->db->getTableSchema($cookies, true)->getColumn('cookieSiteId');
        foreach ([$content, $tracked] as $table) {
            if (!$this->db->getTableSchema($table, true)->getColumn('siteId')) {
                $this->addColumn($table, 'siteId', $this->integer());
            }
            $this->ensureIndex($table, ['siteId'], true);
            $this->ensureForeignKey($table, ['siteId'], Table::SITES);
        }

        if ($oldCookieSchema) {
            $this->addColumn($cookies, 'cookieSiteId', $this->integer());
        }
        $this->update($cookies, ['cookieSiteId' => $primary->id], ['cookieSiteId' => null]);
        if ($this->db->getTableSchema($cookies, true)->getColumn('cookieSiteId')->allowNull) {
            $this->alterColumn($cookies, 'cookieSiteId', $this->integer()->notNull());
        }
        $this->ensureIndex($cookies, ['cookieSiteId']);
        $this->ensureForeignKey($cookies, ['cookieSiteId'], Table::SITES);
        $this->ensureForeignKey($cookies, ['id'], Table::ELEMENTS);
        $this->db->getSchema()->refresh();

        // The original cookie elements already belong to the primary site.
        // Only the consent declaration changes to match its scoped browser name.
        foreach (CookieElement::find()->siteId($primary->id)->status(null)->all() as $cookie) {
            if ($cookie->cookieId === 'cc_cookie') {
                $this->update($cookies, ['cookieId' => SiteContext::cookieName($primary->id)], ['id' => $cookie->id]);
            }
        }

        // Adopt original content before initialization can create a default row.
        // Also resumes data work left by an earlier/interrupted multisite update.
        PrimarySiteUpgrade::run();
        foreach ($sites as $site) {
            SiteData::initializeSite($site->id);
            KnownCookies::seedCraftDefaults($site->id);
        }
        return true;
    }

    private function ensureIndex(string $table, array $columns, bool $unique = false): void
    {
        if (Db::findIndex($table, $columns, $unique, $this->db) === null) {
            $this->createIndex(null, $table, $columns, $unique);
        }
    }

    private function ensureForeignKey(string $table, array $columns, string $reference): void
    {
        $this->db->getSchema()->refreshTableSchema($table);
        if (Db::findForeignKey($table, $columns, $this->db) === null) {
            $this->addForeignKey(null, $table, $columns, $reference, ['id'], 'CASCADE');
        }
    }

    public function safeDown(): bool
    {
        echo "Multisite data cannot be merged automatically. Restore the pre-upgrade database backup to revert.\n";
        return false;
    }
}
