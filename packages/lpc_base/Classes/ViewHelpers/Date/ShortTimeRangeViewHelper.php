<?php
namespace LPC\LpcBase\ViewHelpers\Date;

use Lpc\LpcBase\Utility\Cast\DateCastUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Use Lpc\LpcBase\Utility\Cast::shortTimeRange().
 * Displays a time range in short form (removes obsolete date parts)
 *
 * @deprecated
 * used anywhere at all?
 *
 * @extensionScannerIgnoreFile
 *
 * @author Michael Hadorn <michael.hadorn@laupercomputing.ch>
 * @package TYPO3
 * @subpackage
 * @package Lpc\LpcBase\ViewHelper
 */
class ShortTimeRangeViewHelper extends AbstractViewHelper {

	public function initializeArguments() {
		$this->registerArgument('from', 'mixed', 'from date', true);
		$this->registerArgument('to', 'mixed', 'to date', false);
		$this->registerArgument('forceMonth', 'boolean', 'always show month', false, true);
	}

	/**
	 * Render
	 *
	 * @return string
	 */
	public function render() {
		$fromDate = $this->arguments['from'];
		$toDate = $this->arguments['to'];
		$forceMonth = $this->arguments['forceMonth'];

		$return = DateCastUtility::shortTimeRange($fromDate, $toDate, $forceMonth);

		return $return;
	}
	
}
