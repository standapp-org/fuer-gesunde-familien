<?php
namespace LPC\LpcBase\ViewHelpers;

class QrcodeViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
{
	use QrcodeViewHelperTrait;

	protected $tagName = 'img';

	protected $escapeChildren = false;

	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
		$this->registerQrcodeArguments();
	}

	public function render() {
		$qr = $this->renderQrcode();

		if($this->arguments['format'] == 'svg') {
			$this->tag->setTagName('svg');
			$attributes = $this->tag->getAttributes();
			foreach(['xmlns','version','width','height','viewBox'] as $attr) {
				unset($attributes[$attr]);
			}
			$tagStart = '<svg ';
			foreach($attributes as $name => $value) {
				$tagStart .= $name.'="'.$value.'" ';
			}

			return preg_replace('/^<\?xml version="[\d\.]+" encoding="UTF-8"\?>\s*<svg /', $tagStart, $qr, 1);
		} else {
			$this->tag->addAttribute('src', 'data:image/'.$this->arguments['format'].';base64,'.base64_encode($qr));
			return $this->tag->render();
		}
	}
}
