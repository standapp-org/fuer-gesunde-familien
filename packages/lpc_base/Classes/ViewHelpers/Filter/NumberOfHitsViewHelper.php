<?php
namespace LPC\LpcBase\ViewHelpers\Filter;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class NumberOfHitsViewHelper extends AbstractTagBasedViewHelper
{
	protected $tagName = 'span';

	public function initializeArguments(): void {
		$this->registerUniversalTagAttributes();
		$this->registerArgument('template', 'string', 'the template string to use', false, '[start] – [end] / [total]');
	}

	public function render(): string {
		$list = $this->viewHelperVariableContainer->get(ListViewHelper::class, 'filterlist');
		$class = $list->addNumberDisplay($this->arguments['template']);
		$classes = $this->tag->getAttribute('class');
		$classes = $classes . (!empty($classes) ? ' ' : '') . $class;
		$this->tag->addAttribute('class', $class);
		$this->tag->forceClosingTag(true);
		return $this->tag->render();
	}
}
