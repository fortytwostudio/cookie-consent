<?php

namespace fortytwostudio\cookieconsent\services;

use Craft;
use craft\elements\Asset;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use fortytwostudio\cookieconsent\CookieConsent;
use fortytwostudio\cookieconsent\elements\CookieElement;
use fortytwostudio\cookieconsent\variables\CookieVariable;

/** Builds the public configuration from one resolved site's data. */
final class FrontendConfig
{
    public static function build(): array
    {
        $site = SiteContext::resolve();
        $settings = CookieConsent::getInstance()->getSiteSettings($site->id);
        $variable = new CookieVariable();
        $content = $variable->getContent($site->id);

        $cookies = static fn(string $type): array => array_map(
            static fn(CookieElement $cookie): array => [
                'cookieId' => (string)$cookie->cookieId,
                'domain' => (string)$cookie->domain,
                'duration' => (string)$cookie->duration,
                'description' => (string)$cookie->description,
            ],
            $variable->getCookiesByType($type, $site->id),
        );

        $labels = [
            'acceptAll' => 'Accept all',
            'rejectAll' => 'Reject all',
            'managePreferences' => 'Manage preferences',
            'rejectAllAndClose' => 'Reject all and close',
            'savePreferences' => 'Save preferences',
            'showPreferences' => 'Show cookie preferences modal',
            'necessary' => 'Necessary cookies',
            'functionality' => 'Functional cookies',
            'analytics' => 'Analytics',
            'performance' => 'Performance cookies',
            'advertisement' => 'Advertisement cookies',
            'security' => 'Security cookies',
            'cookie' => 'Cookie',
            'domain' => 'Domain',
            'description' => 'Description',
            'analyticsStorage' => 'Enables storage (such as cookies) related to analytics e.g. visit duration.',
            'adStorage' => 'Enables storage (such as cookies) related to advertising.',
            'adUserData' => 'Sets consent for sending user data related to advertising to Google.',
            'adPersonalization' => 'Sets consent for personalized advertising.',
            'functionalityStorage' => 'Enables storage that supports the functionality of the website or app e.g. language settings.',
            'personalizationStorage' => 'Enables storage related to personalization e.g. video recommendations.',
            'securityStorage' => 'Enables storage related to security such as authentication functionality, fraud prevention, and other user protection.',
        ];

        foreach ($labels as &$label) {
            $label = Craft::t('forty-cookieconsent', $label, [], $site->language);
        }
        unset($label);

        $icon = null;
        if (!empty($content['triggerIcon'])) {
            $asset = Asset::find()->id((int)$content['triggerIcon'])->siteId($site->id)->one();
            if ($asset && strtolower($asset->getExtension()) === 'svg') {
                try {
                    $icon = Html::svg($asset);
                } catch (\Throwable $exception) {
                    Craft::warning('Could not load the cookie consent icon: ' . $exception->getMessage(), __METHOD__);
                }
            }
        }

        return [
            // Post to this request's real entry script on the current origin. actionUrl()
            // can use a shared @web alias that points to the primary site's hostname.
            'actionUrl' => UrlHelper::urlWithParams(Craft::$app->getRequest()->getScriptUrl(), [
                'action' => 'forty-cookieconsent/cookies/log-consent',
            ]),
            'siteId' => $site->id,
            'language' => $site->language,
            'labels' => $labels,
            'enabled' => $settings->isEnabled(),
            'cookieName' => SiteContext::cookieName($site->id),
            'cookiePath' => '/',
            'cookieIcon' => $icon,
            'title' => (string)($content['popupTitle'] ?? ''),
            'description' => (string)($content['popupDescription'] ?? ''),
            'footer' => (string)($content['popupFooter'] ?? ''),
            'consentLayout' => $content['popupLayout'] ?? 'box inline',
            'consentPosition' => $content['popupPosition'] ?? 'bottom right',
            'consentEqualWeightButtons' => $settings->consentEqualWeightButtons,
            'consentFlipButtons' => $settings->consentFlipButtons,
            'internalTitle' => (string)($content['preferencesTitle'] ?? ''),
            'internalDescription' => (string)($content['preferencesDescription'] ?? ''),
            'requiredDescription' => (string)($content['requiredCookies'] ?? ''),
            'functionalDescription' => (string)($content['functionalCookies'] ?? ''),
            'analyticsDescription' => (string)($content['analyticsCookies'] ?? ''),
            'performanceDescription' => (string)($content['performanceCookies'] ?? ''),
            'advertisementDescription' => (string)($content['advertisingCookies'] ?? ''),
            'securityDescription' => (string)($content['securityCookies'] ?? ''),
            'preferencesLayout' => $content['preferencesLayout'] ?? 'bar',
            'preferencesPosition' => $content['preferencesPosition'] ?? 'right',
            'preferencesEqualWeightButtons' => $settings->preferencesEqualWeightButtons,
            'preferencesFlipButtons' => $settings->preferencesFlipButtons,
            'triggerPosition' => $content['triggerPosition'] ?? 'left',
            'sendGtag' => $settings->sendGtag,
            'requiredCookies' => $cookies('necessary'),
            'functionalCookies' => $cookies('functionality'),
            'analyticsCookies' => $cookies('analytics'),
            'performanceCookies' => $cookies('performance'),
            'advertisementCookies' => $cookies('advertisement'),
            'securityCookies' => $cookies('security'),
            'colours' => [
                'popup' => [
                    'backgroundColour' => '#' . $settings->popupBackgroundColour,
                    'textColour' => '#' . $settings->popupTextColour,
                    'buttonBackground' => '#' . $settings->buttonBackground,
                    'hoverButtonBackground' => '#' . $settings->hoverButtonBackground,
                    'buttonText' => '#' . $settings->buttonText,
                    'hoverButtonText' => '#' . $settings->hoverButtonText,
                    'manageBackground' => '#' . $settings->manageBackground,
                    'hoverManageBackground' => '#' . $settings->hoverManageBackground,
                    'manageText' => '#' . $settings->manageText,
                    'hoverManageText' => '#' . $settings->hoverManageText,
                    'footerBackground' => '#' . $settings->footerBackground,
                    'footerText' => '#' . $settings->footerText,
                    'hoverFooterText' => '#' . $settings->hoverFooterText,
                ],
                'modal' => [
                    'toggleBackground' => '#' . $settings->toggleBackground,
                    'hoverToggleBackground' => '#' . $settings->hoverToggleBackground,
                    'toggleText' => '#' . $settings->toggleText,
                    'hoverToggleText' => '#' . $settings->hoverToggleText,
                ],
                'trigger' => [
                    'backgroundColour' => '#' . $settings->triggerBackground,
                    'fillColour' => '#' . $settings->triggerFill,
                ],
            ],
        ];
    }
}
