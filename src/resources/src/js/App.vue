<template>
	<button
		id="forty-cookie-modal-button"
		class="hidden"
		:class="triggerClass"
		type="button"
		v-on:click="$CC.showPreferences()"
		aria-label="Show cookie preferences modal"
		:style="{
			'--trigger-bg': colours.trigger.backgroundColour,
			'--trigger-fill': colours.trigger.fillColour,
			'--trigger-position': triggerPosition,
		}"
	>
		<div v-if="customIcon" v-html="customIcon"></div>
		<CookieIcon v-else></CookieIcon>
	</button>
</template>

<script>
// Components
import CookieIcon from "./resources/CookieIcon.vue";

export default {
	props: [
		"colours",
		"consentMode",
		"icon",
		"triggerPosition",
	],
	components: {
		CookieIcon,
	},
	data: function () {
		return {
			consentMode: this.consentMode,
			popup: this.colours.popup,
			modal: this.colours.modal,
			icon: this.icon,
			triggerPosition: this.triggerPosition,
		};
	},
	mounted() {
		this.updatePopupColors();
		this.updateModalColors();
	},
	computed: {
		customIcon() {
			if (!this.icon) {
				return null
			} else {
				return this.icon;
			}
		},
		triggerClass() {
			return this.triggerPosition;
		}
	},
	methods: {
		updatePopupColors() {
			const root = document.documentElement;
			root.style.setProperty("--cc-bg", this.popup.backgroundColour);
			root.style.setProperty("--cc-primary-color", this.popup.textColour);
			root.style.setProperty("--cc-separator-border-color", this.popup.textColour);
			root.style.setProperty("--cc-secondary-color", this.popup.textColour);

			root.style.setProperty("--cc-btn-primary-bg", this.popup.buttonBackground);
			root.style.setProperty("--cc-btn-primary-hover-bg", this.popup.hoverButtonBackground);
			root.style.setProperty("--cc-btn-primary-color", this.popup.buttonText);
			root.style.setProperty("--cc-btn-primary-hover-color", this.popup.hoverButtonText);

			root.style.setProperty("--cc-btn-secondary-bg", this.popup.manageBackground);
			root.style.setProperty(
				"--cc-btn-secondary-hover-border-color",
				this.popup.hoverManageBackground,
			);
			root.style.setProperty("--cc-btn-secondary-hover-bg", this.popup.hoverManageBackground);

			root.style.setProperty("--cc-btn-secondary-color", this.popup.manageText);
			root.style.setProperty("--cc-btn-secondary-hover-color", this.popup.hoverManageText);

			root.style.setProperty("--cc-footer-bg", this.popup.footerBackground);
			root.style.setProperty("--cc-footer-border-color", this.popup.footerBackground);
			root.style.setProperty("--cc-footer-color", this.popup.footerText);
			root.style.setProperty("--cc-footer-hover-color", this.popup.hoverFooterText);
		},
		updateModalColors() {
			const root = document.documentElement;
			root.style.setProperty("--cc-cookie-category-block-bg", this.modal.toggleBackground);
			root.style.setProperty(
				"--cc-cookie-category-block-border",
				this.modal.toggleBackground,
			);
			root.style.setProperty(
				"--cc-cookie-category-block-hover-bg",
				this.modal.hoverToggleBackground,
			);
			root.style.setProperty(
				"--cc-cookie-category-block-hover-border",
				this.modal.hoverToggleBackground,
			);
			root.style.setProperty("--cc-cookie-category-block-text", this.modal.toggleText);
			root.style.setProperty(
				"--cc-cookie-category-block-hover-text",
				this.modal.hoverToggleText,
			);
		},
	},
};
</script>

<style>

#cc-main .pm__section--toggle {
	.pm__section-title {
		color: var(--cc-cookie-category-block-text);

		&:hover,
		&:focus {
			color: var(--cc-cookie-category-block-hover-text);
		}
	}
}

#cc-main .pm__section--toggle.is-expanded .pm__section-title {
	color: var(--cc-modal-primary-color);

	&:hover {
		color: var(--cc-cookie-category-block-hover-text);
	}
}

#forty-cookie-modal-button {
	position: fixed;
	bottom: 1rem;
	width: 2.813rem;
	height: 2.813rem;
	background: var(--trigger-bg);
	color: var(--trigger-fill);
	padding: 0.5rem;
	border-radius: 100%;
	z-index: 500;

	&.left {
		left: 1rem;
	}

	&.right {
		right: 1rem;
	}

}

#forty-cookie-modal-button.hidden {
	display: none;
}

#forty-cookie-modal-button svg {
	width: 100%;
	height: 100%;
	pointer-events: none;
	fill: currentColor;

	path,
	polygon {
		fill: currentColor;
	}

}
</style>
