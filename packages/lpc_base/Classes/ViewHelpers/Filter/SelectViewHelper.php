<?php
namespace LPC\LpcBase\ViewHelpers\Filter;

class SelectViewHelper extends AbstractControlViewHelper
{
	protected $tagName = 'select';

	public function initializeArguments(): void {
		parent::initializeArguments();
		$this->registerUniversalTagAttributes();
		$this->registerCommonControlArguments();
		$this->registerArgument('divider', 'boolean', '', false, true);
	}

	public function renderControl(array $options, string $id): string {
		$content = '<option value="">'.$this->getLabel().'</option>';
		if($this->arguments['divider']) {
			$content .= '<option disabled style="font-family:sans-serif;">&mdash;&mdash;&mdash;&mdash;&mdash;</option>';
		}

		foreach($this->sortOptions($options) as $value => $optionLabel) {
			$content .= '<option value="'.htmlentities($value).'">'.($optionLabel ?: htmlentities($value)).'</option>';
		}

		$this->tag->addAttribute('id', $id);
		$this->tag->addAttribute('class', ltrim($this->tag->getAttribute('class').' lpcFilterControl lpcFilterSelect', ' '));
		$this->tag->setContent($content);
		return $this->tag->render();
	}

	public function renderScript(string $callback, string $id): string {
		return "document.getElementById('".$id."').addEventListener('change', function() {
			".$callback."(this.value == '' ? [] : [this.value]);
		});";
	}
}
