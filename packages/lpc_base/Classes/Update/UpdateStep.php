<?php
namespace LPC\LpcBase\Update;

class UpdateStep
{
	protected $update = [];

	protected $uid = null;

	protected $uidPlaceholder = null;

	protected $usedPlaceholders = [];

	protected $subSteps = [];

	public function updateRowByUid($table,$row,$uid) {
		$this->update = [
			'UPDATE' => $row,
			'UID' => $uid,
			'TABLE' => $table,
		];
		$this->uid = $uid;
	}

	public function updateRow($table,$row,$where) {
		$this->update = [
			'UPDATE' => $row,
			'WHERE' => $where,
			'TABLE' => $table,
		];
		if(preg_match("/(^|[ \\.])uid\s*=\s*['\"]?([0-9]*)/",$where,$matches)) {
			$this->uid = $matches[2];
		}
	}

	public function statement($sql) {
		$this->update = [
			'SQL' => $sql,
		];
		if(preg_match("/WHERE.*[ \\.]uid\s*=\s*['\"]?([0-9]*)/",$this->update['SQL'],$matches)) {
			$this->uid = $matches[1];
		}
	}

	public function insertRow($table,$row) {
		$this->update = [
			'INSERT' => $row,
			'INTO' => $table,
		];
	}

	public function addSubStep(UpdateStep $subStep, $predraw = false) {
		$this->subSteps[$predraw][] = $subStep;
	}

	public function isEmpty() {
		return empty($this->update) && empty($this->subSteps);
	}

	public function getUid() {
		if($this->uid) {
			return $this->uid;
		} else {
			return $this->getUidPlaceholder();
		}
	}

	protected function getUidPlaceholder() {
		if(!$this->uidPlaceholder) {
			$this->uidPlaceholder = '###UID'.uniqid(true).'###';
		}
		return $this->uidPlaceholder;
	}

	public function usePlaceholder($placeholder) {
		$this->usedPlaceholders[] = $placeholder;
	}

	public function execute(&$placeholders) {

		foreach($this->subSteps[true] ?? [] as $subStep) {
			$subStep->execute($placeholders);
		}

		$insertId = null;

		$replacements = [];
		foreach($this->usedPlaceholders as $placeholder) {
			$replacements[$placeholder] = $placeholders[$placeholder];
		}
		array_walk_recursive($this->update,function(&$value,$key) use($replacements) {$value = strtr($value,$replacements);});

		if(isset($this->update['SQL'])) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
				->getConnectionByName(\TYPO3\CMS\Core\Database\ConnectionPool::DEFAULT_CONNECTION_NAME)
				->exec($this->update['SQL']);
		} else if(isset($this->update['UPDATE']) || isset($this->update['INSERT'])) {
			$table = isset($this->update['UPDATE']) ? $this->update['TABLE'] : $this->update['INTO'];
			$connection = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
				->getConnectionForTable($table);
			$qb = $connection->createQueryBuilder();
			if(isset($this->update['UPDATE'])) {
				$qb->update($this->update['TABLE'])
					->where($this->update['UID'] ? 'uid='.$this->update['UID'] : $this->update['WHERE']);
				foreach($this->update['UPDATE'] as $key => $value) {
					$qb->set($key,$value);
				}
			} else {
				$qb->insert($this->update['INTO'])
					->values($this->update['INSERT']);
			}
			$qb->execute();
			if(isset($this->update['INSERT'])) {
				$insertId = $connection->lastInsertId();
			}
		}

		if($this->uidPlaceholder) {
			if(!$this->uid) {
				$this->uid = $insertId;
			}
			$placeholders[$this->uidPlaceholder] = $this->uid;
		}

		foreach($this->subSteps[false] ?? [] as $subStep) {
			$subStep->execute($placeholders);
		}
	}
}
