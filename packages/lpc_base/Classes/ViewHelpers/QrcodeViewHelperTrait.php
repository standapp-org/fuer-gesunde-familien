<?php
namespace LPC\LpcBase\ViewHelpers;

trait QrcodeViewHelperTrait
{
	public function registerQrcodeArguments() {
		$this->registerArgument('content', 'string', 'if not given, the tag content is used', false);
		$this->registerArgument('size', 'int', '', false, 300);
		$this->registerArgument('margin', 'int', '', false, 4);
		$this->registerArgument('format', 'string', 'eps, svg or any format valid for Imagick::setImageFormat', false, 'png');
		$this->registerArgument('color', 'string', 'foreground color in html notation #rrggbb or #rrggbbaa', false, '#000000');
		$this->registerArgument('backgroundColor', 'string', 'background color in html notation #rrggbb or #rrggbbaa', false, '#ffffff00');
	}

	protected function renderQrcode() {
		$content = $this->arguments['content'] ?: $this->renderChildren();

		$renderer = new \BaconQrCode\Renderer\ImageRenderer(
			new \BaconQrCode\Renderer\RendererStyle\RendererStyle(
				$this->arguments['size'],
				$this->arguments['margin'],
				null,
				null,
				\BaconQrCode\Renderer\RendererStyle\Fill::uniformColor(
					$this->parseColor($this->arguments['backgroundColor']),
					$this->parseColor($this->arguments['color'])
				)
			),
			$this->createRendererBackend($this->arguments['format'])
		);

		$writer = new \BaconQrCode\Writer($renderer);
		return $writer->writeString($content);
	}

	private function parseColor(string $html): \BaconQrCode\Renderer\Color\ColorInterface {
		$colorArray = array_map('hexdec', str_split(substr($html, 1), 2));
		$color = new \BaconQrCode\Renderer\Color\Rgb(...array_slice($colorArray, 0, 3));
		if(isset($colorArray[3])) {
			$color = new \BaconQrCode\Renderer\Color\Alpha($colorArray[3]*100/255, $color);
		}
		return $color;
	}

	private function createRendererBackend($format) {
		switch($format) {
			case 'svg':
				return new \BaconQrCode\Renderer\Image\SvgImageBackEnd;
			case 'eps':
				return new \BaconQrCode\Renderer\Image\EpsImageBackEnd;
			default:
				return new \BaconQrCode\Renderer\Image\ImagickImageBackEnd($format);
		}
	}
}
