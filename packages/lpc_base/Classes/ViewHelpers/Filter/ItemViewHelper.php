<?php
namespace LPC\LpcBase\ViewHelpers\Filter;

class ItemViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
{
	protected $tagName = 'div';

	public function initializeArguments(): void {
		parent::initializeArguments();
		$this->registerUniversalTagAttributes();
	}

	public function render(): string {
		$class = $this->tag->getAttribute('class');
		$class = $class.(!empty($class) ? ' ' : '').'lpcFilterItem';
		$this->tag->addAttribute('class',$class);

		$list = $this->viewHelperVariableContainer->get(ListViewHelper::class, 'filterlist');
		$list->startItem();
		$this->tag->setContent($this->renderChildren());
		$list->endItem();

		return $this->tag->render();
	}
}
