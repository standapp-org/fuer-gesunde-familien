window.addEventListener('DOMContentLoaded', function() {
	Array.prototype.forEach.call(document.querySelectorAll('.lpcFormFiles'), function(wrap) {
		var files = wrap.querySelector('.files');
		var newFilesCounter = 0;

		if(wrap.classList.contains('multi')) {
			multiFilePicker(wrap);
		} else {
			singleFilePicker(wrap);
		}

		function singleFilePicker(wrap) {
			var button = wrap.querySelector('.lpcUploadButton');

			function toggleFileInput() {
				if(Array.prototype.some.call(files.children, function(file) {
					return file.classList.contains('added');
				})) {
					button.style.display = 'none';
				} else {
					button.style.display = null;
				}
			}
			toggleFileInput();

			files.addEventListener('click', function(file) {
				var el = event.target;
				while(el.parentNode !== files) {
					el = el.parentNode;
				}
				if(el.classList.contains('provided')) {
					if(el.classList.contains('selected')) {
						el.classList.remove('selected');
						el.querySelector('input').disabled = true;
						toggleFileInput();
					} else {
						Array.prototype.forEach.call(files.children, function(other) {
							if(other.classList.contains('provided')) {
								if(el !== other) {
									other.classList.remove('selected');
									other.querySelector('input').disabled = true;
								}
							} else {
								other.remove();
								button.querySelector('input').value = '';
							}
						});
						el.classList.add('selected');
						el.querySelector('input').disabled = false;
						toggleFileInput();
					}
				} else {
					el.remove();
					button.querySelector('input').value = '';
					toggleFileInput();
				}
			});
			button.querySelector('input').addEventListener('change', function() {
				files.appendChild(createFileBox(this.files.item(0)));
				Array.prototype.forEach.call(files.children, function(other) {
					other.classList.remove('selected');
				});
				toggleFileInput();
			});
		}

		function multiFilePicker(wrap) {
			var input = wrap.querySelector('.lpcUploadButton input');
			var prevClone = null;

			function onFileSelect() {
				Array.prototype.forEach.call(input.files, function(file) {
					files.appendChild(createFileBox(file));
					newFilesCounter++;
				});
				if(prevClone) {
					prevClone.disabled = false;
				}
				prevClone = input;
				input = input.cloneNode();
				input.addEventListener('change', onFileSelect);
				prevClone.disabled = true;
				prevClone.parentNode.appendChild(input);
			}
			input.addEventListener('change', onFileSelect);

			var dragging = null;
			var placeholder = document.createElement('div');
			var offsetX, offsetY, startX, startY, rects;
			placeholder.classList.add('placeholder');
			var trash = wrap.querySelector('.trash');

			files.addEventListener('mousedown', dragStart);
			files.addEventListener('touchstart', dragStart);

			files.addEventListener('click', function(event) {
				var el = event.target;
				while(el.parentNode !== files) {
					el = el.parentNode;
				}
				if(!el.classList.contains('provided')) {
					return;
				}

				if(el.classList.contains('selected')) {
					el.classList.remove('selected');
					el.querySelector('input').disabled = true;
				} else {
					el.classList.add('selected');
					el.querySelector('input').disabled = false;
				}
			});

			function dragStart(event) {
				if(event.type == 'mousedown' && event.button != 0) return;

				var el = event.target;
				while(el.parentNode !== files) {
					el = el.parentNode;
				}
				if(el.classList.contains('provided') && !el.classList.contains('selected')) {
					return;
				}
				event.preventDefault();

				el.style.top = el.offsetTop+'px';
				el.style.left = el.offsetLeft+'px';
				el.style.position = 'absolute';
				el.style.cursor = 'grabbing';
				el.style.zIndex = 9999;
				el.classList.add('dragging');
				files.insertBefore(placeholder,el);
				dragging = el;
				if(event.type == 'mousedown') {
					offsetX = event.clientX;
					offsetY = event.clientY;
				} else {
					offsetX = event.touches[0].clientX;
					offsetY = event.touches[0].clientY;
				}
				startX = el.offsetLeft;
				startY = el.offsetTop;
				wrap.classList.add('dragging');
				if(event.type == 'mousedown') {
					window.addEventListener('mousemove', dragMove);
					window.addEventListener('mouseup', dragEnd);
				} else {
					window.addEventListener('touchmove', dragMove);
					window.addEventListener('touchend', dragEnd);
				}
			}

			function dragMove(event) {
				var x,y;
				if(event.type == "mousemove") {
					x = event.clientX;
					y = event.clientY;
				} else {
					x = event.touches[0].clientX;
					y = event.touches[0].clientY;
				}
				dragging.style.left = (startX+x-offsetX)+'px';
				dragging.style.top = (startY+y-offsetY)+'px';
				if(!inRect(placeholder.getBoundingClientRect(), x, y)) {
					if(inRect(files.getBoundingClientRect(), x, y)) {
						placeholder.remove();
						if(!Array.prototype.some.call(files.children, function(file) {
							if(file !== dragging && inRect(file.getBoundingClientRect(), x, y)) {
								files.insertBefore(placeholder, file);
								return true;
							}
							return false;
						})) {
							files.appendChild(placeholder);
						}
					} else {
						files.insertBefore(placeholder, dragging);
					}
					if(inRect(trash.getBoundingClientRect(), x, y)) {
						dragging.classList.add('trashing');
						trash.classList.add('trashing');
					} else {
						dragging.classList.remove('trashing');
						trash.classList.remove('trashing');
					}
				}
			}

			function dragEnd() {
				dragging.style.position = null;
				dragging.style.top = null;
				dragging.style.left = null;
				dragging.style.cursor = null;
				dragging.style.zIndex = null;
				if(dragging.classList.contains('trashing')) {
					if(dragging.classList.contains('provided')) {
						dragging.classList.remove('selected');
						dragging.querySelector('input').disabled = true;
						files.prepend(dragging);
					} else {
						dragging.remove();
					}
					placeholder.remove();
				} else {
					files.replaceChild(dragging, placeholder);
				}
				dragging = null;
				wrap.classList.remove('dragging');
				window.removeEventListener('mousemove', dragMove);
				window.removeEventListener('mouseup', dragEnd);
				window.removeEventListener('touchmove', dragMove);
				window.removeEventListener('touchend', dragEnd);
			}

			function inRect(rect, x, y) {
				return rect.left < x && rect.left+rect.width > x && rect.top < y && rect.top+rect.height > y;
			}
		}

		function createFileBox(file) {
			var fileEl = document.createElement('div');
			fileEl.classList.add('file');
			fileEl.classList.add('added');
			var imgEl = document.createElement('img');
			var tries = 0;
			imgEl.addEventListener('error', function() {
				tries++;
				var mimeParts = file.type.split('/', 2);
				if(tries == 1) {
					imgEl.src = '/typo3conf/ext/lpc_base/Resources/Public/Icons/Mime/'+mimeParts[0]+'-'+mimeParts[1]+'.svg';
				} else if(tries == 2) {
					imgEl.src = '/typo3conf/ext/lpc_base/Resources/Public/Icons/Mime/'+mimeParts[0]+'-'+mimeParts[1]+'.svg';
				} else if(tries == 3) {
					imgEl.src = '/typo3conf/ext/lpc_base/Resources/Public/Icons/Mime/unknown.svg';
				}
			});
			imgEl.src = URL.createObjectURL(file);
			var inputEl = document.createElement('input');
			inputEl.name = files.dataset.namespace+'[]';
			inputEl.type = 'hidden';
			inputEl.value = files.dataset.namespace+'[new'+newFilesCounter+']';
			fileEl.appendChild(imgEl);
			fileEl.appendChild(inputEl);
			return fileEl;
		}
	});
});
