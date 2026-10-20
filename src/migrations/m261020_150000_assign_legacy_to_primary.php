<?php

namespace fortytwostudio\cookieconsent\migrations;

use Craft;
use craft\db\Migration;
use fortytwostudio\cookieconsent\services\KnownCookies;
use fortytwostudio\cookieconsent\services\PrimarySiteUpgrade;
use fortytwostudio\cookieconsent\services\SiteData;

/** Also applies primary-site ownership to projects that already ran 3.13.0. */
class m261020_150000_assign_legacy_to_primary extends Migration
{
    public function safeUp(): bool
    {
        PrimarySiteUpgrade::run();
        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            SiteData::initializeSite($site->id);
            KnownCookies::seedCraftDefaults($site->id);
        }
        return true;
    }

    public function safeDown(): bool
    {
        echo "Restore the pre-upgrade database and matching code to revert primary-site attribution.\n";
        return false;
    }
}
