<?php
namespace fortytwostudio\cookieconsent\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->createTables();
        $this->addForeignKeys();

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropProjectConfig();
        $this->dropForeignKeys();
        $this->dropTables();

        return true;
    }

    public function createTables(): void
    {
		// User Clicks
		$this->archiveTableIfExists('{{%forty_cookies_tracked}}');
		$this->createTable('{{%forty_cookies_tracked}}', [
			'id' => $this->primaryKey(),
			'accepted' => $this->integer()->notNull()->defaultValue(0),
			'rejected' => $this->integer()->notNull()->defaultValue(0),
		]);

		// Cookies
        $this->archiveTableIfExists('{{%forty_cookies_enabled}}');
        $this->createTable('{{%forty_cookies_enabled}}', [
            'id' => $this->primaryKey(),
			'type' => $this->string(255),
			'cookieId' => $this->string(255),
			'domain' => $this->string(255),
			'duration' => $this->string(255),
			'description' => $this->text(),
        ]);

		// Content
		$contentTable = '{{%forty_cookies_content}}';
		$this->archiveTableIfExists($contentTable);

		// Create the New Table
		$this->createTable($contentTable, [
			'id' => $this->primaryKey(),
			'popupTitle' => $this->text()->null(),
			'popupDescription' => $this->text()->null(),
			'popupFooter' => $this->text()->null(),
			'popupLayout' => $this->string(255)->defaultValue('box inline'),
			'popupPosition' => $this->string(255)->defaultValue('bottom right'),
			'preferencesTitle' => $this->text()->null(),
			'preferencesDescription' => $this->text()->null(),
			'requiredCookies' => $this->text()->null(),
			'functionalCookies' => $this->text()->null(),
			'analyticsCookies' => $this->text()->null(),
			'performanceCookies' => $this->text()->null(),
			'advertisingCookies' => $this->text()->null(),
			'securityCookies' => $this->text()->null(),
			'preferencesLayout' => $this->string(255)->defaultValue('bar'),
			'preferencesPosition' => $this->string(255)->defaultValue('right'),
			'triggerIcon' => $this->integer(),
			'triggerPosition' => $this->string(255)->defaultValue('left'),
		]);

		// Update Values
		$this->insert($contentTable, [
			'popupTitle' => 'We value your privacy',
			'popupDescription' => "We use cookies to enhance your browsing experience, serve personalised ads or content, and analyse our traffic. By clicking 'Accept', you consent to our use of cookies.",
			'popupFooter' => '<a href="/privacy-policy">Privacy Policy</a>',
			'preferencesTitle' => 'Customise Consent Preferences',
			'preferencesDescription' => 'A cookie is a small text file sent to your browser and stored on your device by a website you visit. Cookies may save information about the pages you visit and the devices you use, which in return can give us more insight about how you use our website so we can improve its usability and deliver more relevant content.',
			'requiredCookies' => 'Necessary cookies are required to enable the basic features of this site, such as providing secure log-in or adjusting your consent preferences. These cookies do not store any personally identifiable data.',
			'functionalCookies' => 'Functional cookies help perform certain functionalities like sharing the content of the website on social media platforms, collecting feedback, and other third-party features.',
			'analyticsCookies' => 'Analytical cookies are used to understand how visitors interact with the website. These cookies help provide information on metrics such as the number of visitors, bounce rate, traffic source, etc.',
			'performanceCookies' => 'Performance cookies are used to understand and analyse the key performance indexes of the website which helps in delivering a better user experience for the visitors.',
			'advertisingCookies' => 'Advertisement cookies are used to provide visitors with customised advertisements based on the pages you visited previously and to analyse the effectiveness of the ad campaigns.',
			'securityCookies' => 'Cookies used for security authenticate users, prevent fraud, and protect users as they interact with a service.',
		]);
    }

    public function addForeignKeys(): void
    {
        $this->addForeignKey(null, '{{%forty_cookies_tracked}}', ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
		$this->addForeignKey(null, '{{%forty_cookies_enabled}}', ['id'], Table::ELEMENTS, ['id'], 'CASCADE', null);
    }

    public function dropTables(): void
    {
        $this->dropTableIfExists('{{%forty_cookies_tracked}}');
        $this->dropTableIfExists('{{%forty_cookies_enabled}}');
    }

    public function dropForeignKeys(): void
    {
        if ($this->db->tableExists('{{%forty_cookies_tracked}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%forty_cookies_tracked}}', $this);
        }

        if ($this->db->tableExists('{{%forty_cookies_enabled}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%forty_cookies_enabled}}', $this);
        }
    }

    public function dropProjectConfig(): void
    {
        Craft::$app->getProjectConfig()->remove('forty-cookieconsent');
    }
}
