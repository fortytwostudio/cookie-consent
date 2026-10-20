<?php
namespace fortytwostudio\cookieconsent\controllers;

use Craft;
use craft\elements\Asset;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use fortytwostudio\cookieconsent\services\SiteContext;
use fortytwostudio\cookieconsent\services\SiteData;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class ContentController extends Controller
{
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $this->requireCpRequest();
        $this->requirePermission('accessPlugin-forty-cookieconsent');
        return true;
    }

    public function actionIndex(): Response
    {
        $site = SiteContext::forCpRequest();
        return $this->renderTemplate('forty-cookieconsent/content/index', [
            'cookieSite' => $site,
            'settings' => SiteData::getContent($site->id),
        ]);
    }

    public function actionUpdateContent(): Response
    {
        $this->requirePostRequest();
        $site = SiteContext::forCpRequest();
        $data = array_intersect_key($this->request->getBodyParams(), SiteData::defaults());
        foreach ($data as $attribute => &$value) {
            if ($attribute === 'triggerIcon') {
                $value = is_array($value) ? reset($value) : $value;
                if ($value === '' || $value === null || $value === false) {
                    $value = null;
                } elseif (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false) {
                    $value = (int)$value;
                    $asset = Asset::find()->id($value)->siteId($site->id)->one();
                    if (!$asset || strtolower($asset->getExtension()) !== 'svg') {
                        throw new BadRequestHttpException('The cookie icon must be an SVG asset.');
                    }
                } else {
                    throw new BadRequestHttpException('Invalid cookie icon.');
                }
            } elseif (!is_string($value)) {
                throw new BadRequestHttpException('Invalid cookie content.');
            }
        }
        unset($value);

        SiteData::saveContent($site->id, $data);
        Craft::$app->getSession()->setNotice('Cookie consent content saved for ' . $site->name . '.');
        return $this->redirect(UrlHelper::cpUrl('42cookie-consent/content', ['site' => $site->handle]));
    }
}
