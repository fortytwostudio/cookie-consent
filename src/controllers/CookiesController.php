<?php
namespace fortytwostudio\cookieconsent\controllers;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use fortytwostudio\cookieconsent\elements\CookieElement;
use fortytwostudio\cookieconsent\models\CookieModel;
use fortytwostudio\cookieconsent\records\LogRecord;
use fortytwostudio\cookieconsent\services\KnownCookies;

use yii\db\Expression;
use yii\web\Response;

class CookiesController extends Controller
{
	// Protected Properties
	// =========================================================================

	protected array|bool|int $allowAnonymous = ['get-consent', 'log-consent'];

	public function actionIndex()
	{
		$variables = [];

		$variables["elements"] = CookieElement::find()
			->all();
		return $this->renderTemplate("forty-cookieconsent/cookies/index", $variables);
	}

	public function actionNew(): Response
	{
		$options = [['label' => 'Custom cookie', 'value' => 'custom']];
		foreach (KnownCookies::services() as $key => $service) {
			$options[] = ['label' => $service['label'], 'value' => $key];
		}

		return $this->renderTemplate('forty-cookieconsent/cookies/new', [
			'serviceOptions' => $options,
			'services' => KnownCookies::services(),
			'siteDomain' => KnownCookies::siteDomain(),
		]);
	}

	public function actionCreateService(): Response
	{
		$this->requirePostRequest();
		$serviceKey = (string)$this->request->getRequiredBodyParam('service');

		if ($serviceKey === 'custom') {
			$definition = [
				'type' => (string)$this->request->getRequiredBodyParam('type'),
				'cookieId' => trim((string)$this->request->getRequiredBodyParam('cookieId')),
				'domain' => trim((string)$this->request->getRequiredBodyParam('customDomain')),
				'duration' => trim((string)$this->request->getRequiredBodyParam('duration')),
				'description' => trim((string)$this->request->getRequiredBodyParam('description')),
			];

			foreach ($definition as $value) {
				if ($value === '') {
					throw new \yii\web\BadRequestHttpException('All custom cookie fields are required.');
				}
			}

			$result = KnownCookies::createMissing([$definition]);
			if ($result['added'] !== []) {
				Craft::$app->getSession()->setNotice("{$definition['cookieId']} added.");
			} else {
				Craft::$app->getSession()->setNotice("{$definition['cookieId']} was not added because that cookie ID already exists.");
			}

			return $this->redirect('42cookie-consent/cookies');
		}

		$service = KnownCookies::service($serviceKey);
		if ($service === null) {
			throw new \yii\web\BadRequestHttpException('Unknown cookie service.');
		}

		$domain = trim((string)$this->request->getRequiredBodyParam('domain'));
		if ($domain === '') {
			throw new \yii\web\BadRequestHttpException('A first-party cookie domain is required.');
		}

		$result = KnownCookies::createMissing($service['cookies'], $domain);
		$added = count($result['added']);
		$skipped = count($result['skipped']);

		if ($added > 0) {
			Craft::$app->getSession()->setNotice("{$service['label']}: {$added} cookie" . ($added === 1 ? '' : 's') . " added, {$skipped} already existed.");
		} else {
			Craft::$app->getSession()->setNotice("{$service['label']}: no cookies added; {$skipped} already existed.");
		}

		return $this->redirect('42cookie-consent/cookies');
	}

	/**
	 * Create Template.
	 *
	 * @return Response The rendering result
	 */
	public function actionEdit()
	{
		return $this->createCookieDraft();
	}

	private function createCookieDraft(): Response
	{
		$template = Craft::createObject(CookieElement::class);

		// Save it
		$template->setScenario(Element::SCENARIO_ESSENTIALS);
		$success = Craft::$app->getDrafts()->saveElementAsDraft($template, Craft::$app->getUser()->getId(), null, null, false);

		if (!$success) {
			return $this->asModelFailure($template, Craft::t('app', 'Couldn’t create {type}.', [
				'type' => CookieElement::lowerDisplayName(),
			]), 'app');
		}

		$editUrl = $template->getCpEditUrl();

		$response = $this->asModelSuccess($template, Craft::t('forty-cookieconsent', '{type} created.', [
			'type' => CookieElement::displayName(),
		]), 'cookieconsent', array_filter([
			'cpEditUrl' => $this->request->isCpRequest ? $editUrl : null,
		]));

		if (!$this->request->getAcceptsJson()) {
			$response->redirect(UrlHelper::urlWithParams($editUrl, [
				'fresh' => 1,
			]));
		}

		return $response;
	}

	/**
	 * Log consent amount
	 *
	 * @return Json The result
	 */
	public function actionGetConsent() {
		$row = (new Query())
			->select(['*'])
			->from('{{%forty_cookies_tracked}}')
			->one();

		$options = [];

		if ($row) {
			$options = [
				$row["accepted"],
				$row["rejected"],
			];
		};

		return $this->asJson([
			'success' => true,
			'data' => $options,
		]);

	}

	/**
	 * Log consent amount
	 *
	 * @return Json The result
	 */
	public function actionLogConsent() {
		$this->requirePostRequest();

		$params = Craft::$app->getRequest()->getBodyParams();

		$row = (new Query())
			->select(['*'])
			->from('{{%forty_cookies_tracked}}')
			->one();

		if ($row) {
			$id = $row['id'];
			$accepted = $params["acceptType"] == "all";
			$action = $params["consentAction"];

			$acceptedValue = (int) $row['accepted'];
			$rejectedValue = (int) $row['rejected'];

			if ($accepted) {
				$acceptedValue = $acceptedValue + 1;
			} else {
				$rejectedValue = $rejectedValue + 1;
			}

			Db::update(
				'{{%forty_cookies_tracked}}',
				[
					'accepted' => $acceptedValue,
					'rejected' => $rejectedValue,
				],
				['id' => $id]
			);
		}

		return $this->asJson([
			'success' => true,
			'data' => $params,
			'row' => $row,
		]);
	}

}
