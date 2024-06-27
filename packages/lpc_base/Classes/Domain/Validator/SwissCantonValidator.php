<?php
namespace LPC\LpcBase\Domain\Validator;

use LPC\LpcBase\Utility\SwissCantonUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class SwissCantonValidator extends AbstractValidator
{
	public function isValid($canton): void {
		if(!is_string($canton) || ($canton !== '' && !in_array($canton, SwissCantonUtility::CANTONS, true))) {
			$this->addError(
				$this->translateErrorMessage(
					'swissCantonValidator.notvalid',
					'LpcBase'
				),
				1600163890
			);
		}
	}
}
