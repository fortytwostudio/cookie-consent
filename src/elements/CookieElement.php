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
use yii\base\InvalidConfigException;
use yii\web\Response;

class CookieElement extends Element
{
	/** The one site that owns this cookie definition. */
	public ?int $cookieSiteId = null;

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
		return true;
	}

	public function getSupportedSites(): array
	{
		return [
			$this->cookieSiteId ?? $this->siteId ?? Craft::$app->getSites()->getPrimarySite()->id,
		];
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
		return $this->canAccessSite($user);
	}

	public function canDelete(User $user): bool
	{
		return $this->canAccessSite($user);
	}

	public function canView(User $user): bool
	{
		return $this->canAccessSite($user);
	}

	private function canAccessSite(User $user): bool
	{
		$siteId = $this->cookieSiteId ?? $this->siteId;
		$site = $siteId ? Craft::$app->getSites()->getSiteById($siteId) : null;

		return $site !== null
			&& $user->can('accessPlugin-forty-cookieconsent')
			&& (!Craft::$app->getIsMultiSite() || $user->can('editSite:' . $site->uid));
	}

	public static function canCreateAnother(): bool
	{
		return true;
	}

	protected static function defineActions(string $source): array
	{
		return [\craft\elements\actions\Restore::class];
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
			"domain" => Craft::t("app", "Domain"),
		];
	}

	protected static function defineSortOptions(): array
	{
		return [
			"cookieId" => Craft::t("app", "Cookie ID"),
		];
	}

	protected function defineRules(): array
	{
		$rules = parent::defineRules();
		$attributes = ['type', 'cookieId', 'domain', 'duration', 'description'];
		$rules[] = [$attributes, 'required', 'on' => [self::SCENARIO_DEFAULT, self::SCENARIO_LIVE]];
		$rules[] = [['type', 'cookieId', 'domain', 'duration'], 'string', 'max' => 255];
		$rules[] = [['description'], 'string'];
		$rules[] = [['type'], 'in', 'range' => ['necessary', 'functionality', 'analytics', 'performance', 'advertisement', 'security']];
		// Ownership is assigned by the controller, never through mass assignment.
		$rules[] = [['!cookieSiteId'], 'integer', 'min' => 1];
		return $rules;
	}

	/**
	 * @inheritdoc
	 */
	public function beforeSave(bool $isNew): bool
	{
		$this->cookieSiteId ??= $this->siteId;

		if (
			!$this->cookieSiteId ||
			!Craft::$app->getSites()->getSiteById($this->cookieSiteId) ||
			$this->siteId !== $this->cookieSiteId
		) {
			$this->addError('cookieSiteId', 'A cookie must belong to its selected site.');
			return false;
		}

		// A posted site ID must never move an existing cookie to another site.
		if (!$isNew) {
			$record = CookieRecord::findOne($this->id);
			if ($record && (int)$record->cookieSiteId !== $this->cookieSiteId) {
				$this->addError('cookieSiteId', 'The site of an existing cookie cannot be changed.');
				return false;
			}
		}

		return parent::beforeSave($isNew);
	}

	public function afterSave(bool $isNew): void
	{
		// Craft populates validated native attributes before saving the element.
		if (!$this->propagating) {
			$record = $isNew ? new CookieRecord() : CookieRecord::findOne($this->id);
			if (!$record) {
				throw new InvalidConfigException("Invalid cookie ID: $this->id");
			}

			$record->id = (int)$this->id;
			foreach (['cookieSiteId', 'type', 'cookieId', 'domain', 'duration', 'description'] as $attribute) {
				$record->{$attribute} = $this->{$attribute};
			}
			if (!$record->save(false)) {
				throw new InvalidConfigException("Could not save cookie ID: $this->id");
			}
		}

		parent::afterSave($isNew);
	}

	// Native records survive Craft soft deletion so restoring a cookie retains
	// its fields. The element foreign key removes them on a hard delete.

	/**
	 * @inheritdoc
	 */
	protected function cpEditUrl(): ?string
	{
		$path = UrlHelper::cpUrl("42cookie-consent/cookies/{$this->id}", [
			'site' => $this->getSite()->handle,
		]);

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
		return UrlHelper::cpUrl('42cookie-consent/cookies', [
			'site' => $this->getSite()->handle,
		]);
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

