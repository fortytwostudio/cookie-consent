<?php

namespace fortytwostudio\cookieconsent\controllers;

use Craft;
use craft\web\Controller;

use fortytwostudio\cookieconsent\CookieConsent;

use yii\web\Response;

class SettingsController extends Controller {

	public function actionGeneral() : Response {
		$settings = CookieConsent::getInstance()->getSettings();

		return $this->renderTemplate("forty-cookieconsent/settings/_general", [
			"settings" => $settings,
		]);
	}

	public function actionGoogle() : Response {
		$settings = CookieConsent::getInstance()->getSettings();

		return $this->renderTemplate("forty-cookieconsent/settings/_google", [
			"settings" => $settings,
		]);
	}

	public function actionCustomisation() : Response {
		$settings = CookieConsent::getInstance()->getSettings();

		$icon = null;

		if (!empty($settings->triggerIcon)) {
			$iconId = $settings->triggerIcon;
			$icon = Craft::$app->elements->getElementById($iconId);
		}

		return $this->renderTemplate("forty-cookieconsent/settings/_customisation", [
			"settings" => $settings,
			"icon" => $icon,
		]);
	}

	public function actionColours() : Response {
		$settings = CookieConsent::getInstance()->getSettings();

		return $this->renderTemplate("forty-cookieconsent/settings/_colours", [
			"settings" => $settings,
		]);
	}
}
