<?php
namespace LPC\LpcBase\Routing;

class GeneratedAliasMapper extends AbstractGeneratedAliasMapper
{
	use SanitizePathSegmentTrait;

	private $routeFieldName;

	public function __construct(array $settings) {
		parent::__construct($settings);
		$this->routeFieldName = $settings['routeFieldName'];
	}

	protected function generatePathSegment(string $value): ?string {
		$result = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
			->getConnectionForTable($this->tableName)
			->select(
				[$this->routeFieldName],
				$this->tableName,
				['uid' => $value]
			)
			->fetch();

		return empty($result[$this->routeFieldName])
			? null
			: $this->sanitizePathSegment($result[$this->routeFieldName]);
	}
}
