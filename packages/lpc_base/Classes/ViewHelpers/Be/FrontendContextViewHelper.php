<?php
namespace LPC\LpcBase\ViewHelpers\Be;

class FrontendContextViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
	protected $escapeOutput = false;

	/**
	 * @var \LPC\LpcBase\Configuration\FrontendUriBuilder
	 */
	protected $frontendUriBuilder;

	public function injectFrontendUriBuilder(\LPC\LpcBase\Configuration\FrontendUriBuilder $frontendUriBuilder) {
		$this->frontendUriBuilder = $frontendUriBuilder;
	}

	public function render()
	{
		$controllerContext = $this->renderingContext->getControllerContext();
		$outerUriBuilder = $controllerContext->getUriBuilder();
		$controllerContext->setUriBuilder($this->frontendUriBuilder);
		$content = $this->renderChildren();
		$controllerContext->setUriBuilder($outerUriBuilder);
		return $content;
	}
}
