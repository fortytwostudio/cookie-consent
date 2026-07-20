<?php

namespace fortytwostudio\cookieconsent\services;

use Craft;
use craft\helpers\App;
use fortytwostudio\cookieconsent\CookieConsent;
use fortytwostudio\cookieconsent\elements\CookieElement;

final class KnownCookies
{
    /**
     * Client-facing service presets. A service can create several cookies.
     */
    public static function services(): array
    {
        return [
            'google-analytics' => [
                'label' => 'Google Analytics (GA4)',
                'cookies' => [
                    self::cookie('analytics', '_ga', null, '2 years', "The _ga cookie, installed by Google Analytics, calculates visitor, session and campaign data and also keeps track of site usage for the site's analytics report. The cookie stores information anonymously and assigns a randomly generated number to recognize unique visitors."),
                    self::cookie('analytics', '_ga_*', null, '2 years', 'Google Analytics sets this cookie to store and count page views.'),
                ],
            ],
            'google-ads' => [
                'label' => 'Google Ads',
                'cookies' => [
                    self::cookie('advertisement', '_gcl_au', null, '3 months', 'Stores and tracks conversions from Google Ads.'),
                    self::cookie('advertisement', '_gcl_aw', null, '90 days', 'Stores click information for Google Ads conversion attribution.'),
                    self::cookie('advertisement', '_gcl_dc', null, '3 months', 'Stores Campaign Manager conversion information.'),
                ],
            ],
            'google-recaptcha' => [
                'label' => 'Google reCAPTCHA',
                'cookies' => [
                    self::cookie('necessary', '_GRECAPTCHA', '.google.com', '6 months', 'Provides reCAPTCHA risk analysis and helps protect forms from spam and abuse.'),
                ],
            ],
            'meta-pixel' => [
                'label' => 'Meta Pixel',
                'cookies' => [
                    self::cookie('advertisement', '_fbp', null, '3 months', 'Identifies browsers for advertising delivery and measurement by Meta.'),
                    self::cookie('advertisement', '_fbc', null, '3 months', 'Stores Facebook click information for advertising attribution.'),
                    self::cookie('advertisement', 'fr', '.facebook.com', '3 months', 'Supports advertising delivery, measurement and relevance on Meta services.'),
                ],
            ],
            'microsoft-clarity' => [
                'label' => 'Microsoft Clarity',
                'cookies' => [
                    self::cookie('analytics', '_clck', null, '1 year', 'Persists the Clarity user ID and preferences for this website.'),
                    self::cookie('analytics', '_clsk', null, '1 day', 'Connects multiple page views into a single Clarity session recording.'),
                    self::cookie('analytics', 'CLID', '.clarity.ms', '1 year', 'Identifies the first time Clarity saw this visitor on a website using Clarity.'),
                    self::cookie('advertisement', 'ANONCHK', '.c.clarity.ms', '10 minutes', 'Indicates whether the visitor identifier is transferred to Microsoft advertising services.'),
                    self::cookie('advertisement', 'MR', '.bing.com', '7 days', 'Indicates whether Microsoft advertising identifiers should be refreshed.'),
                    self::cookie('advertisement', 'MUID', '.bing.com', '1 year', 'Identifies unique web browsers visiting Microsoft sites.'),
                    self::cookie('analytics', 'SM', '.c.clarity.ms', 'Session', 'Synchronises the Microsoft user identifier across Microsoft domains.'),
                ],
            ],
            'hotjar' => [
                'label' => 'Hotjar',
                'cookies' => [
                    self::cookie('analytics', '_hjSessionUser_*', null, '1 year', 'Persists the Hotjar user ID for this website.'),
                    self::cookie('analytics', '_hjSession_*', null, '30 minutes', 'Holds current Hotjar session data.'),
                    self::cookie('analytics', '_hjAbsoluteSessionInProgress', null, '30 minutes', 'Detects the first page-view session of a visitor.'),
                    self::cookie('analytics', '_hjFirstSeen', null, '30 minutes', 'Identifies a new visitor’s first Hotjar session.'),
                ],
            ],
            'linkedin-insight' => [
                'label' => 'LinkedIn Insight Tag',
                'cookies' => [
                    self::cookie('advertisement', '_guid', '.linkedin.com', '90 days', 'Identifies LinkedIn members for advertising measurement.'),
                    self::cookie('advertisement', 'lms_ads', '.linkedin.com', '30 days', 'Identifies LinkedIn members off LinkedIn for advertising.'),
                    self::cookie('analytics', 'lms_analytics', '.linkedin.com', '30 days', 'Identifies LinkedIn members off LinkedIn for analytics.'),
                    self::cookie('analytics', 'AnalyticsSyncHistory', '.linkedin.com', '30 days', 'Stores when LinkedIn analytics identifiers were last synchronised.'),
                    self::cookie('advertisement', 'UserMatchHistory', '.linkedin.com', '30 days', 'Synchronises visitor identifiers for LinkedIn advertising.'),
                    self::cookie('advertisement', 'bcookie', '.linkedin.com', '1 year', 'Identifies the visitor’s browser to LinkedIn.'),
                    self::cookie('necessary', 'bscookie', '.linkedin.com', '1 year', 'Helps verify that a visitor logging in to LinkedIn is secure.'),
                    self::cookie('functionality', 'li_gc', '.linkedin.com', '6 months', 'Stores consent choices for non-essential LinkedIn cookies.'),
                ],
            ],
            'youtube' => [
                'label' => 'YouTube',
                'cookies' => [
                    self::cookie('functionality', 'YSC', '.youtube.com', 'Session', 'Stores a unique ID to keep statistics about videos viewed by the visitor.'),
                    self::cookie('advertisement', 'VISITOR_INFO1_LIVE', '.youtube.com', '6 months', 'Measures bandwidth and supports video delivery and advertising.'),
                    self::cookie('functionality', 'VISITOR_PRIVACY_METADATA', '.youtube.com', '6 months', 'Stores the visitor’s cookie consent state for YouTube.'),
                    self::cookie('functionality', 'PREF', '.youtube.com', '8 months', 'Stores preferences such as language and video playback settings.'),
                    self::cookie('functionality', 'CONSENT', '.youtube.com', '2 years', 'Stores the visitor’s Google and YouTube consent preferences.'),
                ],
            ],
            'vimeo' => [
                'label' => 'Vimeo',
                'cookies' => [
                    self::cookie('analytics', 'vuid', '.vimeo.com', '2 years', 'Assigns a Vimeo analytics identifier to the visitor.'),
                    self::cookie('functionality', 'player', '.vimeo.com', '1 year', 'Stores the visitor’s Vimeo video player preferences.'),
                    self::cookie('functionality', 'continuous_play_v3', '.vimeo.com', '2 years', 'Stores whether continuous video playback is enabled.'),
                ],
            ],
            'hubspot' => [
                'label' => 'HubSpot',
                'cookies' => [
                    self::cookie('analytics', '__hstc', null, '6 months', 'Tracks visitors, sessions and traffic sources in HubSpot.'),
                    self::cookie('analytics', 'hubspotutk', null, '6 months', 'Tracks a visitor’s identity and helps deduplicate form submissions.'),
                    self::cookie('analytics', '__hssc', null, '30 minutes', 'Tracks sessions for HubSpot analytics.'),
                    self::cookie('analytics', '__hssrc', null, 'Session', 'Determines whether the visitor restarted their browser.'),
                    self::cookie('functionality', 'messagesUtk', null, '6 months', 'Recognises visitors who use HubSpot chat.'),
                ],
            ],
            'cloudflare' => [
                'label' => 'Cloudflare',
                'cookies' => [
                    self::cookie('necessary', '__cf_bm', null, '30 minutes', 'Distinguishes legitimate visitors from automated traffic for Cloudflare bot protection.'),
                    self::cookie('necessary', '__cflb', null, 'Session', 'Maintains session affinity for Cloudflare Load Balancing.'),
                    self::cookie('necessary', 'cf_clearance', null, 'Up to 1 year', 'Stores proof that a visitor successfully passed a Cloudflare challenge.'),
                    self::cookie('necessary', '_cfuvid', null, 'Session', 'Distinguishes visitors sharing the same IP address for Cloudflare rate limiting.'),
                    self::cookie('necessary', '__cfseq', null, 'Session', 'Tracks request order and timing for Cloudflare sequence rules.'),
                ],
            ],
            'shopify' => [
                'label' => 'Shopify',
                'cookies' => [
                    self::cookie('analytics', '_shopify_y', null, '1 year', 'Provides Shopify analytics about how visitors use the store.'),
                    self::cookie('analytics', '_shopify_s', null, '30 minutes', 'Provides Shopify analytics for the current session.'),
                    self::cookie('functionality', '_tracking_consent', null, '1 year', 'Stores the visitor’s Shopify tracking preferences.'),
                    self::cookie('advertisement', '_landing_page', null, '2 weeks', 'Tracks the landing page used to reach the store.'),
                    self::cookie('advertisement', '_orig_referrer', null, '2 weeks', 'Tracks the original source used to reach the store.'),
                    self::cookie('necessary', 'cart', null, '2 weeks', 'Stores the visitor’s shopping cart.'),
                    self::cookie('functionality', 'cart_currency', null, '2 weeks', 'Stores the currency used by the shopping cart.'),
                    self::cookie('necessary', 'secure_customer_sig', null, '1 year', 'Supports secure Shopify customer login.'),
                    self::cookie('necessary', 'storefront_digest', null, '2 years', 'Supports access to password-protected Shopify storefronts.'),
                    self::cookie('functionality', 'localization', null, '1 year', 'Stores localisation and checkout preferences.'),
                ],
            ],
            'stripe' => [
                'label' => 'Stripe',
                'cookies' => [
                    self::cookie('necessary', '__stripe_mid', null, '1 year', 'Supports fraud prevention and secure Stripe payment processing.'),
                    self::cookie('necessary', '__stripe_sid', null, '30 minutes', 'Supports fraud prevention during a Stripe payment session.'),
                    self::cookie('necessary', 'm', '.stripe.com', '2 years', 'Supports fraud prevention and secure payment processing by Stripe.'),
                ],
            ],
        ];
    }

