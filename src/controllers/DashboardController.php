<?php
namespace fortytwostudio\cookieconsent\controllers;

use craft\web\Controller;
use fortytwostudio\cookieconsent\services\SiteContext;
use yii\web\Response;

class DashboardController extends Controller
{
    public function actionIndex(): Response
    {
        $this->requireCpRequest();
        $this->requirePermission('accessPlugin-forty-cookieconsent');
        return $this->renderTemplate('forty-cookieconsent/dashboard/index', [
            'cookieSite' => SiteContext::forCpRequest(),
        ]);
    }
}
