<?php
namespace LPC\Captcha\ViewHelpers;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ShieldViewHelper extends AbstractCaptchaViewHelper
{
	protected $tagName = 'input';

	public function render(): string {
		$pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
		$pageRenderer->addJsFooterFile('EXT:lpc_captcha/Resources/Public/JS/shield.js', defer: true);
		$pageRenderer->addCssFile('EXT:lpc_captcha/Resources/Public/CSS/shield.css');

		$answer = random_bytes(30);
		$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$encryptionKey = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
		if (!$encryptionKey) {
			throw new \Exception('Encryption key was empty!');
		}
		$key = hash_hkdf('sha256', $encryptionKey, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
		$encryptedAnswer = $nonce.sodium_crypto_secretbox($answer, $nonce, $key);

		$this->setAnswerFieldName('answer');
		$this->tag->addAttribute('type', 'hidden');
		$this->tag->addAttribute('class', 'lpcCaptchaShield');
		$this->tag->addAttribute('value', base64_encode($encryptedAnswer));

		$out = '<a href="/bots-see-here.html" class="botsSeeHereLink">link</a>';
		$out .= $this->tag->render();
		$out .= $this->renderTypeAndHmacFields('shield', base64_encode($answer));
		return $out;
	}
}
