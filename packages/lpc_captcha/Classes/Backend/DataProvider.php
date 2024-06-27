<?php
namespace LPC\Captcha\Backend;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataProvider
{
	public function __construct(
		private ExtensionConfiguration $extensionConfiguration
	) {}

	/**
	 * @param array<string, mixed> $params
	 */
	public function getCaptchaTypes(array $params): void {
		foreach(['none','math','shield'] as $type) {
			$params['items'][] = [
				'LLL:EXT:lpc_captcha/Resources/Private/Language/locallang.xlf:captchaType.'.$type,
				$type,
			];
		}

		$config = $this->extensionConfiguration->get('lpc_captcha');
		foreach(['recaptcha', 'recaptchaInvisible'] as $recaptchaType) {
			if(!empty($config[$recaptchaType]['sitekey']) && !empty($config[$recaptchaType]['sitekeyPrivate'])) {
				$params['items'][] = [
					'LLL:EXT:lpc_captcha/Resources/Private/Language/locallang.xlf:captchaType.'.$recaptchaType,
					$recaptchaType,
				];
			}
		}
	}
}
