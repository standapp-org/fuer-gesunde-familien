<?php
namespace LPC\LpcBase\ViewHelpers;

/**
 * View helper
 */
class SwissQuotationViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
	/**
	 * @param string $value
	 */
	public function render($value = null)
	{
		if ($value == null) {
			$value = $this->renderChildren();
		}

		$r = $a = preg_replace('/( |\n|>)"/', "$1&laquo;", $value);
		$r = preg_replace('/"(.|\n\<)/', "&raquo;$1", $r);

		return $r;
	}
}
