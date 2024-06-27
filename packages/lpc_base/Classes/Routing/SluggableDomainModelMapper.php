<?php
namespace LPC\LpcBase\Routing;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

class SluggableDomainModelMapper extends AbstractGeneratedAliasMapper
{
	private $domainModelClassName;

	public function __construct(array $settings) {
		if(!isset($settings['tableName'])) {
			$settings['tableName'] = GeneralUtility::makeInstance(DataMapFactory::class)
				->buildDataMap($settings['domainModelClassName'])
				->getTableName();
		}
		parent::__construct($settings);
		$this->domainModelClassName = $settings['domainModelClassName'];
	}

	protected function generatePathSegment(string $value): ?string {
		$object = GeneralUtility::makeInstance(PersistenceManagerInterface::class)->getObjectByIdentifier($value, $this->domainModelClassName);

		if($object instanceof SluggableInterface) {
			return $object->getPathSegment();
		}

		return null;
	}
}
