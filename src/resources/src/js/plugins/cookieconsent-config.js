import * as CookieConsent from "vanilla-cookieconsent";

// Settings
const settings = window.FortyCookieConsent || {};
const labels = settings.labels || {};
const language = settings.language || "en";

// Cookies
const requiredCookies = settings.requiredCookies || [];
const functionalCookies = settings.functionalCookies || [];
const analyticsCookies = settings.analyticsCookies || [];
const performanceCookies = settings.performanceCookies || [];
const advertisementCookies = settings.advertisementCookies || [];
const securityCookies = settings.securityCookies || [];

const CAT_NECESSARY = "necessary";
const CAT_ANALYTICS = "analytics";
const CAT_ADVERTISEMENT = "advertisement";
const CAT_FUNCTIONALITY = "functionality";
const CAT_SECURITY = "security";

const SERVICE_AD_STORAGE = 'ad_storage'
const SERVICE_AD_USER_DATA = 'ad_user_data'
const SERVICE_AD_PERSONALIZATION = 'ad_personalization'
const SERVICE_ANALYTICS_STORAGE = 'analytics_storage'
const SERVICE_FUNCTIONALITY_STORAGE = 'functionality_storage'
const SERVICE_PERSONALIZATION_STORAGE = 'personalization_storage'
const SERVICE_SECURITY_STORAGE = 'security_storage'

// Define dataLayer and the gtag function.
window.dataLayer = window.dataLayer || [];
function gtag(){window.dataLayer.push(arguments);}

// Set default consent to 'denied' (this should happen before changing any other dataLayer)
if (settings.enabled && settings.sendGtag) {
	gtag('consent', 'default', {
		[SERVICE_AD_STORAGE]: 'denied',
		[SERVICE_AD_USER_DATA]: 'denied',
		[SERVICE_AD_PERSONALIZATION]: 'denied',
		[SERVICE_ANALYTICS_STORAGE]: 'denied',
		[SERVICE_FUNCTIONALITY_STORAGE]: 'denied',
		[SERVICE_PERSONALIZATION_STORAGE]: 'denied',
		[SERVICE_SECURITY_STORAGE]: 'denied',
	});
}

function updateGtagConsent() {
	if (settings.sendGtag) {
		gtag('consent', 'update', {
			[SERVICE_ANALYTICS_STORAGE]: CookieConsent.acceptedService(SERVICE_ANALYTICS_STORAGE, CAT_ANALYTICS) ? 'granted' : 'denied',
			[SERVICE_AD_STORAGE]: CookieConsent.acceptedService(SERVICE_AD_STORAGE, CAT_ADVERTISEMENT) ? 'granted' : 'denied',
			[SERVICE_AD_USER_DATA]: CookieConsent.acceptedService(SERVICE_AD_USER_DATA, CAT_ADVERTISEMENT) ? 'granted' : 'denied',
			[SERVICE_AD_PERSONALIZATION]: CookieConsent.acceptedService(SERVICE_AD_PERSONALIZATION, CAT_ADVERTISEMENT) ? 'granted' : 'denied',
			[SERVICE_FUNCTIONALITY_STORAGE]: CookieConsent.acceptedService(SERVICE_FUNCTIONALITY_STORAGE, CAT_FUNCTIONALITY) ? 'granted' : 'denied',
			[SERVICE_PERSONALIZATION_STORAGE]: CookieConsent.acceptedService(SERVICE_PERSONALIZATION_STORAGE, CAT_FUNCTIONALITY) ? 'granted' : 'denied',
			[SERVICE_SECURITY_STORAGE]: CookieConsent.acceptedService(SERVICE_SECURITY_STORAGE, CAT_SECURITY) ? 'granted' : 'denied',
		});
	}
}

