<?php
namespace LPC\Captcha\Answer;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class DataValidator extends AbstractValidator
{
	public function isValid(mixed $captchaAnswerData): void {
		if(!is_array($captchaAnswerData) || !CaptchaAnswer::isDataValid($captchaAnswerData)) {
			$this->result->addError(new \TYPO3\CMS\Extbase\Validation\Error(
				\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('wrongCaptchaAnswer','LpcCaptcha') ?? 'wrong captcha answer',
				1535527600
			));
		}
	}
}
