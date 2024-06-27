<?php
namespace LPC\LpcBase\ViewHelpers\Be;

use LPC\LpcBase\Configuration\FrontendUriBuilder;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class FeLinkViewHelper extends AbstractTagBasedViewHelper
{
	protected $tagName = 'a';

	protected FrontendUriBuilder $uriBuilder;

	public function injectUriBuilder(FrontendUriBuilder $uriBuilder): void {
		$this->uriBuilder = $uriBuilder;
	}

	public function initializeArguments(): void {
		$this->registerArgument('pageUid','int','',true);
		$this->registerArgument('absolute','boolean','',false,true);
		$this->registerArgument('action','string','');
		$this->registerArgument('controller','string','');
		$this->registerArgument('pluginName','string','');
		$this->registerArgument('extensionName','string','');
		$this->registerArgument('arguments','array','',false,[]);
		$this->registerArgument('target','string','',false,'_blank');
		$this->registerArgument('language', 'int', '');
	}

	public function render(): string {
		$pageUid = $this->arguments['pageUid'];
		$this->uriBuilder
			->reset()
			->setTargetPageUid($this->arguments['pageUid'])
			->setCreateAbsoluteUri($this->arguments['absolute']);
		if ($this->hasArgument('language')) {
			$this->uriBuilder->setLanguage($this->arguments['language']);
		}
		if($this->hasArgument('action')) {
			$uri = $this->uriBuilder->uriFor(
				$this->arguments['action'],
				$this->arguments['arguments'],
				$this->arguments['controller'],
				$this->arguments['extensionName'],
				$this->arguments['pluginName']
			);
		} else {
			$uri = $this->uriBuilder->build();
		}
		$this->tag->setContent($this->renderChildren() ?: $uri);
		$this->tag->addAttribute('href',$uri);
		$this->tag->addAttribute('target',$this->arguments['target']);
		return $this->tag->render();
	}
}
