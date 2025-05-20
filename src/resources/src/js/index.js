if (typeof Craft.CookieConsent === typeof undefined) {
	Craft.CookieConsent = {};
}

Craft.CookieConsent.CookieIndex = Craft.BaseElementIndex.extend({
	$newTemplateBtnGroup: null,
	$newTemplateBtn: null,
	init(elements, main, controller) {
		this.on("selectSource", this.createButton.bind(this));

		this.on("selectSite", this.createButton.bind(this));

		this.base(elements, main, controller);

		// Add flex to the action buttons
		$('#action-buttons').addClass('flex');
	},
	createButton: function () {
		if (null === this.$source) return;

		null !== this.$newTemplateBtnGroup && this.$newTemplateBtnGroup.remove(),
			(this.$newTemplateBtnGroup = $('<div class="btngroup" data-wrapper/>'));

		// Add new cookie button
		this.$newTemplateBtn = Craft.ui
			.createButton({
				label: Craft.t("forty-cookieconsent", "New Cookie"),
				spinner: true,
			})
			.addClass("submit add icon")
			.appendTo(this.$newTemplateBtnGroup);

		this.addListener(this.$newTemplateBtn, "click", () => {
			this._createTemplate();
		});

		this.addButton(this.$newTemplateBtnGroup);
	},
	_createTemplate: async function () {
		const table = this.$source.data("handle");

		Craft.sendActionRequest("POST", "forty-cookieconsent/cookies/edit").then(
			({ data: table }) => {
				document.location.href = Craft.getUrl(table.cpEditUrl, {
					fresh: 1,
				});
			},
		);
	},
	_scanForCookies: async function () {

		Craft.sendActionRequest('GET', 'forty-cookieconsent/cookies/scan').then((response) =>
		{
			if (response.success) {
				console.log('✅ Puppeteer script ran successfully:', response.output);
			} else {
				console.error('❌ Puppeteer failed to run.');
				console.log(response);
			}
		}).catch((error) =>
		{
		  console.error('❌ Error:', error);
		});


		//
		//
		// $.ajax({
		//   url: '/actions/forty-cookieconsent/cookies/scan',
		//   method: 'GET',
		//   success(response) {
		// 	if (response.success) {
		// 	  console.log('✅ Puppeteer script ran successfully:', response.output);
		// 	} else {
		// 	  console.error('❌ Puppeteer failed to run.');
		// 	  console.log(response);
		// 	}
		//   },
		//   error(err) {
		// 	console.error('❌ AJAX error', err);
		//   }
		// });


	},
});

Craft.registerElementIndexClass(
	"fortytwostudio\\cookieconsent\\elements\\CookieElement",
	Craft.CookieConsent.CookieIndex,
);
