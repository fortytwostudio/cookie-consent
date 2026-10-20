<?php

namespace fortytwostudio\cookieconsent\services;

use Craft;
use craft\errors\SiteNotFoundException;
use craft\helpers\App;
use craft\models\Site;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

/** Resolves site identity without storing request-specific state on the plugin. */
final class SiteContext
{
    public static function cookieName(?int $siteId = null): string
    {
        return 'cc_cookie_' . str_replace('-', '', self::resolve($siteId)->uid);
    }

    public static function resolve(int|string|null $site = null): Site
    {
        $sites = Craft::$app->getSites();
        if ($site === null) {
            return $sites->getCurrentSite();
        }

        try {
            $resolved = is_int($site) || ctype_digit($site)
                ? $sites->getSiteById((int)$site)
                : ($sites->getSiteByHandle($site) ?? $sites->getSiteByUid($site));
        } catch (SiteNotFoundException) {
            $resolved = null;
        }

        if (!$resolved) {
            throw new BadRequestHttpException('Unknown cookie consent site.');
        }

        return $resolved;
    }

    /** @return Site[] */
    public static function editableSites(): array
    {
        $user = Craft::$app->getUser()->getIdentity();
        if (!$user || (!$user->admin && !$user->can('accessPlugin-forty-cookieconsent'))) {
            throw new ForbiddenHttpException('You cannot manage cookie consent.');
        }

        return array_values(array_filter(
            Craft::$app->getSites()->getAllSites(),
            static fn(Site $site): bool => $user->admin
                || !Craft::$app->getIsMultiSite()
                || $user->can('editSite:' . $site->uid),
        ));
    }

    public static function forCpRequest(): Site
    {
        $request = Craft::$app->getRequest();
        $isGet = $request->getIsGet();
        $handle = $request->getQueryParam('site');
        $id = $request->getQueryParam('siteId');

        if (!$isGet) {
            // A form's site selection is one pair. Do not combine a posted
            // handle/ID with a stale selector from the page's query string.
            $postedHandle = $request->getBodyParam('site');
            $postedId = $request->getBodyParam('siteId');
            if ($postedHandle !== null || $postedId !== null) {
                $handle = $postedHandle;
                $id = $postedId;
            }
        }

        foreach ([$handle, $id] as $value) {
            if ($value !== null && !is_string($value) && !is_int($value)) {
                throw new BadRequestHttpException('Invalid site selection.');
            }
        }

        $editable = self::editableSites();
        if (!$editable) {
            throw new ForbiddenHttpException('You cannot edit any sites.');
        }

        $site = self::resolve($handle ?? $id);
        // Craft's CP site menu replaces `site` while retaining other query
        // parameters, including an old `siteId`. The handle wins for GETs.
        // Conflicting selections in a submitted form still fail before saving.
        if (!$isGet && $handle !== null && $id !== null && self::resolve($id)->id !== $site->id) {
            throw new BadRequestHttpException('Conflicting site selections.');
        }

        foreach ($editable as $allowed) {
            if ($allowed->id === $site->id) {
                return $site;
            }
        }

        // A user with access only to a secondary site needs a usable default.
        if ($handle === null && $id === null) {
            return $editable[0];
        }

        throw new ForbiddenHttpException('You cannot edit cookie consent for this site.');
    }

    /** Action URLs may use a shared front controller, so validate the posted site. */
    public static function forConsentRequest(): Site
    {
        $request = Craft::$app->getRequest();
        $siteId = $request->getBodyParam('siteId') ?? $request->getQueryParam('siteId');
        if ($siteId !== null && (!is_int($siteId) && !(is_string($siteId) && ctype_digit($siteId)))) {
            throw new BadRequestHttpException('Invalid consent site.');
        }

        $site = self::resolve($siteId === null ? null : (int)$siteId);
        $baseUrl = App::parseEnv($site->getBaseUrl());
        $host = is_string($baseUrl) ? parse_url(Craft::getAlias($baseUrl), PHP_URL_HOST) : null;

        if (!$site->hasUrls || ($host && strcasecmp($host, $request->getHostName()) !== 0)) {
            throw new BadRequestHttpException('The consent site does not belong to this hostname.');
        }

        return $site;
    }
}
