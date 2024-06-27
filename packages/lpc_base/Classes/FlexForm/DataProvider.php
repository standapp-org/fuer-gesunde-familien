<?php
namespace LPC\LpcBase\FlexForm;

class DataProvider
{
	/**
	 * @param array<string, mixed> $param
	 */
	public function extLoaded(array $param): bool {
		$loaded = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($param['conditionParameters'][0]);
		if(isset($param['conditionParameters'][1]) && (strtoupper($param['conditionParameters'][1]) == "FALSE" || empty($param['conditionParameters'][1])))
			$loaded = !$loaded;
		return $loaded;
	}

	/**
	 * @param array{row: array<string, scalar>} $params
	 */
	public function getSocialLinkIcon(array $params, mixed $pObj): ?string {
		if(isset($params['row']['link'])) {
			return \LPC\LpcBase\Domain\Model\SocialLink::getIconIdentifierForUrl((string)$params['row']['link']);
		}
		return null;
	}
}
