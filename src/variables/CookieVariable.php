<?php
namespace fortytwostudio\cookieconsent\variables;

use fortytwostudio\cookieconsent\CookieConsent;
use fortytwostudio\cookieconsent\elements\CookieElement;
use fortytwostudio\cookieconsent\elements\db\CookieQuery;

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
}
