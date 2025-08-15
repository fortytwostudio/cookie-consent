<?php

namespace fortytwostudio\cookieconsent\elements;

use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\models\FieldLayout;
use craft\helpers\UrlHelper;

use fortytwostudio\cookieconsent\CookieConsent;
use fortytwostudio\cookieconsent\elements\db\LogQuery;
use fortytwostudio\cookieconsent\records\LogRecord;

use yii\db\Expression;
use yii\web\Response;

class LogElement extends Element
{
	/**
	 * @var ?int Accepted
	 */
	public ?int $accepted = 0;

	/**
	 * @var ?int Rejected
	 */
	public ?int $rejected = 0;

	/**
	 * @inheritdoc
	 */
	public static function displayName(): string
	{
		return Craft::t("forty-cookieconsent", "Cookie");
	}

	/**
	 * @inheritdoc
	 */
	public static function lowerDisplayName(): string
	{
		return Craft::t("forty-cookieconsent", "cookie");
	}

	/**
	 * @inheritdoc
	 */
	public static function hasTitles(): bool
	{
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public static function find(): LogQuery
	{
		return new LogQuery(static::class);
	}

	/**
	 * @inheritdoc
	 */
	public function afterSave(bool $isNew): void
	{
		// Request
		$request = Craft::$app->getRequest();

		$this->accepted = $request->getBodyParam("accepted");
		$this->rejected = $request->getBodyParam("rejected");

		// Add Data to database
		if (!$this->propagating) {
			if (!$isNew) {
				$record = LogRecord::findOne($this->id);

				if (!$record) {
					throw new InvalidConfigException("Invalid ID: $this->id");
				}

				$record->accepted = $record->accepted + $this->accepted;
				$record->rejected = $record->rejected + $this->rejected;

				$record->save(false);
			} else {
				$record = new LogRecord();
				$record->accepted = $record->accepted + $this->accepted;
				$record->rejected = $record->rejected + $this->rejected;

				$record->save(false);
			}
		};

		parent::afterSave($isNew);
	}
}
