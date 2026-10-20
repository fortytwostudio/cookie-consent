<?php
namespace fortytwostudio\cookieconsent\models;

use craft\base\Model;

class SettingsModel extends Model {
	public bool $isEnabled = true;
	public bool $sendGtag = true;
	public string $siteDomain = '';

	/** Site overrides keyed by stable Craft site UID, not environment-specific IDs. */
	public array $sites = [];

	// Entries
	public array $excludeIds = [];

	// Text
	// ______________________________________

	public bool $consentEqualWeightButtons = true;
	public bool $consentFlipButtons = false;
	public bool $preferencesEqualWeightButtons = true;
	public bool $preferencesFlipButtons = false;

	// Popup Colours
	// ______________________________________

	public string $popupBackgroundColour = "FFFFFF";
	public string $popupTextColour = "000000";
	public string $buttonBackground = "30363c";
	public string $hoverButtonBackground = "30363c";
	public string $buttonText = "FFFFFF";
	public string $hoverButtonText = "FFFFFF";
	public string $manageBackground = "eaeff2";
	public string $hoverManageBackground = "eaeff2";
	public string $manageText = "2c2f31";
	public string $hoverManageText = "2c2f31";
	public string $footerBackground = "eaeff2";
	public string $footerText = "5e6266";
	public string $hoverFooterText = "5e6266";

	// Modal Colours
	// ______________________________________

	public string $toggleBackground = "f0f4f7";
	public string $hoverToggleBackground = "e9eff4";
	public string $toggleText = "30363c";
	public string $hoverToggleText = "30363c";

	// Trigger Colours
	// ______________________________________

	public string $triggerBackground = "000000";
	public string $triggerFill = "FFFFFF";
	public string $triggerPosition = "left";

	// Functions
	// ______________________________________

	public function isEnabled(): bool {
		return $this->isEnabled;
	}

	public function forSite(string $siteUid, bool $inheritLegacySettings = true): self
	{
		$settings = $inheritLegacySettings ? clone $this : new self();
		$overrides = $this->sites[$siteUid] ?? [];
		$allowed = array_flip(self::siteAttributes());
		$overrides = is_array($overrides) ? array_intersect_key($overrides, $allowed) : [];
		if (array_key_exists('excludeIds', $overrides)) {
			$overrides['excludeIds'] = self::normalizeExcludeIds($overrides['excludeIds']);
		}
		$settings->setAttributes($overrides, false);
		$settings->sites = [];
		$settings->excludeIds = self::normalizeExcludeIds($settings->excludeIds);
		return $settings;
	}

	public static function siteAttributes(): array
	{
		return array_values(array_diff((new self())->attributes(), ['sites']));
	}

	public static function normalizeExcludeIds(mixed $ids): array
	{
		return is_array($ids) ? array_values(array_unique(array_filter(
			array_map('intval', $ids), static fn(int $id): bool => $id > 0,
		))) : [];
	}

	public function init(): void
    {
        parent::init();

		$this->excludeIds = self::normalizeExcludeIds($this->excludeIds);
    }

	protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['excludeIds'], 'each', 'rule' => ['integer']];
		$rules[] = [['siteDomain'], 'string', 'max' => 255];
		$rules[] = [['isEnabled', 'sendGtag', 'consentEqualWeightButtons', 'consentFlipButtons', 'preferencesEqualWeightButtons', 'preferencesFlipButtons'], 'boolean'];
		$colourFields = array_diff(self::siteAttributes(), [
			'isEnabled', 'sendGtag', 'siteDomain', 'excludeIds', 'triggerPosition',
			'consentEqualWeightButtons', 'consentFlipButtons', 'preferencesEqualWeightButtons', 'preferencesFlipButtons',
		]);
		$rules[] = [$colourFields, 'match', 'pattern' => '/^[0-9a-fA-F]{6}$/'];
		$rules[] = [['sites'], 'safe'];
        return $rules;
    }
}
