<?php
namespace fortytwostudio\cookieconsent\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use fortytwostudio\cookieconsent\services\KnownCookies;
use fortytwostudio\cookieconsent\services\SiteData;

class Install extends Migration
{
    public function safeUp(): bool
    {
        $this->createTables();
        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            SiteData::initializeSite($site->id);
            KnownCookies::seedCraftDefaults($site->id);
        }
        return true;
    }

    public function safeDown(): bool
    {
        $this->dropProjectConfig();
        $this->dropTables();
        return true;
    }

    public function createTables(): void
    {
        $tracked = SiteData::TRACKED_TABLE;
        $this->archiveTableIfExists($tracked);
        $this->createTable($tracked, [
            'id' => $this->primaryKey(),
            // NULL may occur temporarily on legacy upgrades before primary attribution.
            'siteId' => $this->integer(),
            'accepted' => $this->integer()->notNull()->defaultValue(0),
            'rejected' => $this->integer()->notNull()->defaultValue(0),
        ]);
        $this->createIndex(null, $tracked, ['siteId'], true);
        $this->addForeignKey(null, $tracked, ['siteId'], Table::SITES, ['id'], 'CASCADE');

        $cookies = '{{%forty_cookies_enabled}}';
        $this->archiveTableIfExists($cookies);
        $this->createTable($cookies, [
            'id' => $this->primaryKey(),
            'cookieSiteId' => $this->integer()->notNull(),
            'type' => $this->string(255),
            'cookieId' => $this->string(255),
            'domain' => $this->string(255),
            'duration' => $this->string(255),
            'description' => $this->text(),
        ]);
        $this->createIndex(null, $cookies, ['cookieSiteId']);
        $this->addForeignKey(null, $cookies, ['cookieSiteId'], Table::SITES, ['id'], 'CASCADE');
        $this->addForeignKey(null, $cookies, ['id'], Table::ELEMENTS, ['id'], 'CASCADE');

        $content = SiteData::CONTENT_TABLE;
        $this->archiveTableIfExists($content);
        $columns = ['id' => $this->primaryKey(), 'siteId' => $this->integer()];
        foreach (SiteData::defaults() as $attribute => $default) {
            $columns[$attribute] = match ($attribute) {
                'triggerIcon' => $this->integer(),
                'popupLayout', 'popupPosition', 'preferencesLayout', 'preferencesPosition', 'triggerPosition'
                    => $this->string(255)->defaultValue($default),
                default => $this->text()->null(),
            };
        }
        $this->createTable($content, $columns);
        $this->createIndex(null, $content, ['siteId'], true);
        $this->addForeignKey(null, $content, ['siteId'], Table::SITES, ['id'], 'CASCADE');
    }

    public function dropTables(): void
    {
        $this->dropTableIfExists(SiteData::TRACKED_TABLE);
        $this->dropTableIfExists('{{%forty_cookies_enabled}}');
        $this->dropTableIfExists(SiteData::CONTENT_TABLE);
    }

    public function dropProjectConfig(): void
    {
        Craft::$app->getProjectConfig()->remove('forty-cookieconsent');
    }
}