const config = {
	cookie: {
		name: settings.cookieName || "cc_cookie",
		path: settings.cookiePath || "/",
	},
	guiOptions: {
		consentModal: {
			layout: settings.consentLayout,
			position: settings.consentPosition,
			equalWeightButtons: settings.consentEqualWeightButtons,
			flipButtons: settings.consentFlipButtons,
		},
		preferencesModal: {
			layout: settings.preferencesLayout,
			position: settings.preferencesPosition,
			equalWeightButtons: settings.preferencesEqualWeightButtons,
			flipButtons: settings.preferencesFlipButtons,
		},
	},
	onFirstConsent: ({ cookie }) => {
		logConsent();
		updateGtagConsent();
	},
	onConsent: () => {
		const cookieButton = document.querySelector("#forty-cookie-modal-button");

		if (cookieButton) {
			cookieButton.classList.remove("hidden");
		}

		updateGtagConsent();
	},
	onChange: () => {
		logConsent("changed");
		updateGtagConsent();
	},
	categories: {
		[CAT_NECESSARY]: {
			enabled: true,  // this category is enabled by default
			readOnly: true,  // this category cannot be disabled
		},
		[CAT_ANALYTICS]: {
			autoClear: {
				cookies: [
					{
						name: /^_ga/,   // regex: match all cookies starting with '_ga'
					},
					{
						name: '_gid',   // string: exact cookie name
					}
				]
			},
			services: settings.sendGtag ? {
				[SERVICE_ANALYTICS_STORAGE]: {
					label: labels.analyticsStorage,
				}
			} : null
		},
		[CAT_ADVERTISEMENT]: {
			services: settings.sendGtag ? {
				[SERVICE_AD_STORAGE]: {
					label: labels.adStorage,
				},
				[SERVICE_AD_USER_DATA]: {
					label: labels.adUserData,
				},
				[SERVICE_AD_PERSONALIZATION]: {
					label: labels.adPersonalization,
				},
			} : null
		},
		[CAT_FUNCTIONALITY]: {
			services: settings.sendGtag ? {
				[SERVICE_FUNCTIONALITY_STORAGE]: {
					label: labels.functionalityStorage,
				},
				[SERVICE_PERSONALIZATION_STORAGE]: {
					label: labels.personalizationStorage,
				},
			} : null
		},
		performance: {},
		[CAT_SECURITY]: {
			services: settings.sendGtag ? {
				[SERVICE_SECURITY_STORAGE]: {
					label: labels.securityStorage,
				},
			} : null
		}
	},
	language: {
		default: language,

		translations: {
			[language]: {
				consentModal: {
					title: settings.title,
					description: settings.description,
					acceptAllBtn: labels.acceptAll,
					acceptNecessaryBtn: labels.rejectAll,
					showPreferencesBtn: labels.managePreferences,
					closeIconLabel: labels.rejectAllAndClose,
					footer: settings.footer,
				},
				preferencesModal: {
					title: settings.internalTitle,
					acceptAllBtn: labels.acceptAll,
					acceptNecessaryBtn: labels.rejectAll,
					savePreferencesBtn: labels.savePreferences,
					sections: [
						{
							description: settings.internalDescription,
						},
						{
							title: labels.necessary,
							description: settings.requiredDescription,
							linkedCategory: CAT_NECESSARY,
							cookieTable: createCookieTable(requiredCookies),
						},
						{
							title: labels.functionality,
							description: settings.functionalDescription,
							linkedCategory: CAT_FUNCTIONALITY,
							cookieTable: createCookieTable(functionalCookies),
						},
						{
							title: labels.analytics,
							description: settings.analyticsDescription,
							linkedCategory: CAT_ANALYTICS,
							cookieTable: createCookieTable(analyticsCookies),
						},
						{
							title: labels.performance,
							description: settings.performanceDescription,
							linkedCategory: "performance",
							cookieTable: createCookieTable(performanceCookies),
						},
						{
							title: labels.advertisement,
							description: settings.advertisementDescription,
							linkedCategory: CAT_ADVERTISEMENT,
							cookieTable: createCookieTable(advertisementCookies),
						},
						{
							title: labels.security,
							description: settings.securityDescription,
							linkedCategory: CAT_SECURITY,
							cookieTable: createCookieTable(securityCookies),
						},
					],
				},
			},
		},
	},
};

function createCookieTable(cookies) {
	if (cookies.length === 0) {
		return undefined;
	}

	const table = {
		headers: {
			name: labels.cookie,
			domain: labels.domain,
			desc: labels.description,
		},
		body: [],
	};

	cookies.forEach(function (element) {
		const object = {
			name: element.cookieId,
			domain: element.domain,
			desc: element.description,
		};

		table.body.push(object);
	});

	return table;
}

async function logConsent(action = "first") {
    const cookie = CookieConsent.getCookie();
    const preferences = CookieConsent.getUserPreferences();

    const userConsent = {
        siteId: settings.siteId,
        consentId: cookie.consentId,
        consentAction: action,
        acceptType: preferences.acceptType,
        acceptedCategories: preferences.acceptedCategories,
        rejectedCategories: preferences.rejectedCategories,
    };

    try {
        const response = await fetch(settings.actionUrl, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(userConsent),
        });
        if (!response.ok) {
            throw new Error(`Consent request failed (${response.status}).`);
        }
    } catch (error) {
        console.error("Unable to log cookie consent:", error);
    }
}

export default config;
