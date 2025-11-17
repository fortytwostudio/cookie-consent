<?php
namespace fortytwostudio\cookieconsent;

/* Craft */
use Craft;
use craft\base\Plugin;
use craft\elements\Entry as EntryElement;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\PluginEvent;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use craft\services\Plugins;
use craft\web\View;
use craft\web\UrlManager;

use craft\events\DefineFieldLayoutFieldsEvent;
use craft\models\FieldLayout;

/* Plugin */
use fortytwostudio\cookieconsent\assets\CookieAssets;
use fortytwostudio\cookieconsent\models\SettingsModel;
use fortytwostudio\cookieconsent\elements\CookieElement;
use fortytwostudio\cookieconsent\elements\LogElement;
use fortytwostudio\cookieconsent\records\LogRecord;
use fortytwostudio\cookieconsent\twigextensions\CookieExtension;
use fortytwostudio\cookieconsent\variables\CookieVariable;

// Website Documentation
use fortytwostudio\websitedocumentation\WebsiteDocumentation;

/* Yii */
use yii\base\Application;
use yii\base\Event;
use yii\web\User;
use yii\web\View as YiiView;

/* Logging */
use craft\log\MonologTarget;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LogLevel;

/**
 * @author    Forty Two Studio
 * @package   StagingWarning
 * @since     1.0.0
 *
 */
class CookieConsent extends Plugin
{
	public static string $plugin;
	public ?string $name = "Cookie Consent";
	public static ?CookieVariable $cookieVariable;
	public static ?SettingsModel $settings;

	public function init() : void {
		$this->hasCpSection = true;
		$this->hasCpSettings = true;
		self::$settings = $this->getSettings();
		self::$cookieVariable = new CookieVariable();

		// Create Custom Alias
		Craft::setAlias('@fortycookieconsent', __DIR__);

		parent::init();

		$this->setRoutes();
		$this->registerElement();
		$this->registerFields();
		$this->_setEvents();
		$this->_registerTwigExtensions();
	}

	// Public Functions
	// _______________________________________

	/**
	 * @return mixed
	 */
	public function getSettingsResponse(): mixed {
		return Craft::$app->controller->redirect(UrlHelper::cpUrl("42cookie-consent/settings/general"));
	}

	// Rename the Control Panel Item & Add Sub Menu
	public function getCpNavItem(): ?array
	{
		// Create main navigation item
		$item = [
			"label" => "Cookie Consent",
			"url" =>  "42cookie-consent",
			"icon" => "@fortycookieconsent/icons/cookie.svg",
		];

		// Get Settings
		$settings = $this->getSettings();

		// Add Sub Navigation items
		$item = array_merge($item, [
			"subnav" => [
				"dashboard" => [
					"label" => "Dashboard",
					"url" => "42cookie-consent/dashboard",
				],
				"cookies" => [
					"label" => "Cookies",
					"url" => "42cookie-consent/cookies",
				],
				"content" => [
					"label" => "Content",
					"url" => "42cookie-consent/content",
				],
				"guide" => [
					"label" => "Guide",
					"url" => "42cookie-consent/guide",
				],
			],
		]);

		// If changes are allowed, we can show the settings. These will be saved in the project config
		$editableSettings = true;
		$general = Craft::$app->getConfig()->getGeneral();
		if (!$general->allowAdminChanges) {
			$editableSettings = false;
		}

		if ($editableSettings) {
			$item["subnav"]["settings"] = [
				"label" => "Settings",
				"url" => "42cookie-consent/settings"
			];
		}

		return $item;
	}

	// Protected Functions
	// _______________________________________

