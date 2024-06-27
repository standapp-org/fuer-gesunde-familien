<?php
namespace LPC\LpcBase\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class ContentElementViewHelper extends AbstractViewHelper
{
	protected ContentObjectRenderer $cObj;
	protected $escapeOutput = false;

	public function __construct(ConfigurationManagerInterface $configurationManager) {
		$this->cObj = $configurationManager->getContentObject()
			?? GeneralUtility::makeInstance(ContentObjectRenderer::class);
	}

	public function initializeArguments(): void {
		$this->registerArgument('uid', 'int', 'UID of any content element', true);
	}

	public function render(): string {
		$records = $this->cObj->getContentObject('RECORDS');
		return $records === null ? '' : $records->render([
			'tables' => 'tt_content',
			'source' => $this->arguments['uid'],
			'dontCheckPid' => 1
		]);
	}
}
