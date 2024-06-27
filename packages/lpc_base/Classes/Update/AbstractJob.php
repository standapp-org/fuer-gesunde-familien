<?php
namespace LPC\LpcBase\Update;

abstract class AbstractJob
{
	protected $currentRow;
	protected $currentField;
	protected $currentStep;
	protected $userInput;

	/**
	 * check if there is something to do
	 *
	 * @return boolean
	 */
	abstract public function check();

	/**
	 * prepare updates
	 *
	 * @return array<LPC\LpcBase\Update\UpdateStep>
	 */
	abstract public function prepare(\Symfony\Component\Console\Output\OutputInterface $output);

	abstract public function getLabel();

	abstract public function getDescription();

	public function getErrorHandlingOptions() {
		return [];
	}

	protected function getOldTableName() {
		return false;
	}

	protected function getCurrentField() {
		return $this->currentField;
	}

	protected function getNewTableName() {
		return false;
	}

	protected function applyMapOrAction($mapOrAction,$row) {
		$this->currentRow = $row;
		if(is_callable($mapOrAction)) {
			return $mapOrAction($row,$this);
		}
		$newRow = [];
		foreach($mapOrAction as $oldName => $action) {
			if(!isset($row[$oldName])) continue;
			$this->currentField = $oldName;
			if(is_callable($action)) {
				try {
					$value = $action($row[$oldName],$this);
					if(is_array($value)) {
						$newRow = array_merge($newRow,$value);
					} else {
						$newRow[$oldName] = $value;
					}
				} catch(UpdateException $ex) {
					if($ex->failsUpdate()) {
						throw $ex;
					}
				}
			} else if(is_string($action)) {
				$newRow[$action] = $row[$oldName];
			} else if(is_array($action)) {
				foreach($action as $newName => $callback) {
					array_merge($newRow,$callback($row[$oldName],$this));
				}
			} else {
				throw new UpdateException('Could not understand action for "'.$oldName.'"',true);
			}
		}
		return $newRow;
	}

	public function copyFileReferences($newFieldname,$newTable = null,$oldFieldname = null,$oldTable = null,$parentStep = null) {
		if($parentStep === null) {
			$parentStep = $this->currentStep;
		}
		if($newTable === null) {
			$newTable = $this->getNewTableName();
			if(!$newTable) {
				throw new \InvalidArgumentException('You need to specify the target table in your call to copyFileReferences for this migration');
			}
		}
		if($oldTable === null) {
			$oldTable = $this->getOldTableName();
			if(!$oldTable) {
				throw new \InvalidArgumentException('You need to specify the source table in your call to copyFileReferences for this migration');
			}
		}
		if($oldFieldname === null) {
			$oldFieldname = $this->currentField;
		}
		$oldUid = $this->currentRow['uid'];
		$fileReferences = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
			->getConnectionForTable('sys_file_reference')
			->select(
				['*'],
				'sys_file_reference',
				[
					'uid_foreign' => $oldUid,
					'tablenames' => $oldTable,
					'fieldname' => $oldFieldname
				]
			);
		foreach($fileReferences as $fileReference) {
			unset($fileReference['uid']);
			$newUid = $parentStep->getUid();
			$fileReference['uid_foreign'] = $newUid;
			$fileReference['fieldname'] = $newFieldname;
			$fileReference['tablenames'] = $newTable;
			$step = new UpdateStep;
			$step->insertRow('sys_file_reference',$fileReference);
			if(!ctype_digit($newUid)) {
				$step->usePlaceholder($newUid);
			}
			$parentStep->addSubStep($step);
		}
		return [$newFieldname => count($fileReferences)];
	}

	public function createFileReference($pathPrefix,$fieldname,$table = null,$oldFieldname = null) {
		if($table === null) {
			$table = $this->getNewTableName();
			if(!$table) {
				throw new \InvalidArgumentException('You need to specify the target table in your call to createFileReference for this migration');
			}
		}
		if($oldFieldname === null) {
			$oldFieldname = $this->currentField;
		}
		$files = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',',$this->currentRow[$oldFieldname],true);
		$resourceFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Resource\ResourceFactory');
		foreach($files as $file) {
			try {
				$falObject = $resourceFactory->retrieveFileOrFolderObject($pathPrefix.$file);
				$step = new UpdateStep;
				$newUid = $this->currentStep->getUid();
				$step->insertRow('sys_file_reference',[
					'uid_local' => $falObject->getUid(),
					'uid_foreign' => $newUid,
					'fieldname' => $fieldname,
					'tablenames' => $table,
				]);
				if(!ctype_digit($newUid)) {
					$step->usePlaceholder($newUid);
				}
				$this->currentStep->addSubStep($step);
			} catch(\TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException $ex) {}
		}
		return [$fieldname => count($files)];
	}

	public function addSubStep(UpdateStep $step, $predraw = false) {
		$this->currentStep->addSubstep($step, $predraw);
	}

	public function getCurrentUid() {
		return $this->currentStep->getUid();
	}

	public function getCurrentStep() {
		return $this->currentStep;
	}

	public function getCurrentRow() {
		return $this->currentRow;
	}
}
