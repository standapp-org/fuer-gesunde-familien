<?php
namespace LPC\LpcBase\ViewHelpers;

class OpenGraphViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{

	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerArgument('property','string','Meta property name');
		$this->registerArgument('content','string','Meta content');
		$this->registerArgument('replace', 'boolean', 'override a previous set meta tag, if one exists.', false, true);
	}


	/**
	 * @param string $property
	 * @param mixed $content
	 */
	public function render() {
		$property = $this->arguments['property'];
		$content = $this->arguments['content'];
		\LPC\LpcBase\Utility\PluginUtility::addOpenGraphTag($property, $content, $this->arguments['replace']);
	}
}
