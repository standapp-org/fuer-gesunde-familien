<?php
namespace LPC\LpcBase\Configuration;

use TYPO3\CMS\Backend\Controller\Event\ModifyNewContentElementWizardItemsEvent;
use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\ServiceProvider;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Core\Event\BootCompletedEvent;

class PluginRegistry implements NewContentElementWizardHookInterface
{
	/**
	 * @var Plugin[]
	 */
	private static array $plugins = [];

	/**
	 * @var ?\ArrayObject<string, array<string, mixed>>
	 */
	private static ?\ArrayObject $missedIcons = null;

	public static function addPlugin(Plugin $plugin): void {
		if ($plugin instanceof ExtbasePlugin) {
			ExtensionUtility::configurePlugin(
				$plugin->getExtensionName(),
				$plugin->getPluginName(),
				$plugin->getControllerActions(),
				$plugin->getUncachedControllerActions(),
				$plugin->getPluginType(),
			);
		} else {
			$ts = $plugin->getTypoScriptConfig();
			if ($ts !== null) {
				if ($plugin->getPluginType() === 'CType') {
					$tsPath = 'tt_content.';
				} else if ($plugin->getPluginType() === 'list_type') {
					$tsPath = 'tt_content.list.20.';
				} else {
					$tsPath = null;
				}
				if ($tsPath !== null) {
					$tsPath .= $plugin->getSignature();
					$ts = "$tsPath =< lib.contentElement\n$tsPath {\n$ts\n}\n";
					ExtensionManagementUtility::addTypoScript($plugin->getExtensionKey(), 'setup', $ts, 'defaultContentRendering');
				}
			}
		}

		self::$plugins[] = $plugin;

		// if an IconRegistry instance is created in any ext_localconf.php (which it should not) we are too late
		// for registering our plugin icons. store them here, and add them after booting is complete
		if(self::$missedIcons !== null) {
			self::addPluginIcon($plugin, self::$missedIcons);
		}
	}

	public static function registerTca(AfterTcaCompilationEvent $event): void {
		$GLOBALS['TCA'] = $event->getTca();

		foreach(self::$plugins as $plugin) {
			$pluginSignature = $plugin->getSignature();

			ExtensionManagementUtility::addPlugin(
				[
					$plugin->getTitle(),
					$pluginSignature,
					$plugin->getIconIdentifier(),
					$plugin->getCTypeGroup(),
				],
				$plugin->getPluginType(),
				$plugin->getExtensionKey()
			);

			$flexform = $plugin->getFlexform();
			if (is_array($flexform)) {
				// don't use GeneralUtility::array2xml. it doesn't allow periods in tag names
				$flexform = self::array2xml(['T3DataStructure' => ['ROOT' => ['type' => 'array', 'el' => $flexform]]]);
			}
			if ($plugin->getPluginType() === 'list_type') {
				$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'select_key,pages,recursive';

				if($flexform !== null) {
					$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
					\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature, $flexform);
				}
			}
			if ($plugin->getPluginType() === 'CType') {
				$GLOBALS['TCA']['tt_content']['types'][$pluginSignature] = $plugin->getTcaTypeConfig();
				if ($flexform !== null) {
					\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('*', $flexform, $pluginSignature);
				}
			}
		}

