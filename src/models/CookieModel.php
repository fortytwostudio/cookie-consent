<?php
namespace fortytwostudio\cookieconsent\models;

use fortytwostudio\cookieconsent\CookieConsent;
use fortytwostudio\cookieconsent\elements\CookieElement;

use Craft;
use craft\base\Model;

class CookieModel extends Model
{
	// Properties
	// =========================================================================

	public ?string $type = null;
	public ?string $cookieId = null;
	public ?string $domain = null;
	public ?string $duration = null;
	public ?string $description = null;


	// Protected Methods
	// =========================================================================

	protected function defineRules(): array
	{
		$rules = parent::defineRules();

		$rules[] = [
			[
				'type',
				'cookieId',
				'domain',
				'duration',
				'description'
			],
		'required'];

		return $rules;
	}
}
