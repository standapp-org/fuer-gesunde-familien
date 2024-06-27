<?php
namespace LPC\Captcha\ViewHelpers;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ReCaptchaViewHelper extends AbstractCaptchaViewHelper
{
	protected $tagName = 'input';

	public function initializeArguments(): void {
		parent::initializeArguments();
		$this->registerArgument('theme','string','dark | light',false,'light');
		$this->registerArgument('size','string','compact | normal | invisible',false,'normal');
		$this->registerArgument('badge','string','bottomright | bottomleft | inline',false,'bottomright');
	}

	public function render(): string {
		GeneralUtility::makeInstance(PageRenderer::class)->addJsLibrary('recaptcha', 'https://www.google.com/recaptcha/api.js');
		$config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('lpc_captcha');
		$id = uniqid();
		$data = [
			'theme' => $this->arguments['theme'],
			'size' => $this->arguments['size'],
			'badge' => $this->arguments['badge'],
			'callback' => 'recaptchaComplete'.$id,
			'sitekey' => $config[$this->arguments['size'] == 'invisible' ? 'recaptchaInvisible' : 'recaptcha']['sitekey'],
		];
		$out = '<div class="g-recaptcha"';
		foreach($data as $name => $value) {
			$out .= ' data-'.$name.'="'.$value.'"';
		}
		$out .= '></div>';

		$out .= "<script>\n";
		$out .= "window.addEventListener('DOMContentLoaded', function() {\n";
		$out .= "window.recaptchaComplete$id = (function(input) {\n";
		if($this->arguments['size'] == 'invisible') {
			$out .= <<<'JS'
				input.form.addEventListener('submit', function() {
					event.preventDefault();
					grecaptcha.execute();
				}, {once: true});
				return function(result) {
					input.value = result;
					input.form.submit();
				}
JS;
		} else {
			$out .= "return function(result) { input.value = result; }\n";
		}
		$out .= "})(document.getElementById('recaptchaInput$id'));})\n";
		$out .= "</script>\n";

		$this->setAnswerFieldName('answer');

		$this->tag->addAttribute('type','hidden');
		$this->tag->addAttribute('id','recaptchaInput'.$id);
		$out .= $this->tag->render();
		$out .= $this->renderTypeAndHmacFields($this->arguments['size'] == 'invisible' ? 'recaptchaInvisible' : 'recaptcha');

		return $out;
	}
}
