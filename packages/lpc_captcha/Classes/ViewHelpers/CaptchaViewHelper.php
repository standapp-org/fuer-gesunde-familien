<?php
namespace LPC\Captcha\ViewHelpers;

use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;

class CaptchaViewHelper extends AbstractFormFieldViewHelper
{
	public function initializeArguments(): void {
		parent::initializeArguments();
		$this->registerArgument('type', 'string', 'none | math | recaptcha | recaptchaInvisible', true);
		$this->registerArgument('showDummy', 'bool', 'don\'t use type="hidden" for dummy inputs', false, false);
	}

	public function render(): string {
		if(!empty($GLOBALS['TSFE']->fe_user->user['uid'])) {
			// logged in fe_users do not need to solve captchas
			$viewHelper = DummyViewHelper::class;
		} else {
			$viewHelper = [
				'none' => DummyViewHelper::class,
				'math' => MathViewHelper::class,
				'recaptcha' => ReCaptchaViewHelper::class,
				'recaptchaInvisible' => ReCaptchaViewHelper::class,
				'shield' => ShieldViewHelper::class,
			][$this->arguments['type']] ?? DummyViewHelper::class;
		}

		$arguments = $this->arguments;
		unset($arguments['type']);

		if($viewHelper === DummyViewHelper::class) {
			$arguments['hidden'] = $arguments['showDummy'] == false;
		}
		unset($arguments['showDummy']);

		if($this->arguments['type'] === 'recaptchaInvisible') {
			$arguments['size'] = 'invisible';
		}

		return $this->renderingContext->getViewHelperInvoker()->invoke($viewHelper, $arguments, $this->renderingContext);
	}
}
