<?php

namespace fortytwostudio\cookieconsent\services;

use Craft;
use craft\db\Query;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use yii\db\Expression;

/** Site content lives in the database; technical settings remain in project config. */
final class SiteData
{
    public const CONTENT_TABLE = '{{%forty_cookies_content}}';
    public const TRACKED_TABLE = '{{%forty_cookies_tracked}}';

    public static function defaults(?int $siteId = null): array
    {
        $defaults = [
            'popupTitle' => 'We value your privacy',
            'popupDescription' => "We use cookies to enhance your browsing experience, serve personalised ads or content, and analyse our traffic. By clicking 'Accept', you consent to our use of cookies.",
            'popupFooter' => '<a href="/privacy-policy">Privacy Policy</a>',
            'popupLayout' => 'box inline',
            'popupPosition' => 'bottom right',
            'preferencesTitle' => 'Customise Consent Preferences',
            'preferencesDescription' => 'A cookie is a small text file sent to your browser and stored on your device by a website you visit. Cookies may save information about the pages you visit and the devices you use, which in return can give us more insight about how you use our website so we can improve its usability and deliver more relevant content.',
            'requiredCookies' => 'Necessary cookies are required to enable the basic features of this site, such as providing secure log-in or adjusting your consent preferences. These cookies do not store any personally identifiable data.',
            'functionalCookies' => 'Functional cookies help perform certain functionalities like sharing the content of the website on social media platforms, collecting feedback, and other third-party features.',
            'analyticsCookies' => 'Analytical cookies are used to understand how visitors interact with the website. These cookies help provide information on metrics such as the number of visitors, bounce rate, traffic source, etc.',
            'performanceCookies' => 'Performance cookies are used to understand and analyse the key performance indexes of the website which helps in delivering a better user experience for the visitors.',
            'advertisingCookies' => 'Advertisement cookies are used to provide visitors with customised advertisements based on the pages you visited previously and to analyse the effectiveness of the ad campaigns.',
            'securityCookies' => 'Cookies used for security authenticate users, prevent fraud, and protect users as they interact with a service.',
            'preferencesLayout' => 'bar',
            'preferencesPosition' => 'right',
            'triggerIcon' => null,
            'triggerPosition' => 'left',
        ];

        if ($siteId !== null) {
            $defaults['popupFooter'] = Html::a('Privacy Policy', UrlHelper::siteUrl('privacy-policy', null, null, $siteId));
        }

        return $defaults;
    }

    public static function getContent(?int $siteId = null): array
    {
        $siteId = SiteContext::resolve($siteId)->id;
        $row = (new Query())->from(self::CONTENT_TABLE)->where(['siteId' => $siteId])->one();

        return array_replace(self::defaults($siteId), $row ?: [], ['siteId' => $siteId]);
    }

    public static function saveContent(int $siteId, array $data): void
    {
        $siteId = SiteContext::resolve($siteId)->id;
        $data = array_intersect_key($data, self::defaults());
        Craft::$app->getDb()->createCommand()->upsert(
            self::CONTENT_TABLE,
            ['siteId' => $siteId] + array_replace(self::defaults($siteId), $data),
            $data,
        )->execute();
    }

    /** @return array{accepted:int,rejected:int,total:int} */
    public static function getLogs(?int $siteId = null): array
    {
        $siteId = SiteContext::resolve($siteId)->id;
        $row = (new Query())->from(self::TRACKED_TABLE)->where(['siteId' => $siteId])->one();

        return self::totals($row ?: []);
    }

    /** Legacy rows awaiting transfer into the primary site's totals. */
    public static function getLegacyLogs(): array
    {
        $rows = (new Query())->from(self::TRACKED_TABLE)->where(['siteId' => null])->all();
        $totals = ['accepted' => 0, 'rejected' => 0];
        foreach ($rows as $row) {
            $totals['accepted'] += (int)$row['accepted'];
            $totals['rejected'] += (int)$row['rejected'];
        }

        return self::totals($totals);
    }

    public static function initializeSite(int $siteId): void
    {
        $siteId = SiteContext::resolve($siteId)->id;
        Craft::$app->getDb()->createCommand()->upsert(
            self::CONTENT_TABLE,
            ['siteId' => $siteId] + self::defaults($siteId),
            false,
        )->execute();
        self::ensureLogRow($siteId);
    }

    public static function logConsent(int $siteId, bool $accepted): void
    {
        $siteId = SiteContext::resolve($siteId)->id;
        self::ensureLogRow($siteId);
        $column = $accepted ? 'accepted' : 'rejected';

        // Let the database increment the value so concurrent requests cannot lose clicks.
        Craft::$app->getDb()->createCommand()->update(
            self::TRACKED_TABLE,
            [$column => new Expression('[[' . $column . ']] + 1')],
            ['siteId' => $siteId],
        )->execute();
    }

    private static function ensureLogRow(int $siteId): void
    {
        Craft::$app->getDb()->createCommand()->upsert(
            self::TRACKED_TABLE,
            ['siteId' => $siteId, 'accepted' => 0, 'rejected' => 0],
            false,
        )->execute();
    }

    private static function totals(array $row): array
    {
        $accepted = (int)($row['accepted'] ?? 0);
        $rejected = (int)($row['rejected'] ?? 0);
        return ['accepted' => $accepted, 'rejected' => $rejected, 'total' => $accepted + $rejected];
    }
}
