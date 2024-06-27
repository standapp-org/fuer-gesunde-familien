<?php
namespace LPC\LpcBase\Update;

class DatabaseTableJob extends AbstractJob
{
	protected $oldTable;
	protected $newTableOrAction;
	protected $mapOrAction;
	protected $numRows = null;

	public function __construct($oldTableName,$newTableOrAction,$mapOrAction = []) {
		$this->oldTable = $oldTableName;
		$this->newTableOrAction = $newTableOrAction;
		$this->mapOrAction = $mapOrAction;
	}

	public function getLabel() {
		$this->check();
		$label = 'convert '.$this->numRows.' records from table '.$this->oldTable;
		if(is_string($this->newTableOrAction)) {
			$label .= ' to '.$this->newTableOrAction;
		}
		return $label;
	}

	public function getDescription() {
		$description = 'convert '.$this->oldTable.' records';
		if(is_string($this->newTableOrAction)) {
			$description .= ' to '.$this->newTableOrAction;
		}
		return $description;
	}

	protected function getOldTableName() {
		return $this->oldTable;
	}

	protected function getNewTableName() {
		if(is_callable($this->newTableOrAction)) {
			return false;
		}
		return $this->newTableOrAction;
	}

	public function check() {
		if($this->numRows === null) {
			if($this->hasTable($this->oldTable)) {
				$where = 'deleted = 0';
				if($this->hasTableField($this->oldTable,'tx_lpc_update')) {
					$where .= ' AND tx_lpc_update<1';
				}

				$this->numRows = (int)\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
					->getConnectionForTable($this->oldTable)
					->fetchOne('SELECT COUNT(*) FROM '.$this->oldTable.' WHERE '.$where);
			} else {
				$this->numRows = 0;
			}
		}
		return $this->numRows > 0;
	}

	public function prepare(\Symfony\Component\Console\Output\OutputInterface $output) {
		$steps = [];
		$andWhere = '';
		if($this->hasTableField($this->oldTable,'tx_lpc_update')) {
			$andWhere = ' AND tx_lpc_update = 0';
		} else {
			$step = new UpdateStep;
			$step->statement('ALTER TABLE '.$this->oldTable.' ADD tx_lpc_update INT(11) DEFAULT 0 NOT NULL');
			$steps[] = $step;
		}
		$mapOrAction = $this->mapOrAction;
		if(is_array($mapOrAction) && is_string($this->newTableOrAction)) {
			$copyFields = array_intersect_key(
				[
					'pid' => 'pid',
					'cruser_id' => 'cruser_id',
					'tstamp' => 'tstamp',
					'crdate' => 'crdate',
					'sys_language_uid' => 'sys_language_uid',
					'l10n_parent' => function($l10nParent) use(&$steps) {
						if($l10nParent) {
							return $this->getL10nParent($l10nParent,$steps);
						}
						return 0;
					},
					'hidden' => 'hidden',
				],
				array_fill_keys($this->getTableFields($this->oldTable),0),
				array_fill_keys($this->getTableFields($this->newTableOrAction),0)
			);
			$mapOrAction = array_merge($mapOrAction,$copyFields);
		}
		$rows = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
			->getConnectionForTable($this->oldTable)
			->executeQuery('SELECT * FROM '.$this->oldTable.' WHERE deleted=0'.$andWhere.' ORDER BY l10n_parent ASC');
		foreach($rows as $row) {
			try {
				$step = new UpdateStep;
				$this->currentStep = $step;
				$newRow = $this->applyMapOrAction($mapOrAction,$row);
				if(is_callable($this->newTableOrAction)) {
					$action = $this->newTableOrAction;
					$action($newRow,$this);
				} else if(is_string($this->newTableOrAction)) {
					$step->insertRow($this->newTableOrAction,$newRow);
				} else {
					throw new \Exception('An action could not be processed: '.var_export($this->newTableOrAction,true));
				}
				$newUid = -1;
				if(!$step->isEmpty()) {
					$newUid = $step->getUid();
					$substep = new UpdateStep;
					$substep->updateRowByUid($this->oldTable,['tx_lpc_update' => $newUid],$row['uid']);
					if(substr($newUid,0,3) == '###') {
						$substep->usePlaceholder($newUid);
					}
					$step->addSubStep($substep);
					$steps[$row['uid']] = $step;
				}
			} catch(UpdateException $ex) {
				if($ex->failsUpdate()) {
					throw $ex;
				} else {
					$output->writeln($ex->getMessage);
				}
			}
		}
		return $steps;
	}

	protected function getL10nParent($oldL10nParent,&$steps) {
		if(isset($steps[$oldL10nParent])) {
			return $steps[$oldL10nParent]->getUid();
		} else {
			$row = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
				->getConnectionForTable($this->oldTable)
				->select(['tx_lpc_update'],$this->oldTable,['uid' => $oldL10nParent]);
			if($row) {
				return $row['tx_lpc_update'];
			}
		}
		//$this->addError('l10n_parent with uid='.$oldL10nParent.' on table '.$this->oldTable.' not found');
		return 0;
	}

	protected function getTableFields($table) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
			->getConnectionForTable($table)
			->fetchAssoc('SHOW FIELDS FROM '.$table);
	}

	protected function hasTableField($table,$field) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
			->getConnectionForTable($table)
			->executeQuery("SHOW FIELDS FROM ".$table." LIKE '".$field."'")
			->rowCount() == 1;
	}

	protected function hasTable($table) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
			->getConnectionByName(\TYPO3\CMS\Core\Database\ConnectionPool::DEFAULT_CONNECTION_NAME)
			->executeQuery("SHOW TABLES LIKE '".$table."'")
			->rowCount() == 1;
	}
}
