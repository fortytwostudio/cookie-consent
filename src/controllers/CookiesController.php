<?php
namespace fortytwostudio\cookieconsent\controllers;

use Craft;
use craft\base\Element;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use fortytwostudio\cookieconsent\elements\CookieElement;
use fortytwostudio\cookieconsent\services\KnownCookies;
use fortytwostudio\cookieconsent\services\SiteContext;
use fortytwostudio\cookieconsent\services\SiteData;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class CookiesController extends Controller
{
    protected array|bool|int $allowAnonymous = ['get-consent', 'log-consent'];

    public function beforeAction($action): bool
    {
        if ($action->id === 'log-consent') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionIndex(): Response
    {
        $site = SiteContext::forCpRequest();

        // Craft's native index and breadcrumbs identify the site by handle.
        // Normalize old numeric URLs before the CP assets/menu are rendered.
        if ($this->request->getIsGet()) {
            $params = $this->request->getQueryParamsWithoutPath();
            if (array_key_exists('siteId', $params) || ($params['site'] ?? null) !== $site->handle) {
                unset($params['siteId']);
                $params['site'] = $site->handle;
                return $this->redirect(UrlHelper::cpUrl('42cookie-consent/cookies', $params));
            }
        }

        return $this->renderTemplate('forty-cookieconsent/cookies/index', [
            'cookieSite' => $site,
            'selectedSite' => $site,
        ]);
    }

    public function actionNew(): Response
    {
        $site = SiteContext::forCpRequest();
        $options = [['label' => 'Custom cookie', 'value' => 'custom']];
        foreach (KnownCookies::services() as $key => $service) {
            $options[] = ['label' => $service['label'], 'value' => $key];
        }

        return $this->renderTemplate('forty-cookieconsent/cookies/new', [
            'cookieSite' => $site,
            'serviceOptions' => $options,
            'services' => KnownCookies::services(),
            'siteDomain' => KnownCookies::siteDomain($site->id),
        ]);
    }

    public function actionCreateService(): Response
    {
        $this->requirePostRequest();
        $site = SiteContext::forCpRequest();
        $serviceKey = $this->requiredString('service');
        $redirect = UrlHelper::cpUrl('42cookie-consent/cookies', ['site' => $site->handle]);

        if ($serviceKey === 'custom') {
            $definition = [
                'type' => $this->requiredString('type'),
                'cookieId' => $this->requiredString('cookieId'),
                'domain' => $this->requiredString('customDomain'),
                'duration' => $this->requiredString('duration'),
                'description' => $this->requiredString('description'),
            ];

            if (!in_array($definition['type'], ['necessary', 'functionality', 'analytics', 'performance', 'advertisement', 'security'], true)) {
                throw new BadRequestHttpException('Unknown cookie type.');
            }

            $result = KnownCookies::createMissing([$definition], null, $site->id);
            if ($result['added'] !== []) {
                Craft::$app->getSession()->setNotice("{$definition['cookieId']} added to {$site->name}.");
            } else {
                Craft::$app->getSession()->setNotice("{$definition['cookieId']} already exists for {$site->name}.");
            }

            return $this->redirect($redirect);
        }

        $service = KnownCookies::service($serviceKey);
        if ($service === null) {
            throw new BadRequestHttpException('Unknown cookie service.');
        }

        $domain = $this->requiredString('domain');
        $result = KnownCookies::createMissing($service['cookies'], $domain, $site->id);
        $added = count($result['added']);
        $skipped = count($result['skipped']);

        Craft::$app->getSession()->setNotice(
            "{$service['label']} for {$site->name}: {$added} cookie" . ($added === 1 ? '' : 's') .
            " added, {$skipped} already existed for this site."
        );

        return $this->redirect($redirect);
    }

    /** Retain the existing draft-creation action for integrations that use it. */
    public function actionEdit(): Response
    {
        $this->requirePostRequest();
        $site = SiteContext::forCpRequest();
        $cookie = new CookieElement();
        $cookie->siteId = $site->id;
        $cookie->cookieSiteId = $site->id;
        $cookie->setScenario(Element::SCENARIO_ESSENTIALS);

        $success = Craft::$app->getDrafts()->saveElementAsDraft(
            $cookie,
            Craft::$app->getUser()->getId(),
            null,
            null,
            false,
        );

        if (!$success) {
            return $this->asModelFailure($cookie, Craft::t('app', 'Couldn’t create {type}.', [
                'type' => CookieElement::lowerDisplayName(),
            ]), 'app');
        }

        $editUrl = $cookie->getCpEditUrl();
        $response = $this->asModelSuccess($cookie, Craft::t('forty-cookieconsent', '{type} created.', [
            'type' => CookieElement::displayName(),
        ]), 'cookieconsent', ['cpEditUrl' => $editUrl]);

        if (!$this->request->getAcceptsJson()) {
            $response->redirect(UrlHelper::urlWithParams($editUrl, ['fresh' => 1]));
        }

        return $response;
    }

    public function actionGetConsent(): Response
    {
        $site = $this->request->getIsCpRequest()
            ? SiteContext::forCpRequest()
            : SiteContext::forConsentRequest();
        $logs = SiteData::getLogs($site->id);

        return $this->asJson([
            'success' => true,
            'data' => [$logs['accepted'], $logs['rejected']],
        ]);
    }

    public function actionLogConsent(): Response
    {
        $this->requirePostRequest();
        $site = SiteContext::forConsentRequest();
        $acceptType = $this->requiredString('acceptType');

        if (!in_array($acceptType, ['all', 'necessary', 'custom'], true)) {
            throw new BadRequestHttpException('Unknown consent type.');
        }

        SiteData::logConsent($site->id, $acceptType === 'all');

        return $this->asJson(['success' => true]);
    }

    private function requiredString(string $name): string
    {
        $value = $this->request->getRequiredBodyParam($name);
        if (!is_string($value) || trim($value) === '') {
            throw new BadRequestHttpException("A valid {$name} value is required.");
        }

        return trim($value);
    }
}
