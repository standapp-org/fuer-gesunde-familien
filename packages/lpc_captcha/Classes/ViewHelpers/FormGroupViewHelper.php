<?php
namespace LPC\Captcha\ViewHelpers;

use LPC\LpcBase\ViewHelpers\Form\GroupViewHelper;

class FormGroupViewHelper extends GroupViewHelper
{
	/**
	 * @var \TYPO3\CMS\Core\Page\PageRenderer
	 */
	public $pageRenderer;

	public function injectPageRenderer(\TYPO3\CMS\Core\Page\PageRenderer $pageRenderer): void {
		$this->pageRenderer = $pageRenderer;
	}

	public function initializeArguments(): void {
		parent::initializeArguments();
		$this->registerArgument('type', 'string', 'none | math | recaptcha | recaptchaInvisible', true);
		$this->registerArgument('name', 'name', 'name of the input tag', false, null);
		$this->registerArgument('property', 'property', 'property for generating input tag name', false, null);
	}

	public function render(): string {
		if($this->arguments['type'] === '' || $this->arguments['type'] === 'none') {
			$this->pageRenderer->addCssInlineBlock('dummyCaptchaGroup', '.lpcFormGroup.dummyCaptchaGroup { position: absolute; visibility: collapse; }');
			$this->tag->addAttribute('class', $this->arguments['class'] . (empty($this->arguments['class']) ? ' ' : '') . 'dummyCaptchaGroup');
		} else if($this->arguments['type'] === 'recaptchaInvisible' || $this->arguments['type'] === 'shield') {
			return $this->renderChildren();
		}
		return parent::render();
	}

	public function renderChildren() {
		$arguments = [
			'type' => $this->arguments['type'],
			'name' => $this->arguments['name'],
			'property' => $this->arguments['property'],
			'showDummy' => true,
		];

		return $this->renderingContext->getViewHelperInvoker()->invoke(CaptchaViewHelper::class, $arguments, $this->renderingContext);
	}
}
