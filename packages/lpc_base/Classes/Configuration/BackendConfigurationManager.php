<?php
namespace LPC\LpcBase\Configuration;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager as TYPO3BackendConfigurationManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * @property ?int $currentPageId
 */
class BackendConfigurationManager extends TYPO3BackendConfigurationManager
{
	public function initializeObject(): void {
		$parentInstance = GeneralUtility::makeInstance(parent::class);
		$this->setConfiguration($parentInstance->configuration);
	}

	// protected $tsfeCache = [];

	/**
	 * @var array<int, array<mixed>>
	 */
	protected array $superConfigurationCache = [];

	public function setPageId(int $pageId): void {
		if(isset($this->currentPageId) && !empty($this->configurationCache[$this->currentPageId])) {
			$this->superConfigurationCache[$this->currentPageId] = $this->configurationCache;
		}
		$this->configurationCache = isset($this->superConfigurationCache[$pageId]) ? $this->superConfigurationCache[$pageId] : [];
		$this->currentPageId = $pageId;
	}

	/**
	 * @param array<mixed> $params
	 */
	private function getPageIdFromQueryParams(array $params): ?int {
		if(!empty($params['id'])) return (int)$params['id'];
		if(!empty($params['returnUrl'])) {
			parse_str(parse_url($params['returnUrl'], PHP_URL_QUERY) ?: '', $returnUrlParams);
			$pageId = $this->getPageIdFromQueryParams($returnUrlParams);
			if($pageId !== null) return $pageId;
		}
		foreach($params['edit']['tt_content'] ?? [] as $id => $mode) {
			if($mode === 'new') return (int)$id;
			if($mode === 'edit') {
				return GeneralUtility::makeInstance(ConnectionPool::class)
					->getConnectionForTable('tt_content')
					->fetchOne('SELECT pid FROM tt_content WHERE uid=?', [$id]);
			}
		}
		return null;
	}

	public function getCurrentPageId(?ServerRequestInterface $request = null): int {
		if($this->currentPageId !== null) {
			return $this->currentPageId;
		}

		//Get pageID from HTTP_REFERER
		if(isset($GLOBALS['TSFE']->id)) {
			$this->currentPageId = $GLOBALS['TSFE']->id;
		}

		if(!$this->currentPageId) {
			$this->currentPageId = $this->getPageIdFromQueryParams($_GET);
		}

		if(!$this->currentPageId && isset($_SERVER['HTTP_REFERER'])) {
			parse_str(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY) ?: '', $referrerParams);
			$this->currentPageId = $this->getPageIdFromQueryParams($referrerParams);
		}

		return parent::getCurrentPageId();
	}

	/**
	 * overridden to get plugin. instead of module. key
	 * @return array<string, mixed>
	 */
	protected function getPluginConfiguration(string $extensionName, ?string $pluginName = null): array
	{
		$extensionName = str_replace('_','',$extensionName);

		$setup = $this->getTypoScriptSetup();
		$pluginConfiguration = array();
		if (isset($setup['plugin.']['tx_' . strtolower($extensionName) . '.'])) {
			$pluginConfiguration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($setup['plugin.']['tx_' . strtolower($extensionName) . '.']);
		}
		if ($pluginName !== null) {
			$pluginSignature = strtolower($extensionName . '_' . $pluginName);
			if (isset($setup['plugin.']['tx_' . $pluginSignature . '.'])) {
				$overruleConfiguration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($setup['plugin.']['tx_' . $pluginSignature . '.']);
				\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($pluginConfiguration, $overruleConfiguration);
			}
		}
		return $pluginConfiguration;
	}

	// TODO: not sure if this is needed anymore and if yes how this would be done as of typo3 11
	// protected function initTSFE() {
	// 	$id = $this->getCurrentPageId();
	// 	if(!isset($GLOBALS['TSFE']) || $GLOBALS['TSFE']->id != $id) {
	// 		if (!isset($GLOBALS['TT'])) {
	// 			$GLOBALS['TT'] = new \TYPO3\CMS\Core\TimeTracker\TimeTracker(false);
	// 			$GLOBALS['TT']->start();
	// 		}
	// 		$GLOBALS['TSFE'] = GeneralUtility::makeInstance(TypoScriptFrontendController::class,  $GLOBALS['TYPO3_CONF_VARS'], $id, 0);
	// 		$GLOBALS['TSFE']->connectToDB();
	// 		$GLOBALS['TSFE']->initFEuser();
	// 		$GLOBALS['TSFE']->determineId();
	// 		$GLOBALS['TSFE']->initTemplate();
	// 		$GLOBALS['TSFE']->getConfigArray();
	// 		$this->tsfeCache[$id] = $GLOBALS['TSFE'];
	// 	}
	// }

	public function getContentObject(): \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer {
		// $this->initTSFE();
		return GeneralUtility::makeInstance(ContentObjectRenderer::class);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getSettings(string $extensionName, ?string $pluginName = null): array {
		$pluginConfiguration = $this->getPluginConfiguration($extensionName,$pluginName);
		return $pluginConfiguration['settings'];
	}
}
