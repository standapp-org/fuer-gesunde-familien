<?php
namespace LPC\LpcBase\ViewHelpers\Filter;

class ValueViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
	protected $escapeOutput = false;

	public function initializeArguments(): void {
		$this->registerArgument('name','string','',true);
		$this->registerArgument('value','string','',false);
		$this->registerArgument('label','string','',false);
		$this->registerArgument('output','boolean','output the tag content besides using it as value or label',false,false);
		$this->registerArgument('ignore','boolean','ignore the filter `name` for this item.',false,false);
	}

	public function render(): string {
		$list = $this->viewHelperVariableContainer->get(ListViewHelper::class, 'filterlist');

		$content = $this->renderChildren() ?? '';

		if($this->hasArgument('value')) {
			$value = $this->arguments['value'];
			$label = $this->arguments['label'] ?: trim(strip_tags($content)) ?: null;
		} else {
			$value = trim(strip_tags($content));
			$label = $this->arguments['label'];
		}

		if($this->arguments['ignore']) {
			$list->ignoreValue($this->arguments['name']);
		} else if($value) {
			$list->addValue(
				$this->arguments['name'],
				html_entity_decode($value),
				$label
			);
		}

		return $this->arguments['output'] ? $content : '';
	}
}
