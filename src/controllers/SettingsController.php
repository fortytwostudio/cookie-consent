<?php

namespace fortytwostudio\cookieconsent\controllers;

use Craft;
use craft\web\Controller;
use craft\elements\Entry;
use craft\models\Section;

use fortytwostudio\cookieconsent\CookieConsent;

use yii\web\Response;

class SettingsController extends Controller {

	public function actionGeneral() : Response {
		$settings = CookieConsent::getInstance()->getSettings();

		return $this->renderTemplate("forty-cookieconsent/settings/_general", [
			"settings" => $settings,
		]);
	}

	public function actionExclude() : Response {
		$settings = CookieConsent::getInstance()->getSettings();

		// Get all sections
		$sections = Craft::$app->entries->getAllSections();
		$sectionHandlesById = [];

		foreach ($sections as $section) {
			$sectionHandlesById[$section->id] = [
				"title" => $section->name,
				"handle" => $section->handle
			];
		}

		// Get all the entries
		$rows = Entry::find()
			->site('*')
			->uri(':notempty:')
			->select([
				'id'    => 'elements.id',
				'title' => 'elements_sites.title',
				'uri'	=> 'elements_sites.uri',
				'sectionId' => 'entries.sectionId',
			])
			->asArray()
			->all();

		$entriesBySection = [];

		foreach ($rows as $row) {
			$handle = $sectionHandlesById[$row['sectionId']]["handle"] ?? null;

			if (!$handle) {
				continue;
			}

			$title = $sectionHandlesById[$row['sectionId']]["title"] ?? null;
			$entriesBySection[$handle]["title"] = $title;

			$entriesBySection[$handle]["entries"][] = [
				'id'    => $row['id'],
				'title' => $row['title'],
			];
		}

		return $this->renderTemplate("forty-cookieconsent/settings/_exclude", [
			"settings" => $settings,
			"sections" => $entriesBySection,
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
