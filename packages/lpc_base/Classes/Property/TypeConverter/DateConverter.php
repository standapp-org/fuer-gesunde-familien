<?php
namespace LPC\LpcBase\Property\TypeConverter;

use LPC\LpcBase\Domain\Type\Date;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;

class DateConverter extends AbstractTypeConverter
{
	protected $priority = 15;

	protected $sourceTypes = ['string', 'array'];

	protected $targetType = Date::class;

	/**
	 * @param array<mixed> $convertedChildProperties
	 */
	public function convertFrom(mixed $source, string $targetType, array $convertedChildProperties = [], PropertyMappingConfigurationInterface $configuration = null): ?Date {
		if(is_string($source)) {
			$dateString = $source;
			$dateFormat = 'Y-m-d';
		} else if(is_array($source)) {
			if (!isset($source['date'])) {
				throw new \Exception('Date value is passed by array but \'date\' key is missing.');
			}
			$dateString = $source['date'];
			$dateFormat = $source['dateFormat'] ?? 'Y-m-d';
		} else {
			throw new \Exception('Cannot parse value of type '.get_debug_type($source));
		}

		if ($dateString === '') {
			return null;
		}

		return Date::createFromFormat($dateFormat, $dateString);
	}
}
