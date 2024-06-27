<?php
namespace LPC\LpcBase\ViewHelpers\Form;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper;

class AjaxViewHelper extends FormViewHelper
{
	public function initializeArguments(): void {
		parent::initializeArguments();
		$this->registerArgument('uid', 'int', 'uid of the content element', false, null);
	}

	public function render(): string {
		$contentObject = GeneralUtility::makeInstance(ConfigurationManagerInterface::class)->getContentObject();
		if ($contentObject === null) {
			throw new \Exception('Ajax links can only be generated when rendering a content element.');
		}
		$this->arguments['additionalParams']['lpcajax'] = $this->arguments['uid'] ?? $contentObject->data['uid'];
		return parent::render();
	}
}
