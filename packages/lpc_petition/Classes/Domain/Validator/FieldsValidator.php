<?php
namespace LPC\LpcPetition\Domain\Validator;

use LPC\LpcPetition\Domain\Model\Field;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class FieldsValidator extends AbstractValidator
{
	/**
	 * @param array<string, Field> $fields
	 */
	public function __construct(
		private array $fields,
	) {}

	/**
	 * @param mixed $value
	 */
	protected function isValid($value): void {
		foreach($this->fields as $property => $field) {
			$this->checkProperty($property, $field, $value);
		}
	}

	/**
	 * @param mixed $object
	 */
	protected function checkProperty(string $property, Field $field, $object): void {
		$val = ObjectAccess::getPropertyPath($object, $property);

		$validEmptyValue = false;
		if(in_array($field->getType(), ['select', 'radios', 'checkboxes'])) {
			$validOptions = array_keys($field->getOptions());
			foreach($field->getType() === 'checkboxes' ? $val : [$val] as $v) {
				if(!in_array($v, $validOptions) && !$this->isEmpty($v)) {
					$this->result->forProperty($property)->addError(new Error(
						LocalizationUtility::translate('invalidOptionError', 'LpcPetition') ?? 'invalid option',
						1491208096
					));
				} else {
					$validEmptyValue = true;
				}
			}
		}

		if(!$validEmptyValue && $this->isEmpty($val) && $field->getMandatory()) {
			$this->result->forProperty($property)->addError(new Error(
				LocalizationUtility::translate('mandatoryFieldError','LpcPetition') ?? 'missing mandatory field',
				1491208096
			));
		}
	}
}
