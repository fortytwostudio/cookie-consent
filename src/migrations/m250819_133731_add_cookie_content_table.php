<?php

namespace fortytwostudio\cookieconsent\migrations;

use Craft;
use craft\db\Migration;
use craft\services\ProjectConfig;

/**
 * m250819_133730_add_cookie_content_table migration.
 */
class m250819_133731_add_cookie_content_table extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $tableName = '{{%forty_cookies_content}}';

		if (!$this->db->tableExists($tableName)) {

			// Create the New Table
			$this->createTable($tableName, [
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
			$this->insert($tableName, [
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


			// Get the Data from the Project Config
			$pc = Craft::$app->getProjectConfig();
			$pluginHandle = 'forty-cookieconsent';

			$settings = $pc->get(ProjectConfig::PATH_PLUGINS . ".$pluginHandle.settings") ?? [];

			// If settings exist
			if (!empty($settings) && isset($settings["title"])) {
				$this->update($tableName, [
					'popupTitle' => $settings["title"],
					'popupDescription' => $settings["description"],
					'popupFooter' => $settings["footer"],
					'preferencesTitle' => $settings["internalTitle"],
					'preferencesDescription' => $settings["internalDescription"],
					'requiredCookies' => $settings["requiredDescription"],
					'functionalCookies' => $settings["functionalDescription"],
					'analyticsCookies' => $settings["analyticsDescription"],
					'performanceCookies' => $settings["performanceDescription"],
					'advertisingCookies' => $settings["advertisementDescription"],
					'securityCookies' => $settings["securityDescription"],
				]);

				unset($settings['title'], $settings['description'], $settings['footer'], $settings['internalTitle'], $settings['internalDescription'], $settings['requiredDescription'], $settings['functionalDescription'], $settings['analyticsDescription'], $settings['performanceDescription'], $settings['advertisementDescription'], $settings['securityDescription']);

				$pc->set(".$pluginHandle.settings", $settings, 'Move popup settings from Project Config to DB table');
			}
		};

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m250819_133731_add_cookie_content_table cannot be reverted.\n";
        return false;
    }
}
