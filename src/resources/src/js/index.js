if (typeof Craft.CookieConsent === typeof undefined) {
	Craft.CookieConsent = {};
}

Craft.CookieConsent.CookieIndex = Craft.BaseElementIndex.extend({
	$newCookieBtn: null,

	init(elements, main, controller) {
		this.on("selectSource", this.createButton.bind(this));
		this.on("selectSite", this.createButton.bind(this));
		this.base(elements, main, controller);
		$('#action-buttons').addClass('flex');
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
				document.location.href = Craft.getUrl("42cookie-consent/cookies/new");
			});

		this.addButton(this.$newCookieBtn);
	},
});

Craft.registerElementIndexClass(
	"fortytwostudio\\cookieconsent\\elements\\CookieElement",
	Craft.CookieConsent.CookieIndex,
);
