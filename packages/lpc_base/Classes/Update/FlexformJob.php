<?php
namespace LPC\LpcBase\Update;

class FlexformJob extends AbstractJob
{
	protected $oldPluginName;
	protected $newPluginNameOrAction;
	protected $mapOrAction;
	protected $numRows = null;
	protected $currentTableRow = null;
	static protected $flexformDefinitions = [];

	public function __construct($oldPluginName,$newPluginNameOrAction,$mapOrAction) {
		$this->oldPluginName = $oldPluginName;
		$this->newPluginNameOrAction = $newPluginNameOrAction;
		$this->mapOrAction = $mapOrAction;
	}

	public function getLabel() {
		$this->check();
		$label = 'convert '.$this->numRows.' Plugins from '.$this->oldPluginName;
		if(is_string($this->newPluginNameOrAction)) {
			$label .= ' to '.$this->newPluginNameOrAction;
		}
		return $label;
	}

	public function getDescription() {
		$description = 'convert '.$this->oldPluginName.' Plugins';
		if(is_string($this->newPluginNameOrAction)) {
			$description .= ' to '.$this->newPluginNameOrAction;
		}
		return $description;
	}

	protected function getOldTableName() {
		return 'tt_content';
	}

	protected function getNewTableName() {
		return 'tt_content';
	}

	public function check() {
		if($this->numRows === null) {
			$this->numRows = (int)\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
				->getConnectionForTable('tt_content')
				->fetchOne('SELECT COUNT(*) FROM tt_content WHERE '.$this->getUpdatedableFlexformClause());
		}
		return $this->numRows > 0;
	}

	public function prepare(\Symfony\Component\Console\Output\OutputInterface $output) {
		$steps = [];
		$plugins = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
			->getConnectionForTable('tt_content')
			->iterateAssociative('SELECT * FROM tt_content WHERE '.$this->getUpdatedableFlexformClause());
		foreach($plugins as $plugin) {
			$this->currentTableRow = $plugin;
			try {
				$oldSettings = $this->extractFlexformSettings($plugin['pi_flexform']);
				$this->currentStep = new UpdateStep;
				$newSettings = $this->applyMapOrAction($this->mapOrAction,$oldSettings);
				if(is_callable($this->newPluginNameOrAction)) {
					($this->newPluginNameOrAction)($newSettings,$this);
				} else {
					$availableSettings = self::getFlexformDefinition($this->newPluginNameOrAction);
					foreach($availableSettings ?? [] as $setting => $sheet) {
						if(!isset($newSettings[$setting]) && isset($oldSettings[$setting])) {
							$newSettings[$setting] = $oldSettings[$setting];
						}
					}
					$this->currentStep->updateRowByUid('tt_content',[
						'pi_flexform' => self::buildFlexformForPlugin($this->newPluginNameOrAction,$newSettings),
						'list_type' => $this->newPluginNameOrAction,
					],$plugin['uid']);
				}
				if(!$this->currentStep->isEmpty()) {
					$steps[] = $this->currentStep;
				}
			} catch(UpdateException $ex) {
				if($ex->failsUpdate()) {
					throw $ex;
				} else {
					$output->wrinteln($ex->getMessage());
				}
			}
			$this->currentTableRow = null;
		}
		return $steps;
	}

	protected function getUpdatedableFlexformClause() {
		if(!is_callable($this->newPluginNameOrAction) && !is_callable($this->mapOrAction) && $this->oldPluginName == $this->newPluginNameOrAction) {
			$regex = [];
			$flexformDefinition = self::getFlexformDefinition($this->newPluginNameOrAction);
			foreach($this->mapOrAction as $oldName => $action) {
				if(isset($flexformDefinition[$oldName])) {
					// look for setting outside the defined sheet
					$sheetName = preg_quote($flexformDefinition[$oldName]);
					$oldName = preg_quote($oldName);
					$regex[] = '<field index="'.preg_quote($oldName).'">.*<'.preg_quote($sheetName).'>';
					$regex[] = '</'.preg_quote($sheetName).'>.*<field index="'.preg_quote($oldName).'">';
				} else {
					// look for any occurence of the obsolete setting
					$regex[] = '<'.preg_quote($oldName).'>';
				}
			}
			$where = "list_type='".$this->oldPluginName."' AND pi_flexform REGEXP '(".str_replace("\\","\\\\",implode('|',$regex)).")'";
		} else {
			$where = "list_type='".$this->oldPluginName."'";
		}
		return $where.' AND deleted=0';
	}

	static public function getFlexformDefinition($pluginName) {
		if(!isset(self::$flexformDefinitions[$pluginName])) {
			$xml = $GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config']['ds'][$pluginName.',list'] ?? null;
			if($xml === null) return null;
			if(substr($xml,0,5) == 'FILE:') {
				$xml = file_get_contents(\TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(substr($xml,5)));
			}
			$flexform = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($xml);
			$def = [];
			foreach($flexform['sheets'] as $sheetName => $sheet) {
				$def = array_merge($def,array_fill_keys(array_keys($sheet['ROOT']['el']),$sheetName));
			}
			self::$flexformDefinitions[$pluginName] = $def;
		}
		return self::$flexformDefinitions[$pluginName];
	}

	static public function buildFlexformForPlugin($pluginName,$settings) {
		$definition = self::getFlexformDefinition($pluginName);
		if($definition === null) return '';
		$sheets = [];
		foreach($settings as $setting => $value) {
			if(isset($definition[$setting])) {
				$sheets[$definition[$setting]][$setting] = $value;
			}
		}
		$xml = "<?xml version=\"1.0\" encoding=\"utf-8\" standalone=\"yes\" ?>\n<T3FlexForms>\n\t<data>\n";
		foreach($sheets as $sheetName => $sheetSettings) {
			$xml .= "\t\t<sheet index=\"".$sheetName."\">\n\t\t\t<language index=\"lDEF\">\n";
			foreach($sheetSettings as $name => $value) {
				$xml .= "\t\t\t\t<field index=\"".$name."\">\n\t\t\t\t\t<value index=\"vDEF\">".htmlspecialchars($value)."</value>\n\t\t\t\t</field>\n";
			}
			$xml .= "\t\t\t</language>\n\t\t</sheet>\n";
		}
		$xml .= "\t</data>\n</T3FlexForms>";
		return $xml;
	}

	protected function extractFlexformSettings($xml) {
		$flexform = \TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($xml);
		$settings = [];
		foreach($flexform['data'] as $sheetName => $sheetContent) {
			foreach($sheetContent['lDEF'] as $setting => $settingContent) {
				$settings[$setting] = $settingContent['vDEF'];
			}
		}
		return $settings;
	}

	public function getCurrentRow() {
		return $this->currentTableRow;
	}

	public function getCurrentPlugin() {
		return $this->currentRow;
	}
}
