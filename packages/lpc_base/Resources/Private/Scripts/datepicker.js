
import flatpickr from "flatpickr";

import "flatpickr/dist/flatpickr.css";

const locales = {};

window.addEventListener('DOMContentLoaded', async () => {
	for (const element of document.getElementsByClassName('lpcDatepicker')) {
		if (element.classList.contains('flatpickr-mobile')) continue;

		const options = JSON.parse(element.dataset.options);

		if (options.forceFormat) {
			delete options.forceFormat;
			if (options.dateFormat) {
				element.addEventListener('change', () => {
					if (element.value) {
						const date = flatpickr.parseDate(element.value, options.dateFormat);
						element.value = date ? flatpickr.formatDate(date, options.dateFormat) : '';
					}
				});
			}
		}

		if (options.locale) {
			if (locales.hasOwnProperty(options.locale)) {
				options.locale = locales[options.locale];
			} else {
				let locale;
				switch (options.locale) {
					case 'de':
						locale = (await import('flatpickr/dist/l10n/de.js')).default.de;
						break;
					case 'fr':
						locale = (await import('flatpickr/dist/l10n/fr.js')).default.fr;
						break;
					case 'it':
						locale = (await import('flatpickr/dist/l10n/it.js')).default.it;
						break;
					case 'en':
						delete options.locale;
						break;
					default:
						locale = (await import('https://npmcdn.com/flatpickr/dist/l10n/'+options.locale+'.js')).default[options.locale];
				}
				if (locale) {
					locales[options.locale] = locale;
					options.locale = locale;
				}
			}
		}

		flatpickr(element, options);
	}
});
