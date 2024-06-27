<?php
namespace LPC\LpcBase\Routing;

abstract class AbstractGeneratedAliasMapper implements \TYPO3\CMS\Core\Routing\Aspect\PersistedMappableAspectInterface, \TYPO3\CMS\Core\Routing\Aspect\StaticMappableAspectInterface
{
	protected $tableName;
	private $pathSegmentFieldName;

	public function __construct(array $settings) {
		$this->tableName = $settings['tableName'];
		$this->pathSegmentFieldName = $settings['pathSegmentFieldName'];
	}

	/**
	 * @param string $value
	 * @return string|null
	 */
	public function generate(string $value): ?string {
		$db = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
			->getConnectionForTable($this->tableName);
		$result = $db->select(
			[$this->pathSegmentFieldName],
			$this->tableName,
			['uid' => $value]
		)->fetch();
		if(!$result) {
			return $value;
		}
		if(empty($result[$this->pathSegmentFieldName])) {
			$pathSegmentWithSuffix = $pathSegment = $this->generatePathSegment($value);
			if(empty($pathSegment) || $pathSegment === '-') {
				return $value;
			}
			$suffix = 0;
			while($db->count('*', $this->tableName, [$this->pathSegmentFieldName => $pathSegmentWithSuffix]) > 0) {
				$suffix++;
				$pathSegmentWithSuffix = $pathSegment.'-'.$suffix;
			}
			$db->update(
				$this->tableName,
				[$this->pathSegmentFieldName => $pathSegmentWithSuffix],
				['uid' => $value]
			);
			return $pathSegmentWithSuffix;
		} else {
			return $result[$this->pathSegmentFieldName];
		}
	}

	abstract protected function generatePathSegment(string $value): ?string;

	/**
	 * @param string $value
	 * @return string|null
	 */
	public function resolve(string $value): ?string {
		if(ctype_digit($value)) {
			return $value;
		}

		$result = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
			->getConnectionForTable($this->tableName)
			->select(
				['uid'],
				$this->tableName,
				[$this->pathSegmentFieldName => $value]
			)
			->fetch();
		if(!$result) {
			return null;
		}
		return $result['uid'];
	}
}
