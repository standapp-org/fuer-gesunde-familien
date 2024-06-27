<?php

namespace LPC\LpcBase\Utility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Created by PhpStorm.
 * User: Michael Hadorn
 * Date: 09.03.16
 * Time: 11:07
 */
class MailUtility implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @param $addresses
	 * @param string $delimiter
	 * @return array
	 */
	public static function getAddressesSplittedBySemikola($addresses, $delimiter = ';') {
		$address = GeneralUtility::trimExplode($delimiter, $addresses, true);

		// remove invalid mail addresses
		$address = array_filter($address, array('TYPO3\CMS\Core\Utility\GeneralUtility', 'validEmail'));
		return $address;
	}

}
