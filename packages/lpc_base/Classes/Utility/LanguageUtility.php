<?php
namespace LPC\LpcBase\Utility;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class LanguageUtility
{
	/**
	 * @see \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translateFileReference (until 8.7)
	 * @see \TYPO3\CMS\Extbase\Utility\LocalizationUtility::getLanguageKeys (since 9.5)
	 */
	static public function getCurrentLanguage(): ?string {
		$request = $GLOBALS['TYPO3_REQUEST'];
		if (ApplicationType::fromRequest($request)->isFrontend()) {
			if ($request instanceof ServerRequestInterface) {
				$siteLanguage = $request->getAttribute('language');
				if ($siteLanguage instanceof SiteLanguage) {
					return $siteLanguage->getTypo3Language();
				} elseif (isset($GLOBALS['TSFE']->config['config']['language'])) {
					return $GLOBALS['TSFE']->config['config']['language'];
				}
			}
			return null;
		} elseif (!empty($GLOBALS['BE_USER']->uc['lang'])) {
			return $GLOBALS['BE_USER']->uc['lang'];
		} elseif (!empty($GLOBALS['LANG']->lang)) {
			return $GLOBALS['LANG']->lang;
		} else {
			return null;
		}
	}

	public static function getCurrentLocale(): ?Locale {
		return self::getSiteLanguage()?->getLocale();
	}

	public static function getSiteLanguage(): ?SiteLanguage {
		$request = $GLOBALS['TYPO3_REQUEST'];
		if (ApplicationType::fromRequest($request)->isFrontend()) {
			if ($request instanceof ServerRequestInterface) {
				$siteLanguage = $request->getAttribute('language');
				if ($siteLanguage instanceof SiteLanguage) {
					return $siteLanguage;
				} elseif (isset($GLOBALS['TSFE']->config['config']['language'])) {
					return self::findSiteLanguage($GLOBALS['TSFE']->config['config']['language']);
				}
			}
			return null;
		} elseif (!empty($GLOBALS['BE_USER']->uc['lang'])) {
			return self::findSiteLanguage($GLOBALS['BE_USER']->uc['lang']);
		} elseif (!empty($GLOBALS['LANG']->lang)) {
			return self::findSiteLanguage($GLOBALS['LANG']->lang);
		} else {
			return null;
		}
	}

	private static function findSiteLanguage(string $typo3Language): ?SiteLanguage {
		$request = $GLOBALS['TYPO3_REQUEST'];
		if ($request instanceof ServerRequestInterface) {
			$site = $request->getAttribute('site');
			if ($site !== null) {
				if ($typo3Language === 'default') {
					return $site->getDefaultLanguage();
				}
				foreach ($site->getLanguages() as $language) {
					if ($language->getTypo3Language() === $typo3Language) {
						return $language;
					}
				}
			}
		}
		return null;
	}
}
