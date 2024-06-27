<?php
namespace LPC\LpcBase\ViewHelpers\Form;

class RteViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper
{
	protected $tagName = 'textarea';

	/**
	 * @var \TYPO3\CMS\Core\Page\PageRenderer
	 */
	public $pageRenderer;

	public function injectPageRenderer(\TYPO3\CMS\Core\Page\PageRenderer $pageRenderer): void {
		$this->pageRenderer = $pageRenderer;
	}

	public function initializeArguments(): void {
		parent::initializeArguments();
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('rows','int','number of rows');
		$this->registerTagAttribute('cols','int','number of cols');
		$this->registerArgument('configFile','string','Path to custom config.js for ckeditor',false,'EXT:lpc_base/Resources/Public/JS/default_rte_config.js');
		$this->registerArgument('contentCss','string','Path to css for content',false,null);
		$this->registerArgument('inline','boolean','use inline editor',false,false);
	}

	public function render(): string {
		$this->registerFieldNameForFormTokenGeneration($this->getName());
		$this->pageRenderer->addJsLibrary('rte_ckeditor','EXT:rte_ckeditor/Resources/Public/JavaScript/Contrib/ckeditor.js');
		$configFile = '/'.\TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->arguments['configFile']));
		$contentCss = $this->arguments['contentCss'];
		if($contentCss === null) {
			$settings = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,'LpcBase');
			$contentCss = $settings['rteDefaultContentCSS'];
		}
		if($contentCss) {
			$contentCss = '/'.\TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($contentCss));
		}
		$config = [
			'customConfig' => $configFile,
			'stylesSet' => false,
			'contentsCss' => $contentCss,
		];
		if($this->arguments['inline'] == true) {
			$this->pageRenderer->addJsFooterInlineCode('lpcBaseRteInline',
'$(document).ready(function() {
	$(\'.lpcBaseRteInline\').each(function() {
		CKEDITOR.inline(this,$(this).data(\'rte-config\'));
	});
});');
		} else {
			$this->pageRenderer->addJsFooterInlineCode('lpcBaseRte',
'$(document).ready(function() {
	$(\'.lpcBaseRte\').each(function() {
		CKEDITOR.replace(this,$(this).data(\'rte-config\'));
	});
});');
		}
		$this->tag->forceClosingTag(true);
		$this->tag->addAttribute('name',$this->getName());
		$rteClass = $this->arguments['inline'] == true ? 'lpcBaseRteInline' : 'lpcBaseRte';
		if($this->tag->hasAttribute('class')) {
			$class = $this->tag->getAttribute('class');
			$this->tag->removeAttribute('class');
			$this->tag->addAttribute('class',$class.' '.$rteClass);
		} else {
			$this->tag->addAttribute('class',$rteClass);
		}
		$this->tag->addAttribute('data-rte-config',json_encode($config, JSON_THROW_ON_ERROR));
		$this->tag->setContent($this->getValueAttribute());
		return $this->tag->render();
	}
}
