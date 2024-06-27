<?php
namespace LPC\LpcBase\ViewHelpers\Date;

use Lpc\LpcBase\Utility\Cast\DateCastUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Get the parts of two dates who are different.
 * Return one of three values: d,m,y
 *
 * @deprecated
 * only used by previous version of lpc_events (until 7e4bb52a2923e4d5a0d27ef9dc7b9a7d62d8e2ed 2018-02-01)
 *
 * @extensionScannerIgnoreFile
 *
 * @author Michael Hadorn <michael.hadorn@laupercomputing.ch>
 * @package TYPO3
 * @subpackage
 * @package Lpc\LpcBase\ViewHelper
 */
class ChangedPartsViewHelper extends AbstractViewHelper {


	public function initializeArguments() {
		$this->registerArgument('date1', 'mixed', 'Date 1', true);
		$this->registerArgument('date2', 'mixed', 'Date 2', true);
	}

	/**
	 * Render
	 * Get the parts of two dates who has changed (d, m, y) -> later include prev.
	 * Working example in lpc_events -> ListEntry.html
	 * @return string
	 */
	public function render() {
		$date1 = $this->arguments['date1'];
		$date2 = $this->arguments['date2'];

		$return = DateCastUtility::getChangedDateParts($date1, $date2);

		return $return;
	}
	
}
