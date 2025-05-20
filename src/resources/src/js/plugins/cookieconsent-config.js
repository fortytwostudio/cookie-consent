import * as CookieConsent from "vanilla-cookieconsent";

// Settings
const settings = window.FortyCookieConsent;

// Cookies
const requiredCookies = JSON.parse(settings.requiredCookies.replace(/&quot;/gi, '"'));
const functionalCookies = JSON.parse(settings.functionalCookies.replace(/&quot;/gi, '"'));
const analyticsCookies = JSON.parse(settings.analyticsCookies.replace(/&quot;/gi, '"'));
const performanceCookies = JSON.parse(settings.performanceCookies.replace(/&quot;/gi, '"'));
const advertisementCookies = JSON.parse(settings.advertisementCookies.replace(/&quot;/gi, '"'));
const securityCookies = JSON.parse(settings.securityCookies.replace(/&quot;/gi, '"'));

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
function gtag(){dataLayer.push(arguments);}

// Set default consent to 'denied' (this should happen before changing any other dataLayer)
gtag('consent', 'default', {
	[SERVICE_AD_STORAGE]: 'denied',
	[SERVICE_AD_USER_DATA]: 'denied',
	[SERVICE_AD_PERSONALIZATION]: 'denied',
	[SERVICE_ANALYTICS_STORAGE]: 'denied',
	[SERVICE_FUNCTIONALITY_STORAGE]: 'denied',
	[SERVICE_PERSONALIZATION_STORAGE]: 'denied',
	[SERVICE_SECURITY_STORAGE]: 'denied',
});

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
	guiOptions: {
		consentModal: {
			layout: settings.consentLayout,
			position: settings.consentPosition,
		},
		preferencesModal: {
			layout: settings.preferencesLayout,
			position: settings.preferencesPosition,
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
					label: 'Enables storage (such as cookies) related to analytics e.g. visit duration.',
				}
			} : null
		},
		[CAT_ADVERTISEMENT]: {
			services: settings.sendGtag ? {
				[SERVICE_AD_STORAGE]: {
					label: 'Enables storage (such as cookies) related to advertising.',
				},
				[SERVICE_AD_USER_DATA]: {
					label: 'Sets consent for sending user data related to advertising to Google.',
				},
				[SERVICE_AD_PERSONALIZATION]: {
					label: 'Sets consent for personalized advertising.',
				},
			} : null
		},
		[CAT_FUNCTIONALITY]: {
			services: settings.sendGtag ? {
				[SERVICE_FUNCTIONALITY_STORAGE]: {
					label: 'Enables storage that supports the functionality of the website or app e.g. language settings.',
				},
				[SERVICE_PERSONALIZATION_STORAGE]: {
					label: 'Enables storage related to personalization e.g. video recommendations.',
				},
			} : null
		},
		performance: {},
		[CAT_SECURITY]: {
			services: settings.sendGtag ? {
				[SERVICE_SECURITY_STORAGE]: {
					label: 'Enables storage related to security such as authentication functionality, fraud prevention, and other user protection.',
				},
			} : null
		}
	},
	language: {
		default: "en",

		translations: {
			en: {
				consentModal: {
					title: settings.title,
					description: settings.description,
					acceptAllBtn: "Accept all",
					acceptNecessaryBtn: "Reject all",
					showPreferencesBtn: "Manage preferences",
					closeIconLabel: "Reject all and close",
					footer: convertHTMLEntity(settings.footer),
				},
				preferencesModal: {
					title: settings.internalTitle,
					acceptAllBtn: "Accept all",
					acceptNecessaryBtn: "Reject all",
					savePreferencesBtn: "Save preferences",
					sections: [
						{
							description: settings.internalDescription,
						},
						{
							title: "Necessary cookies",
							description: settings.requiredDescription,
							linkedCategory: CAT_NECESSARY,
							cookieTable: createCookieTable(requiredCookies),
						},
						{
							title: "Functional cookies",
							description: settings.functionalDescription,
							linkedCategory: CAT_FUNCTIONALITY,
							cookieTable: createCookieTable(functionalCookies),
						},
						{
							title: "Analytics",
							description: settings.analyticsDescription,
							linkedCategory: CAT_ANALYTICS,
							cookieTable: createCookieTable(analyticsCookies),
						},
						{
							title: "Performance cookies",
							description: settings.performanceDescription,
							linkedCategory: "performance",
							cookieTable: createCookieTable(performanceCookies),
						},
						{
							title: "Advertisement cookies",
							description: settings.advertisementDescription,
							linkedCategory: CAT_ADVERTISEMENT,
							cookieTable: createCookieTable(advertisementCookies),
						},
						{
							title: "Security cookies",
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

function convertHTMLEntity(text) {
	const span = document.createElement("span");

	return text.replace(/&[#A-Za-z0-9]+;/gi, (entity, position, text) => {
		span.innerHTML = entity;
		return span.innerText;
	});
}

function createCookieTable(cookies) {
	if (cookies.length == 0) {
		return [];
	}

	const table = {
		headers: {
			name: "Cookie",
			domain: "Domain",
			desc: "Description",
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
	// Retrieve all the fields
	const cookie = CookieConsent.getCookie();
	const preferences = CookieConsent.getUserPreferences();

	// In this example we're saving only 4 fields
	const userConsent = {
		consentId: cookie.consentId,
		consentAction: action,
		acceptType: preferences.acceptType,
		acceptedCategories: preferences.acceptedCategories,
		rejectedCategories: preferences.rejectedCategories,
		[settings.csrf.name]: settings.csrf.value,
	};

	// Send the data backend record
	const response = await fetch("actions/forty-cookieconsent/cookies/log-consent", {
		method: "POST",
		headers: {
			"Content-Type": "application/json",
		},
		body: JSON.stringify(userConsent),
	});
}

export default config;
