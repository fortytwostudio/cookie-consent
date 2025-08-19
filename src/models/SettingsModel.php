<?php
namespace fortytwostudio\cookieconsent\models;

use craft\base\Model;

class SettingsModel extends Model {
	public bool $isEnabled = true;
	public bool $sendGtag = true;

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
}
