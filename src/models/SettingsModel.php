<?php
namespace fortytwostudio\cookieconsent\models;

use craft\base\Model;

class SettingsModel extends Model {
	public bool $isEnabled = true;
	public bool $sendGtag = true;

	// Text
	// ______________________________________

	public string $title = "We value your privacy";
	public string $description = "We use cookies to enhance your browsing experience, serve personalised ads or content, and analyse our traffic. By clicking 'Accept', you consent to our use of cookies.";
	public string $footer = "<a href=\"/privacy-policy\">Privacy Policy</a>";

	public string $consentLayout = 'box inline';
	public string $consentPosition = 'bottom right';
	public bool $consentEqualWeightButtons = true;
	public bool $consentFlipButtons = false;

	public string $internalTitle = "Customise Consent Preferences";
	public string $internalDescription = "A cookie is a small text file sent to your browser and stored on your device by a website you visit. Cookies may save information about the pages you visit and the devices you use, which in return can give us more insight about how you use our website so we can improve its usability and deliver more relevant content.";

	public string $requiredDescription = "Necessary cookies are required to enable the basic features of this site, such as providing secure log-in or adjusting your consent preferences. These cookies do not store any personally identifiable data.";

	public string $functionalDescription = "Functional cookies help perform certain functionalities like sharing the content of the website on social media platforms, collecting feedback, and other third-party features.";

	public string $analyticsDescription = "Analytical cookies are used to understand how visitors interact with the website. These cookies help provide information on metrics such as the number of visitors, bounce rate, traffic source, etc.";

	public string $performanceDescription = "Performance cookies are used to understand and analyse the key performance indexes of the website which helps in delivering a better user experience for the visitors.";

	public string $advertisementDescription = "Advertisement cookies are used to provide visitors with customised advertisements based on the pages you visited previously and to analyse the effectiveness of the ad campaigns.";

	public string $securityDescription = "Cookies used for security authenticate users, prevent fraud, and protect users as they interact with a service.";

	public string $moreTitle = "More information";
	public string $moreDescription = "For any query in relation to my policy on cookies and your choices, please <a class=\"cc__link\" href=\"/contact\">contact us</a>.";

	public string $preferencesLayout = "bar";
	public string $preferencesPosition = "right";
	public bool $preferencesEqualWeightButtons = true;
	public bool $preferencesFlipButtons = false;
	public ?string $triggerIcon = null;

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
