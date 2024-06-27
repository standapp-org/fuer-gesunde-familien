<?php
namespace LPC\LpcBase\ViewHelpers\Filter;

class ButtonsViewHelper extends AbstractControlViewHelper
{
	public function initializeArguments(): void {
		parent::initializeArguments();
		$this->registerUniversalTagAttributes();
		$this->registerCommonControlArguments();
		$this->registerArgument('multiple','boolean','allow multiple options to be selected at once',false,false);
	}

	public function renderControl(array $options, string $id): string {
		$content = '<span class="label">'.$this->getLabel().'</span>';

		$content .= '<div class="buttons">';
		$content .= '<button class="all active">'.\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('all', 'LpcBase').'</button>';
		foreach($this->sortOptions($options) as $value => $label) {
			$content .= '<button value="'.htmlentities($value).'">'.($label ?: htmlentities($value)).'</button>';
		}
		$content .= '</div>';

		$this->tag->addAttribute('id', $id);
		$this->tag->addAttribute('class', ltrim($this->tag->getAttribute('class').' lpcFilterControl lpcFilterButtons', ' '));
		$this->tag->setContent($content);
		return $this->tag->render();
	}

	public function renderScript(string $callback, string $id): string {
		if($this->arguments['multiple']) {
			return <<<JS
				var buttons = document.getElementById('$id').querySelectorAll('button');
				var states = Array(buttons.length).fill(false);
				Array.prototype.forEach.call(buttons, function(button, i) {
					button.addEventListener('click', function(event) {
						event.preventDefault();
						if(i === 0) {
							if(states[0]) {
								return;
							}
							states.fill(false,1);
							states[0] = true;
						} else {
							states[i] = !states[i];
							states[0] = states.every(function(v,i) {return i == 0 || !v;});
						}
						$callback(Array.prototype.reduce.call(buttons, function(values, btn, j) {
							if(states[j]) {
								btn.classList.add('active');
								if(j !== 0) {
									values.push(btn.value);
								}
							} else {
								btn.classList.remove('active');
							}
							return values;
						}, []));
					});
				});
JS;
		} else {
			return <<<JS
				var buttons = document.getElementById('$id').querySelectorAll('button');
				Array.prototype.forEach.call(buttons, function(button) {
					button.addEventListener('click', function(event) {
						event.preventDefault();
						if(button.classList.contains('active')) {
							return;
						}
						Array.prototype.forEach.call(buttons, function(btn) {
							if(btn !== button) {
								btn.classList.remove('active');
							}
						});
						button.classList.add('active');
						$callback(button.value ? [button.value] : []);
					});
				});
JS;
		}
	}
}

