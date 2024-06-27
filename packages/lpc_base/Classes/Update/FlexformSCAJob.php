<?php
namespace LPC\LpcBase\Update;

class FlexformSCAJob extends FlexformJob
{
	protected $scaValue;

	public function __construct($oldPluginName,$scaValue,$newPluginNameOrAction,$mapOrAction) {
		parent::__construct($oldPluginName,$newPluginNameOrAction,$mapOrAction);
		$this->scaValue = $scaValue;
	}

	protected function getUpdatedableFlexformClause() {
		$where = 'pi_flexform REGEXP \'<field index="switchableControllerActions">[[:space:]]*<value[^>]*>('.htmlspecialchars($this->scaValue).'|'.$this->scaValue.')</value>[[:space:]]*</field>\'';
		return $where.' AND '.parent::getUpdatedableFlexformClause();
	}
}
