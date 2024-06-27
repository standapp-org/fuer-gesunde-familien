<?php
namespace LPC\LpcBase\ViewHelpers\Iterator;

class TransposeViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
	public function initializeArguments() {
		$this->registerArgument('columns', 'int', 'number of columns to transpose into', true);
	}

	public function render() {
		$iterator = $this->renderChildren();
		if($iterator instanceof \Traversable) {
			$iterator = iterator_to_array($iterator);
		}
		$iterator = array_values($iterator);
		$total = count($iterator);

		$numColumns = $this->arguments['columns'];
		$columnLength = intdiv($total - 1, $numColumns) + 1;

		for($i = 0; $i < $total; $i++) {
			$col = $i % $numColumns;
			$row = intdiv($i, $numColumns);
			yield $iterator[$col * $columnLength + $row];
		}
	}
}
