<?php
namespace fortytwostudio\cookieconsent\variables;

use Craft;
use craft\models\Site;
use fortytwostudio\cookieconsent\CookieConsent;
use fortytwostudio\cookieconsent\elements\CookieElement;
use fortytwostudio\cookieconsent\models\SettingsModel;
use fortytwostudio\cookieconsent\services\FrontendConfig;
use fortytwostudio\cookieconsent\services\SiteContext;
use fortytwostudio\cookieconsent\services\SiteData;
use yii\di\ServiceLocator;

class CookieVariable extends ServiceLocator
{
    public function getAllCookies(?int $siteId = null): array
    {
        return CookieElement::find()->siteId($this->siteId($siteId))->all();
    }

    public function getCookiesByType(string $type, ?int $siteId = null): array
    {
        return CookieElement::find()->siteId($this->siteId($siteId))->type($type)->all();
    }

    public function getSettings(?int $siteId = null): SettingsModel
    {
        return CookieConsent::getInstance()->getSiteSettings($this->siteId($siteId));
    }

    public function getContent(?int $siteId = null): array
    {
        return SiteData::getContent($this->siteId($siteId));
    }

    public function getLogs(?int $siteId = null): array
    {
        return SiteData::getLogs($this->siteId($siteId));
    }

    public function getLegacyLogs(): array
    {
        $user = Craft::$app->getUser()->getIdentity();
        return $user && $user->admin ? SiteData::getLegacyLogs() : ['accepted' => 0, 'rejected' => 0, 'total' => 0];
    }

    public function getCpSite(): Site
    {
        return SiteContext::forCpRequest();
    }

    public function getCpSites(): array
    {
        return SiteContext::editableSites();
    }

    public function getFrontendConfig(): array
    {
        return FrontendConfig::build();
    }

    private function siteId(?int $siteId): int
    {
        if ($siteId !== null) {
            return SiteContext::resolve($siteId)->id;
        }
        $request = Craft::$app->getRequest();
        return $request->getIsConsoleRequest() || !$request->getIsCpRequest()
            ? SiteContext::resolve()->id
            : SiteContext::forCpRequest()->id;
    }
}