	protected function setRoutes() : void {
		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_CP_URL_RULES,
			function (RegisterUrlRulesEvent $event) {

				$routes = [
					"42cookie-consent/settings" => "forty-cookieconsent/settings/general", // Controller
					"42cookie-consent/settings/general" => "forty-cookieconsent/settings/general", // Controller
					"42cookie-consent/settings/exclude" => "forty-cookieconsent/settings/exclude", // Controller
					"42cookie-consent/settings/google" => "forty-cookieconsent/settings/google", // Controller
					"42cookie-consent/settings/colours" => "forty-cookieconsent/settings/colours", // Controller
					"42cookie-consent" => [
						"template" => "forty-cookieconsent/dashboard", // Template
					],
					"42cookie-consent/dashboard" => [
						"template" => "forty-cookieconsent/dashboard", // Template
					],
					"42cookie-consent/cookies" => "forty-cookieconsent/cookies/index", // Controller
					"42cookie-consent/cookies/<elementId:\d+>" => "elements/edit", // Craft
					"42cookie-consent/guide" => [
						"template" => "forty-cookieconsent/guide", // Template
					],
					"42cookie-consent/content" => [
						"template" => "forty-cookieconsent/content", // Template
					],
				];

				$event->rules = array_merge($event->rules, $routes);
			}
		);
	}

	protected function createSettingsModel(): SettingsModel {
		return new SettingsModel();
	}

	protected function registerElement() {
		Event::on(
			Elements::class,
			Elements::EVENT_REGISTER_ELEMENT_TYPES,
			function(RegisterComponentTypesEvent $event) {
				$event->types[] = CookieElement::class;
				$event->types[] = LogElement::class;
			}
		);
	}

	protected function registerFields() {
		Event::on(
			FieldLayout::class,
			FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
			static function(DefineFieldLayoutFieldsEvent $event): void {
				CookieElement::defineNativeFields($event);
			}
		);
	}

	/**
	 * @return string|null
	 * @throws \Twig\Error\LoaderError
	 * @throws \Twig\Error\RuntimeError
	 * @throws \Twig\Error\SyntaxError
	 * @throws \yii\base\Exception
	 */
	protected function settingsHtml(): ?string {
		return \Craft::$app->getView()->renderTemplate(
			"42cookie-consent/settings",
			[ "settings" => $this->getSettings() ]
		);
	}

	// Private Functions
	// _______________________________________

	private function _setEvents() : void {
		/** @var $settings SettingsModel */
		$settings = $this->getSettings();
		$self = $this;

		if(
			Craft::$app->getRequest()->getIsSiteRequest() &&
			$settings->isEnabled()
		) {
			Event::on(
				View::class,
				View::EVENT_BEGIN_BODY,
				function(Event $event) use ($settings, $self) {
					$view = Craft::$app->getView();

					// Check the entry
					$element = Craft::$app->getUrlManager()->getMatchedElement();

					if ($element instanceof EntryElement) {
						if (in_array((string) $element->id, $settings->excludeIds, true)) {
							return;
						}
					}

					// Check we've got website documentation plugin installed
					$websitedocs = Craft::$app->plugins->getPlugin('websitedocumentation');
					$websitedocsUrl = null;

					if ($websitedocs) {
						$handle = Craft::$app->sites->currentSite->handle ?? "default";
						$config = WebsiteDocumentation::customConfig();
						$websitedocsUrl = WebsiteDocumentation::$plugin->getDocUrl($config, $handle);
					}

					if ($websitedocsUrl) {
						$currentUrl = Craft::$app->getRequest()->getAbsoluteUrl();
						if (strpos($currentUrl, $websitedocsUrl) !== false) {
							return;
						}
					}

					// Switch to the plugin templates
					$view->setTemplateMode(View::TEMPLATE_MODE_CP);

					// Add Variables
					$variables = $view->renderTemplate('forty-cookieconsent/_variables.twig');

					//minify
					$variables = preg_replace('/\s+/', ' ', $variables);
					$variables = str_replace([' >', '< ', ' ,'], ['>', '<', ','], $variables);

					$view->registerJs($variables, View::POS_HEAD);

					// Load the JS file
					$bundle = Craft::$app->getView()->registerAssetBundle(CookieAssets::class);

					$jsFile = $bundle->baseUrl . '/dist/js/main.js';

					$view->registerJsFile(
						$jsFile,
						[
							'position' => YiiView::POS_END,
							'defer' => true,
							'type' => 'module',
						]
					);

					// Switch back to front end
					$view->setTemplateMode(View::TEMPLATE_MODE_SITE);
				}
			);
		}
	}

	/**
	 * Registers Twig extensions.
	 */
	private function _registerTwigExtensions()
	{
		Craft::$app->view->registerTwigExtension(new CookieExtension());
	}
}
