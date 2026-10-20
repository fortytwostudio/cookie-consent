<?php

namespace fortytwostudio\cookieconsent\migrations;

use Craft;
use craft\db\Migration;
use fortytwostudio\cookieconsent\services\KnownCookies;

class m260720_150000_seed_known_craft_cookies extends Migration
{
    public function safeUp(): bool
    {
        // Older installations may still have this migration pending. The later
        // multisite migration seeds every site after adding ownership columns.
        if ($this->db->getTableSchema('{{%forty_cookies_enabled}}', true)?->getColumn('cookieSiteId')) {
            foreach (Craft::$app->getSites()->getAllSites() as $site) {
                KnownCookies::seedCraftDefaults($site->id);
            }
        }
        return true;
    }

    public function safeDown(): bool
    {
        // Defaults may have been edited or deliberately retained, so an update
        // rollback must not delete user-managed cookie elements.
        return true;
    }
}
