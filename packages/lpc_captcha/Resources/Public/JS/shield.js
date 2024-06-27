(() => {
	for (const shieldInput of document.querySelectorAll('input.lpcCaptchaShield')) {
		let closeEventStream;

		let eventStream = new ReadableStream({
			type: 'bytes',
			start(controller) {
				let time = +Date.now();
				const append = (key) => {
					const now = Date.now();
					let interval = now-time;
					time += interval;
					let bytes = [];
					while (interval > 0) {
						bytes.push(interval & 0xff);
						interval >>= 8;
					}
					bytes.unshift(key.charCodeAt(0), bytes.length);
					let buffer = new Uint8Array(bytes);
					controller.enqueue(buffer);
				}

				const abort = new AbortController();
				let closed = false;
				for (const [key, event] of [['m', 'mousemove'], ['c', 'mousedown'], ['C', 'mouseup'], ['k', 'keydown'], ['K', 'keyup'], ['s', 'scroll'], ['S', 'scrollend'], ['t', 'touchstart'], ['T', 'touchend'], ['w', 'wheel']]) {
					window.addEventListener(event, () => append(key), {signal: abort.signal});
				}

				const observer = new IntersectionObserver((entries) => append(entries[0].isIntersecting ? 'I' : 'i'));
				observer.observe(shieldInput.form);

				closeEventStream = () => {
					if (closed == false) {
						abort.abort();
						observer.unobserve(shieldInput.form);
						controller.close();
						closed = true;
					}
				};
			}
		});

		let journal = new Promise((resolve, reject) => {
			let chunks = [];
			eventStream.pipeThrough(new CompressionStream('gzip')).pipeTo(new WritableStream({
				write(chunk, controller) {
					chunks.push(chunk);
				},
				close(controller) {
					console.log(chunks);
					resolve(new Blob(chunks));
				},
				abort(reason) {
					reject(reason);
				}
			}));
		});

		let answer = shieldInput.value;
		shieldInput.value = '';
		shieldInput.form.addEventListener('submit', async (event) => {
			if (shieldInput.value) return;

			event.preventDefault();
			const data = new FormData(shieldInput.form, event.submitter);
			for (const [name, value] of data.entries()) {
				if (value instanceof Blob) {
					data.delete(name);
				}
			}

			closeEventStream();
			data.append('lpcCaptchaShieldValue', await journal);
			data.append('lpcCaptchaShieldAnswer', answer);
			const response = await fetch('/lpc-captcha-shield-check', {
				method: 'POST',
				body: data,
			});
			if (response.ok) {
				shieldInput.value = await response.text();
				shieldInput.form.submit();
			} else {
				const popup = document.createElement('dialog');
				const div = document.createElement('div');
				popup.append(div);
				const shadow = div.attachShadow({mode: 'closed'});
				const form = document.createElement('form');
				let errorMsg = null;
				form.addEventListener('submit', async (event) => {
					if (errorMsg !== null) {
						errorMsg.remove();
					}
					event.preventDefault();
					const data = new FormData(form);
					data.append('lpcCaptchaShieldAnswer', answer);
					const response = await fetch ('/lpc-captcha-shield-check', {
						method: 'POST',
						body: data,
					});
					if (response.ok) {
						shieldInput.value = await response.text();
						shieldInput.form.submit();
					} else {
						if (errorMsg === null) {
							errorMsg = document.createElement('p');
							errorMsg.style.color = 'red';
						}
						errorMsg.textContent = await response.text();
						form.prepend(errorMsg);
					}
				});
				form.innerHTML = await response.text();
				shadow.append(form);
				document.body.append(popup);
				popup.showModal();
			}
		});
	}
})();
