<?php

namespace fortytwostudio\cookieconsent\migrations;

use Craft;
use craft\db\Migration;

/**
 * m250520_105952_remove_url_pattern migration.
 */
class m250520_105952_remove_url_pattern extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $tableName = '{{%forty_cookies_enabled}}'; // Note: use table prefix syntax

		if ($this->db->tableExists($tableName)) {

			// Check if the column exists
			$columnName = 'urlPattern';
			$schema = Craft::$app->db->getSchema();
			$columns = $schema->getTableSchema($tableName)->getColumnNames();

			if (in_array($columnName, $columns)) {
				// Drop the column
				$this->dropColumn($tableName, $columnName);
				echo "Dropped column `$columnName` from `$tableName`.\n";
			}

			$schema = $this->db->getSchema();
			$columns = $schema->getTableSchema($tableName)->getColumnNames();
			$columnName = 'description';

			if (in_array($columnName, $columns)) {
				// Change the column data type
				$this->alterColumn($tableName, $columnName, $this->text());
				echo "Changed `$columnName` to TEXT() in `$tableName`.\n";
			}

		}

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m250520_105952_remove_url_pattern cannot be reverted.\n";
        return false;
    }
}
