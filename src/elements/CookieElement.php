<?php

namespace fortytwostudio\cookieconsent\elements;

use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\models\FieldLayout;
use craft\helpers\UrlHelper;

use fortytwostudio\cookieconsent\CookieConsent;
use fortytwostudio\cookieconsent\elements\db\CookieQuery;
use fortytwostudio\cookieconsent\records\CookieRecord;

use fortytwostudio\cookieconsent\elements\fieldlayoutelements\TypeField;
use fortytwostudio\cookieconsent\elements\fieldlayoutelements\CookieIdField;
use fortytwostudio\cookieconsent\elements\fieldlayoutelements\DomainField;
use fortytwostudio\cookieconsent\elements\fieldlayoutelements\DurationField;
use fortytwostudio\cookieconsent\elements\fieldlayoutelements\DescriptionField;

use yii\db\Expression;
use yii\web\Response;

class CookieElement extends Element
{
	/**
	 * @var ?int The type of cookie this is.
	 */
	public ?string $type = null;

	/**
	 * @var ?int The cookie ID
	 */
	public ?string $cookieId = null;

	/**
	 * @var ?int The cookie domain
	 */
	public ?string $domain = null;

	/**
	 * @var ?int The duration of the cookie
	 */
	public ?string $duration = null;

	/**
	 * @var ?int The cookies description
	 */
	public ?string $description = null;

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
	public static function isLocalized(): bool
	{
		return false;
	}

	/**
	 * @inheritdoc
	 */
	public static function hasStatuses(): bool
	{
		return false;
	}

	public function canSave(User $user): bool
	{
		return true;
	}

	public function canDelete(User $user): bool
	{
		return true;
	}

	public function canView(User $user): bool
	{
		return true;
	}

	public static function canCreateAnother(): bool
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public static function find(): CookieQuery
	{
		return new CookieQuery(static::class);
	}

	protected static function defineSources(string $context = null): array
	{
		return [
			[
				"key" => "*",
				"label" => "Cookies",
				"criteria" => [],
				"defaultSort" => [
					"cookieId",
					"asc"
				],
			],
		];
	}

	public function getUiLabel(): string
	{
		return $this->cookieId ?? "Untitled";
	}

	protected static function defineTableAttributes(): array
	{
		return [
			"type" => Craft::t("app", "Type"),
			"description" => Craft::t("app", "Description"),
		];
	}

	protected static function defineSortOptions(): array
	{
		return [
			"cookieId" => Craft::t("app", "Cookie ID"),
		];
	}

	/**
	 * @inheritdoc
	 */
	public function afterSave(bool $isNew): void
	{
		// Request
		$request = Craft::$app->getRequest();

		$this->type = $request->getBodyParam("type");
		$this->cookieId = $request->getBodyParam("cookieId");
		$this->domain = $request->getBodyParam("domain");
		$this->duration = $request->getBodyParam("duration");
		$this->description = $request->getBodyParam("description");

		// Add Data to database
		if (!$this->propagating) {
			if (!$isNew) {
				$record = CookieRecord::findOne($this->id);

				if (!$record) {
					throw new InvalidConfigException("Invalid ID: $this->id");
				}

				$record->type = $this->type;
				$record->cookieId = $this->cookieId;
				$record->domain = $this->domain;
				$record->duration = $this->duration;
				$record->description = $this->description;

				$record->save(false);
			} else {
				$record = new CookieRecord();
				$record->id = (int)$this->id;
				$record->type = $this->type;
				$record->cookieId = $this->cookieId;
				$record->domain = $this->domain;
				$record->duration = $this->duration;
				$record->description = $this->description;

				$record->save(false);
			}
		};

		parent::afterSave($isNew);
	}

	/**
	 * @inheritdoc
	 */
	public function afterDelete(): void
	{
		$record = CookieRecord::findOne($this->id);

		if ($record) {
			$record->delete();
		}

		parent::afterDelete();
	}

	/**
	 * @inheritdoc
	 */
	protected function cpEditUrl(): ?string
	{
		$path = UrlHelper::cpUrl("42cookie-consent/cookies/{$this->id}");

		return $path;
	}

	public static function defineNativeFields(DefineFieldLayoutFieldsEvent $event): void
	{
		/** @var FieldLayout $fieldLayout */
		$fieldLayout = $event->sender;

		if ($fieldLayout->type === self::class) {
			$event->fields[] = TypeField::class;
			$event->fields[] = CookieIdField::class;
			$event->fields[] = DomainField::class;
			$event->fields[] = DurationField::class;
			$event->fields[] = DescriptionField::class;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getPostEditUrl(): ?string
	{
		return UrlHelper::cpUrl('42cookie-consent/cookies');
	}

	public function __toString(): string
	{
		if ($this->cookieId) {
			return $this->cookieId;
		}

		return parent::__toString();
	}

	public function getFieldLayout(): ?FieldLayout
	{
		$layout = new FieldLayout([
			'type' => $this::class,
		]);

		return $layout;
	}

	/**
	 * @inheritdoc
	 */
	public function metaFieldsHtml(bool $static): string
	{
		$fields = [];

		return implode("\n", $fields);
	}
}
