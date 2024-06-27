<?php
namespace LPC\LpcBase\ViewHelpers\Filter;

class CaptionViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
{
	public function initializeArguments(): void {
		parent::initializeArguments();
		$this->registerUniversalTagAttributes();
	}

	public function render(): string {
		$class = $this->tag->getAttribute('class');
		$class = $class.(!empty($class) ? ' ' : '').'lpcFilterCaption';
		$this->tag->addAttribute('class',$class);

		$list = $this->viewHelperVariableContainer->get(ListViewHelper::class, 'filterlist');
		$list->addCaption();

		$this->tag->setContent($this->renderChildren());
		return $this->tag->render();
	}
}