    public static function service(string $key): ?array
    {
        return self::services()[$key] ?? null;
    }

    public static function craftDefaults(): array
    {
        $general = Craft::$app->getConfig()->getGeneral();
        $domain = self::siteDomain();

        return [
            self::cookie('necessary', $general->phpSessionName ?? 'CraftSessionId', $domain, 'Session', 'Maintains a secure session for visitors and users of this website.'),
            self::cookie('necessary', $general->csrfTokenName ?? 'CRAFT_CSRF_TOKEN', $domain, 'Session', 'Protects forms and requests against Cross-Site Request Forgery attacks.'),
        ];
    }

    public static function siteDomain(): string
    {
        $configured = trim(CookieConsent::getInstance()->getSettings()->siteDomain ?? '');
        if ($configured !== '') {
            return $configured;
        }

        $site = Craft::$app->getSites()->getPrimarySite();
        $baseUrl = App::parseEnv($site->getBaseUrl());
        $host = is_string($baseUrl) ? parse_url($baseUrl, PHP_URL_HOST) : null;

        return is_string($host) && $host !== '' ? $host : 'First party';
    }

    public static function seedCraftDefaults(): void
    {
        self::createMissing(self::craftDefaults());
    }

    /**
     * @return array{added: string[], skipped: string[]}
     */
    public static function createMissing(array $definitions, ?string $firstPartyDomain = null): array
    {
        $existingIds = array_map(
            static fn(CookieElement $cookie): ?string => $cookie->cookieId,
            CookieElement::find()->status(null)->all(),
        );
        $result = ['added' => [], 'skipped' => []];

        foreach ($definitions as $definition) {
            $cookieId = $definition['cookieId'];

            // Cookie names are case-sensitive, so duplicate checking is too.
            if (in_array($cookieId, $existingIds, true)) {
                $result['skipped'][] = $cookieId;
                continue;
            }

            if ($definition['domain'] === null) {
                $definition['domain'] = $firstPartyDomain ?: self::siteDomain();
            }

            $cookie = new CookieElement();
            self::populate($cookie, $definition);

            if (Craft::$app->getElements()->saveElement($cookie)) {
                $result['added'][] = $cookieId;
                $existingIds[] = $cookieId;
            } else {
                Craft::warning("Could not create the {$cookieId} cookie: " . implode(', ', $cookie->getErrorSummary(true)), __METHOD__);
            }
        }

        return $result;
    }

    public static function populate(CookieElement $cookie, array $definition): void
    {
        foreach (['type', 'cookieId', 'domain', 'duration', 'description'] as $attribute) {
            $cookie->{$attribute} = $definition[$attribute];
        }
    }

    private static function cookie(string $type, string $cookieId, ?string $domain, string $duration, string $description): array
    {
        return compact('type', 'cookieId', 'domain', 'duration', 'description');
    }
}

