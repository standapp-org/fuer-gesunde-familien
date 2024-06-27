<?php
namespace LPC\LpcBase\ViewHelpers\Filter;

class FulltextSearchViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
{
	protected $tagName = 'input';

	public function initializeArguments(): void {
		parent::initializeArguments();
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('placeholder', 'string', '', false);
		$this->registerTagAttribute('type', 'string', '', false, 'search');
	}

	public function render() {
		$this->tag->addAttribute(
			'id',
			$this->viewHelperVariableContainer
				->get(ListViewHelper::class, 'filterlist')
				->getFulltextSearchInputId()
		);

		return $this->tag->render();
	}
}

