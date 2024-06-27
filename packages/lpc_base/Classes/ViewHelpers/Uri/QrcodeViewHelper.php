<?php
namespace LPC\LpcBase\ViewHelpers\Uri;

class QrcodeViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
	use \LPC\LpcBase\ViewHelpers\QrcodeViewHelperTrait;

	public function initializeArguments() {
		$this->registerQrcodeArguments();
	}

	public function render() {
		$qr = $this->renderQrcode();
		return 'data:image/'.$this->arguments['format'].';base64,'.base64_encode($qr);
	}
}
