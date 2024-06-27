<?php
namespace LPC\LpcBase\ViewHelpers\Format;

class Html2textViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
	/**
	 * @var \LPC\LpcBase\Service\Html2TextService
	 */
	protected $html2text;

	public function injectHtml2TextService(\LPC\LpcBase\Service\Html2TextService $html2text) {
		$this->html2text = $html2text;
	}

	protected $escapingInterceptorEnabled = false;

	public function render() {
		$this->html2text->setBaseUrl($this->renderingContext->getControllerContext()->getRequest()->getBaseUri());
		$parts = preg_split('/(<!--DMAILER_SECTION_BOUNDARY_[A-Z]*-->\\n?)/',$this->renderChildren(),null,PREG_SPLIT_DELIM_CAPTURE);
		for($i = 0; $i < count($parts); $i += 2) {
			$this->html2text->setHtml($parts[$i]);
			$parts[$i] = $this->html2text->getText();
		}
		return implode('',$parts);
	}
}
