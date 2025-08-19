<?php
namespace fortytwostudio\cookieconsent\controllers;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\db\Expression;

class ContentController extends Controller
{
	// Public Properties
	// =========================================================================

	/**
	 * Update Content
	 *
	 * @return Json The result
	 */
	public function actionUpdateContent() {
		$this->requirePostRequest();

		$params = Craft::$app->getRequest()->getBodyParams();
		$tableName = '{{%forty_cookies_content}}';

		$row = (new Query())
			->select(['*'])
			->from($tableName)
			->one();

		// Get the table schema (all column names)
		$columns = Craft::$app->db->getTableSchema($tableName)->getColumnNames();

		// Whitelist only the valid keys from $params
		$data = array_intersect_key($params, array_flip($columns));

		if ($row) {
			$id = $row['id'];

			Db::update(
				$tableName,
				$data,
				['id' => $id]
			);
		}

		// Do your logic here (set cookie, update DB, etc.)
		Craft::$app->getSession()->setFlash('notice', 'Cookies accepted!');

		// Redirect back to the referring page (refresh)
		return $this->redirect(Craft::$app->getRequest()->getReferrer() ?? '/');
	}

}
