<?php
namespace LPC\LpcBase\ViewHelpers\Date;

use Lpc\LpcBase\Utility\Cast\DateCastUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Get the date by week
 *
 * @deprecated
 * only used by previous version of lpc_prayer
 *
 * @extensionScannerIgnoreFile
 *
 * @author Michael Hadorn <michael.hadorn@laupercomputing.ch>
 * @package TYPO3
 * @subpackage
 * @package Lpc\LpcBase\ViewHelper
 */
class DateByWeekViewHelper extends AbstractViewHelper {


	public function initializeArguments() {
		$this->registerArgument('week', 'number', 'This is the calendar week', true);
		$this->registerArgument('year', 'number', 'This is the year', false, 0);
	}

	/**
	 * Render
	 *
	 * @return string
	 */
	public function render() {
		$week = $this->arguments['week'];
		$year = $this->arguments['year'];
		$return = DateCastUtility::getDateByWeekNumber($week, 0, $year, false, 20);

		return $return;
	}
	
}
