<?php
namespace LPC\LpcBase\Hook;

use TYPO3\CMS\Core\DataHandling\DataHandler;

class FixEmptyStoragePidHook
{
	/**
	 * @param array<string, mixed> $fieldArray
	 */
	public function processDatamap_postProcessFieldArray(string $status, string $table, $id, array &$fieldArray, DataHandler $pObj): void {
		if($table == 'tt_content' && isset($fieldArray['pi_flexform'])) {
			$fieldArray['pi_flexform'] = preg_replace('#<field index="persistence.storagePid">\s*<value index="vDEF">\s*</value>\s*</field>#','',$fieldArray['pi_flexform']);
		}
	}
}
