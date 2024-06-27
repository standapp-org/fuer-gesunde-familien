<?php
namespace LPC\Captcha\ViewHelpers;

use LPC\LpcBase\ViewHelpers\Form\GroupViewHelper;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;

abstract class AbstractCaptchaViewHelper extends AbstractFormFieldViewHelper
{
	protected function renderHiddenField(string $name, string $value): string {
		$fieldName = $this->getName().'['.$name.']';
		$this->registerFieldNameForFormTokenGeneration($fieldName);
		return '<input type="hidden" name="'.$fieldName.'" value="'.$value.'" />';
	}

	protected function setAnswerFieldName(string $name): void {
		$fieldName = $this->getName().'['.$name.']';
		$this->registerFieldNameForFormTokenGeneration($fieldName);
		$this->tag->addAttribute('name', $fieldName);
		$formGroup = $this->viewHelperVariableContainer->get(GroupViewHelper::class, 'lpcFormGroup');
		if ($formGroup instanceof GroupViewHelper) {
			$formGroup->setPropertyPath($this->arguments['property'] ?? $this->arguments['name']);
		}
	}

	protected function renderTypeAndHmacFields(string $type, ?string $answer = null): string {
		$encryptionKey = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
		if (!$encryptionKey) {
			throw new \Exception('Encryption key was empty!');
		}
		$time = time();
		$hmac = base64_encode(pack('V', $time).hash_hmac('sha256', $type.$answer.$time, $encryptionKey, true));
		return $this->renderHiddenField('type', $type) . $this->renderHiddenField('hmac', $hmac);
	}
}
