<?php

namespace LPC\LpcBase\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FlexformTyposcriptMergeUtility
 * Merges flexform values with typoScript values.
 *
 *
 * Use as:
 *
		ts: overrideFlexformSettingsIfEmpty = settings.frontend.fileicon.path,settings.frontend.fileicon.extension

		// merge typoscript with flexforms values -> initialAction
		$this->typoscriptUtility->merge($this->request->getControllerExtensionKey());

		// set default pid, if no pid is defined
		$configuration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if (empty($configuration['persistence']['storagePid'])) {
			// will only work with this instance of configurationmanager
			$configuration['persistence']['storagePid'] = $GLOBALS['TSFE']->id;
			$this->configurationManager->setConfiguration($configuration);
		}
 *
 *
 * @author Michael Hadorn <michael.hadorn@laupercomputing.ch>
 * @package TYPO3
 * @subpackage
 * @package Lpc\LpcBase\Utility
 */
class FlexformTyposcriptMergeUtility implements \TYPO3\CMS\Core\SingletonInterface
{
	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/** @var array contains the merged settings */
	protected $settings = null;

	public function injectConfigurationManagerInterface(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param $settingName (dot for hierarchy)
	 * @return string
	 */
	public function getSetting($settingName)
	{
		$setting = explode('.', $settingName);
		$val = $this->getValue($this->settings, $setting);

		return $val;
	}

	/**
	 * return alls merged settings (e.x. for assign for fluid)
	 * @return array
	 */
	public function getSettings() {
		return $this->settings;
	}


	/**
	 * Injects the Configuration Manager and is initializing the framework settings
	 *  ->    https://git.typo3.org/TYPO3CMS/Extensions/news.git/blob_plain/HEAD:/Classes/Controller/NewsController.php
	 *        https://forge.typo3.org/issues/51935
	 * @param string $extName the extension with plugin (e.g. tx_lpckoolevents_event)
	 * 							Best show in here in $tsSettings['plugin.']
	 */
	public function merge($extName)
	{
		// load both configurations
		$flexSettings = $this->configurationManager->getConfiguration(
			\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
		);
		$ts = $this->configurationManager->getConfiguration(
			\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
		);

		if(substr_count($extName,'_') == 2) {
			$extensionNamespace = substr($extName,0,strrpos($extName,'_'));
			$tsPluginSettings = $ts['plugin.'][$extensionNamespace.'.'] ?? [];
			\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($tsPluginSettings,$ts['plugin.'][$extName.'.'] ?? []);
		} else {
			$tsPluginSettings = $ts['plugin.'][$extName.'.'];
		}

		// start override
		if (isset($tsPluginSettings['settings.']['overrideFlexformSettingsIfEmpty'])) {
			$flexSettings = $this->override($flexSettings, $tsPluginSettings);
		}

		$this->settings = $flexSettings['settings'];
	}

	/**
	 * @param array $flex flexform
	 * @param array $ts typoscript as fallback if flex is empty
	 * @return array
	 */
	protected function override(array $flex, array $ts)
	{
		$overrideFields = $ts['settings.']['overrideFlexformSettingsIfEmpty'];
		$validFields = GeneralUtility::trimExplode(',', $overrideFields, TRUE);

		foreach ($validFields as $fieldName) {
			if (strpos($fieldName, '.') !== FALSE) {
				// Multilevel field
				$keyAsArray = explode('.', $fieldName);
				$foundInCurrentTs = $this->getValue($flex, $keyAsArray);

				if (is_string($foundInCurrentTs) && strlen($foundInCurrentTs) === 0) {
					// if only empty string is found, use typoscript value
					$foundInOriginal = $this->getValue($ts, $keyAsArray);
					if ($foundInOriginal) {
						// override flex value
						$flex = $this->setValue($flex, $keyAsArray, $foundInOriginal);
					}
				}
			} else {
				// if flexform setting is empty and value is available in TS
				if ((!isset($flex[$fieldName]) || strlen($flex[$fieldName]) === 0) && isset($ts[$fieldName])) {
					$flex[$fieldName] = $ts[$fieldName];
				}
			}
		}
		return $flex;
	}

	/**
	 * Get value from array by path
	 *
	 * @param array $data
	 * @param array $path
	 * @return array|null
	 */
	protected function getValue(array $data, array $path)
	{
		$found = TRUE;

		for ($x = 0; ($x < count($path) && $found); $x++) {
			$key = $path[$x];

			if (isset($data[$key])) {
				$data = $data[$key];
			} elseif (isset($data[$key.'.'])) {
				$data = $data[$key.'.'];
			} else {
				$found = FALSE;
			}
		}

		if ($found) {
			return $data;
		}
		return NULL;
	}

	/**
	 * Set value in array by path
	 *
	 * @param array $array
	 * @param $path
	 * @param $value
	 * @return array
	 */
	protected function setValue(array $array, $path, $value)
	{
		$this->setValueByReference($array, $path, $value);

		$final = array_merge_recursive(array(), $array);
		return $final;
	}

	/**
	 * Set value by reference
	 *
	 * @param array $array
	 * @param array $path
	 * @param $value
	 */
	private function setValueByReference(array &$array, array $path, $value)
	{
		while (count($path) > 1) {
			$key = array_shift($path);
			if (!isset($array[$key])) {
				$array[$key] = array();
			}
			$array = & $array[$key];
		}

		$key = reset($path);
		$array[$key] = $value;
	}
}