		$event->setTca($GLOBALS['TCA']);
	}

	/**
	 * @param array<mixed> $array
	 */
	private static function array2xml(array $array): string {
		$xml = '';
		foreach ($array as $key => $value) {
			if (is_int($key)) {
				$tag = 'numIndex';
				$xml .= '<numIndex index="'.$key.'">';
			} else {
				$tag = $key;
				$xml .= '<'.$tag.'>';
			}
			if (is_array($value)) {
				$xml .= self::array2xml($value);
			} else {
				$xml .= (string)$value;
			}
			$xml .= '</'.$tag.'>';
		}
		return $xml;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function getIcons(): array {
		self::$missedIcons = new \ArrayObject;

		$icons = new \ArrayObject;
		foreach(self::$plugins as $plugin) {
			self::addPluginIcon($plugin, $icons);
		}
		return $icons->getArrayCopy();
	}

	/**
	 * @param \ArrayObject<string, array<string, mixed>> $icons
	 */
	private static function addPluginIcon(Plugin $plugin, \ArrayObject $icons): void {
		$identifier = $plugin->getIconIdentifier();
		if($identifier === null || !str_starts_with($identifier, 'tx-') || $icons->offsetExists($identifier)) return;

		$path = $plugin->getIconPath();
		if ($path === null) {
			$path = ExtensionManagementUtility::getExtensionIcon(
				GeneralUtility::makeInstance(PackageManager::class)
					->getPackage($plugin->getExtensionKey())
					->getPackagePath(),
			);
			if ($path === '') return;
			$path = 'EXT:'.$plugin->getExtensionKey().'/'.$path;
		}
		$icons[$identifier] = [
			'provider' => strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'svg'
				? SvgIconProvider::class
				: BitmapIconProvider::class,
			'source' => $path,
		];
	}

	public static function appendPluginIcons(BootCompletedEvent $event): void {
		if(self::$missedIcons !== null && count(self::$missedIcons) > 0) {
			$container = GeneralUtility::getContainer();
			$icons = $container->get('icons');
			foreach(self::$missedIcons as $identifier => $icon) {
				$icons[$identifier] = $icon;
			}

			// clear cache and reconfigure IconRegistry
			// this is quite a bit of overhead but is only executed after a cache flush
			$cacheIdentifier = $container->get(PackageDependentCacheIdentifier::class)->withPrefix('Icons')->toString();
			$container->get('cache.core')->remove($cacheIdentifier);
			ServiceProvider::configureIconRegistry($container, $container->get(IconRegistry::class));
		}
	}

	/**
	 * Modifies WizardItems array
	 *
	 * @param array<mixed> $wizardItems Array of Wizard Items
	 * @param \TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController $parentObject Parent object New Content element wizard
	 */
	public function manipulateWizardItems(&$wizardItems, &$parentObject): void {
		foreach ($this->prepareWizardItems() as $key => $item) {
			$wizardItems[$key] = $item;
		}
		ksort($wizardItems);
	}

	public function modifyWizardItems(ModifyNewContentElementWizardItemsEvent $event): void {
		$lastOfTab = [];
		foreach ($event->getWizardItems() as $key => $_) {
			$lastOfTab[strtok($key, '_')] = $key;
		}

		foreach ($this->prepareWizardItems() as $key => $item) {
			$tab = strtok($key, '_');
			$event->setWizardItem($key, $item, isset($lastOfTab[$tab]) ? ['after' => $lastOfTab[$tab]] : []);
			$lastOfTab[$tab] = $key;
		}
	}

	private function prepareWizardItems(): \Generator {
		foreach(self::$plugins as $plugin) {
			$tab = $plugin->getWizardTab();
			if($tab !== null) {
				$signature = $plugin->getSignature();
				if ($plugin->getPluginType() === 'list_type') {
					$defValues = [
						'CType' => 'list',
						'list_type' => $signature,
					];
				} else if($plugin->getPluginType() === 'CType') {
					$defValues = [
						'CType' => $signature,
					];
				} else {
					continue;
				}
				yield $tab.'_'.$signature => [
					'title' => $this->translate($plugin->getTitle()),
					'description' => $this->translate($plugin->getDescription()),
					'iconIdentifier' => $plugin->getIconIdentifier(),
					'tt_content_defValues' => $defValues,
					'params' => urldecode(http_build_query(['defVals' => ['tt_content' => $defValues]])),
				];
			}
		}
	}

	private function translate(string $subject): string {
		if (substr($subject, 0, 4) === 'LLL:') {
			return $GLOBALS['LANG']->sL($subject);
		} else {
			return $subject;
		}
	}
}
