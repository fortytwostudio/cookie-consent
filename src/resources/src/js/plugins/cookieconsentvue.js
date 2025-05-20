import "vanilla-cookieconsent/dist/cookieconsent.css";
import * as CookieConsent from "vanilla-cookieconsent";
import config from "./cookieconsent-config";

export default {
	install: (app) => {
		CookieConsent.run(config);
		app.config.globalProperties.$CC = CookieConsent;
		app.config.globalProperties.$config = config;
	},
};
