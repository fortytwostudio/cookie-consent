if (typeof Craft.CookieConsent === typeof undefined) {
	Craft.CookieConsent = {};
}

Craft.CookieConsent.CookieIndex = Craft.BaseElementIndex.extend({
	$newCookieBtn: null,

	init(elements, main, controller) {
		this.on("selectSource", this.createButton.bind(this));
		this.on("selectSite", () => {
			this.createButton();
			this.syncSiteNavigation();
		});
		this.base(elements, main, controller);
		$('#action-buttons').addClass('flex');
		this.syncSiteNavigation();
	},

	syncSiteNavigation() {
		const site = this.getSite();
		if (this.settings.context !== 'index' || !site?.handle) {
			return;
		}

		// Craft rebuilds breadcrumb URLs when query parameters change. Use its
		// canonical handle so each site's link cannot inherit an old numeric ID.
		Craft.setQueryParam('siteId', null);
		Craft.setQueryParam('site', site.handle);
		document.querySelectorAll('#nav a[href]').forEach(link => {
			const url = new URL(link.href, window.location.href);
			if (
				url.origin !== window.location.origin ||
				!/(?:^|\/)42cookie-consent(?:\/|$)/.test(url.pathname)
			) {
				return;
			}

			url.searchParams.delete('siteId');
			url.searchParams.set('site', site.handle);
			link.href = url.toString();
		});
	},

	createButton() {
		if (this.$source === null) {
			return;
		}

		this.$newCookieBtn?.remove();
		this.$newCookieBtn = Craft.ui.createButton({
			label: Craft.t("forty-cookieconsent", "New Cookie"),
		})
			.addClass("submit add icon")
			.on("click", () => {
				const site = this.getSite();
				document.location.href = Craft.getUrl(
					"42cookie-consent/cookies/new",
					site?.handle ? {site: site.handle} : {siteId: this.siteId ?? Craft.siteId},
				);
			});

		this.addButton(this.$newCookieBtn);
	},
});

Craft.registerElementIndexClass(
	"fortytwostudio\\cookieconsent\\elements\\CookieElement",
	Craft.CookieConsent.CookieIndex,
);
