<?php
namespace LPC\Captcha\ViewHelpers;

use TYPO3\CMS\Core\Context\SecurityAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MathViewHelper extends AbstractCaptchaViewHelper
{
	private const DEFAULT_FONT = 'EXT:lpc_captcha/Resources/Private/Fonts/PermanentMarker.ttf';

	protected $tagName = 'input';

	public function initializeArguments(): void {
		parent::initializeArguments();
		$this->registerArgument('fontsize','integer','The font size in pt',false,16);
		$this->registerArgument('fontfile','string','A ttf font file',false,null);
	}

	public function render(): string {
		$solution = rand(1, 20);
		$left = rand($solution - 10, 10);
		$right = $solution - $left;
		if ($left <= 0) {
			[$left, $right] = [$right, -$left];
			$operator = '-';
		} else if ($right < 0) {
			$right = -$right;
			$operator = '-';
		} else {
			$operator = '+';
		}
		$equation = $left.' '.$operator.' '.$right.' = ';

		$this->setAnswerFieldName('answer');
		$this->tag->addAttribute('required', 'required');
		$out = $this->renderEquation($equation, $this->arguments['fontfile'] ?? GeneralUtility::getFileAbsFileName(self::DEFAULT_FONT), $this->arguments['fontsize']);
		$out .= $this->tag->render();
		$out .= $this->renderTypeAndHmacFields('math', (string)$solution);

		return '<div class="lpcMathCaptcha lpcFormInputGroup">'.$out.'</div>';
	}

	private function renderEquation(string $equation, string $fontfile, int $fontsize): string {
		$bbox = imagettfbbox($fontsize, 0, $fontfile, $equation) ?: $this->throwLastError();
		$width = $bbox[2] - $bbox[0];
		$height = $bbox[1] - $bbox[7];
		$image = imagecreatetruecolor($width, $height) ?: $this->throwLastError();
		imagealphablending($image, false);
		$background = imagecolorallocate($image, 255, 255, 255);
		if ($background === false) $this->throwLastError();
		imagefill($image, 0, 0, $background);
		$black = imagecolorallocate($image, 0, 0, 0);
		if ($black === false) $this->throwLastError();
		imagettftext($image, $fontsize, 0, -(int)$bbox[0], -(int)$bbox[7], $black, $fontfile, $equation);
		$buffer = fopen('php://memory', 'w+b') ?: $this->throwLastError();
		imagepng($image, $buffer);
		imagedestroy($image);
		rewind($buffer);
		stream_filter_append($buffer, 'convert.base64-encode', STREAM_FILTER_READ);
		return '<img src="data:image/png;base64,'.stream_get_contents($buffer).'" width="'.$width.'" height="'.$height.'" class="lpcMatchCaptchaImage" />';
	}

	/**
	 * @return never
	 */
	private function throwLastError(): void {
		$error = error_get_last();
		throw $error === null
			? new \Exception('function failed but no error was reported')
			: new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
	}
}
