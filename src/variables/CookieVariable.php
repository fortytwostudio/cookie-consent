<?php
namespace fortytwostudio\cookieconsent\variables;

use fortytwostudio\cookieconsent\CookieConsent;
use fortytwostudio\cookieconsent\elements\CookieElement;
use fortytwostudio\cookieconsent\elements\db\CookieQuery;
use craft\db\Query;

use Craft;
use craft\helpers\UrlHelper;
use yii\di\ServiceLocator;

class CookieVariable extends ServiceLocator
{
	public function getAllCookies(): array
	{
		$query = CookieElement::find()
			->all();

		return $query;

	}

	public function getCookiesByType(string $type): array
	{
		$query = CookieElement::find()
			->type($type)
			->all();

		return $query;
	}

	public function getSettings()
	{
		return CookieConsent::$settings;
	}

	public function getLogs()
	{
		$row = (new Query())
			->select(['*'])
			->from('{{%forty_cookies_tracked}}')
			->one();

		$options = [];

		if ($row) {
			$options = [
				"accepted" => $row["accepted"],
				"rejected" => $row["rejected"],
				"total" => $row["accepted"] + $row["rejected"],
			];
		};

		return $options;
	}

	public function getContent()
	{
		$row = (new Query())
			->select(['*'])
			->from('{{%forty_cookies_content}}')
			->one();

		return $row;
	}

}
