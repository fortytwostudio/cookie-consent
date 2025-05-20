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
			'id' => $this->integer()->notNull(),
			'accepted' => $this->integer()->notNull()->defaultValue(0),
			'rejected' => $this->integer()->notNull()->defaultValue(0),
			'PRIMARY KEY(id)',
		]);

		// Cookies
        $this->archiveTableIfExists('{{%forty_cookies_enabled}}');
        $this->createTable('{{%forty_cookies_enabled}}', [
            'id' => $this->integer()->notNull(),
			'type' => $this->string(255),
			'cookieId' => $this->string(255),
			'domain' => $this->string(255),
			'duration' => $this->string(255),
			'description' => $this->text(),
            'PRIMARY KEY(id)',
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
