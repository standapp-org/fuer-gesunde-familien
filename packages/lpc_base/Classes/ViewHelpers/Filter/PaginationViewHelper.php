<?php
namespace LPC\LpcBase\ViewHelpers\Filter;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class PaginationViewHelper extends AbstractTagBasedViewHelper
{
	public function initializeArguments(): void {
		$this->registerUniversalTagAttributes();
		$this->registerArgument('itemsPerPage', 'int', 'items per page to show', true);
	}

	public function render(): string {
		$pageSize = $this->arguments['itemsPerPage'];
		if ($pageSize < 1) return '';

		$class = $this->tag->getAttribute('class');
		$class = $class.(!empty($class) ? ' ' : '').'lpcFilterPagination';
		$this->tag->addAttribute('class',$class);

		$list = $this->viewHelperVariableContainer->get(ListViewHelper::class, 'filterlist');
		$list->activatePagination($pageSize);

		$this->tag->forceClosingTag(true);
		return $this->tag->render();
	}
}
