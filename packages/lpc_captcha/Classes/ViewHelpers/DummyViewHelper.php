<?php
namespace LPC\Captcha\ViewHelpers;

class DummyViewHelper extends AbstractCaptchaViewHelper
{
	protected $tagName = 'input';

	public function initializeArguments(): void {
		parent::initializeArguments();
		$this->registerArgument('hidden', 'bool', 'use type=\'hidden\'', false, true);
	}

	public function render(): string {
		$fieldName = $this->getName().'[answer]';
		$this->registerFieldNameForFormTokenGeneration($fieldName);
		$this->tag->addAttribute('name', $fieldName);

		if (!empty($this->arguments['hidden'])) {
			$this->tag->addAttribute('type','hidden');
		}
		$this->tag->addAttribute('autocomplete', 'new-password');
		return $this->tag->render() . $this->renderTypeAndHmacFields('dummy', '');
	}
}
