export default class {
	constructor(callbacks, acceptAllCallback = null) {
		this.initialized = false;
		this.visible = true;
		this.options = new Map;
		this.acceptAllCallback = acceptAllCallback;

		const part = document.cookie.split('; ').find((row) => row.startsWith("lpcPrivacyConsent="));
		if (part) {
			const consent = JSON.parse(decodeURIComponent(part.split('=')[1]));
			if (consent) {
				if (this.options.size === 0) {
					this.visible = !consent.hasOwnProperty('_all');
					if (consent._all) {
						this.acceptAllCallback();
					}
				} else {
					this.visible = false;
					for (const [id, state] of Object.entries(consent)) {
						this.options.set(id, {
							state: state,
						});
					}
				}
			}
		}

		for (const [id, callback] of Object.entries(callbacks)) {
			if (this.options.has(id)) {
				this.options.get(id).callback = callback;
			} else {
				this.options.set(id, {
					callback: callback,
				});
				this.visible = true;
			}
		}

		if (!this.visible && this.options.size > 0) {
			let allSet = true;
			for (const option of this.options.values()) {
				if (!option.state) {
					allSet = false;
				} else if (option.callback) {
					option.callback();
				}
			}
			if (allSet && this.acceptAllCallback) {
				this.acceptAllCallback();
			}
		}

		this.element = document.querySelector('.lpcPrivacyConsent');
		if (this.element && this.visible) {
			this.show();
		} else {
			window.addEventListener('DOMContentLoaded', () => {
				this.element = document.querySelector('.lpcPrivacyConsent');
				if (this.visible) {
					this.show();
				}
			});
		}
	}

	show() {
		if (!this.initialized) {
			this.init();
		}
		this.visible = true;
		if (this.element) {
			this.element.classList.add('open');
		}
	}

	hide() {
		this.visible = false;
		if (this.element) {
			this.element.classList.remove('open');
		}
	}

	init() {
		if (!this.element) return;
		const acceptAllButton = this.element.querySelector('button.acceptAll');
		if (acceptAllButton) {
			acceptAllButton.addEventListener('click', (event) => {
				event.preventDefault();
				this.save(true);
				this.hide();
			});
		}
		const dismissAllButton = this.element.querySelector('button.dismissAll');
		if (dismissAllButton) {
			dismissAllButton.addEventListener('click', (event) => {
				event.preventDefault();
				this.save(false);
				this.hide();
			});
		}
		const form = this.element.querySelector('form');
		form.addEventListener('submit', (event) => {
			event.preventDefault();
			this.save();
			this.hide();
		});
		for (const input of form.elements) {
			if (input.name.startsWith('consent-')) {
				const id = input.name.substring(8);
				if (this.options.has(id) && this.options[id].hasOwnProperty(state)) {
					input.checked = this.options[id].state;
					this.options.get(id).checkbox = input;
				} else {
					this.options.set(id, {
						checkbox: input,
					});
				}
			}
		}
		this.initialized = true;
	}

	save(all = null) {
		const consent = {};
		for (const [id, option] of this.options.entries()) {
			if (all !== null) {
				consent[id] = all;
			} else if (option.checkbox) {
				consent[id] = option.checkbox.checked;
			}
		}
		if (this.options.size === 0) {
			consent._all = all;
		}
		document.cookie = "lpcPrivacyConsent=" + encodeURIComponent(JSON.stringify(consent)) + ";path=/;max-age=10368000";
		for (const [id, option] of this.options.entries()) {
			if (consent[id] && option.callback) {
				option.callback();
			}
		}
		if (all === true && this.acceptAllCallback) {
			this.acceptAllCallback();
		}
		if (all === false && this.dismissAllCallback) {
			this.dismissAllCallback();
		}
	}
}
