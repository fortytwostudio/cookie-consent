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

use yii\db\Expression;

class CookiesController extends Controller
{
	// Protected Properties
	// =========================================================================

	protected array|bool|int $allowAnonymous = true;

	public function actionIndex()
	{
		$variables = [];

		$variables["elements"] = CookieElement::find()
			->all();

		return $this->renderTemplate("forty-cookieconsent/cookies/index", $variables);
	}

	/**
	 * Create Template.
	 *
	 * @return Response The rendering result
	 */
	public function actionEdit()
	{
		// Create & populate the draft
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

		$table = '{{%forty_cookies_enabled}}';
		$schema = Craft::$app->db->schema->getTableSchema($table);

		if ($schema && isset($schema->columns['id'])) {
			$idColumn = $schema->columns['id'];

			// Will be true for MySQL auto_increment or Postgres identity
			if ($idColumn->autoIncrement) {
				Craft::info("✅ {$table}.id is auto-increment", "DowleyDev");
			} else {
				Craft::warning("⚠ {$table}.id is NOT auto-increment", "DowleyDev");
			}
		} else {
			Craft::error("Table {$table} not found.", "DowleyDev");
		}

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
