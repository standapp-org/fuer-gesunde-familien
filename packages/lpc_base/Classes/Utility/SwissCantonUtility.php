<?php
namespace LPC\LpcBase\Utility;

class SwissCantonUtility
{
	public const CANTONS = ['AG','AI','AR','BE','BL','BS','FR','GE','GL','GR','JU','LU','NE','NW','OW','SG','SH','SO','SZ','TG','TI','UR','VD','VS','ZG','ZH'];

	/**
	 * @return \Generator<string, string>
	 */
	public static function getSortedByName(): \Generator {
		$cantons = new class() extends \SplHeap {
			private $collator;
			public function __construct() {
				$this->collator = new \Collator(setlocale(LC_COLLATE, '') ?: null);
			}
			public function compare($a,$b) {
				return -$this->collator->compare($a[1],$b[1]);
			}
		};
		foreach(self::CANTONS as $canton) {
			$cantons->insert([
				$canton,
				\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:lpc_base/Resources/Private/Language/cantons.xlf:'.$canton),
			]);
		}
		foreach($cantons as [$canton, $name]) {
			yield $canton => $name;
		}
	}

	/**
	 * @return list<array{0: string, 1: string}>
	 */
	public static function getTCAItems(): array {
		$items = [];
		foreach(self::CANTONS as $canton) {
			$items[] = ['LLL:EXT:lpc_base/Resources/Private/Language/cantons.xlf:'.$canton, $canton];
		}
		return $items;
	}
}
