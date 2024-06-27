<?php
namespace LPC\LpcBase\Service;

class FileIndexService extends \TYPO3\CMS\Core\Resource\Index\Indexer
{
	static $indexerInstances = [];

	protected function processChangesInFolder(\TYPO3\CMS\Core\Resource\Folder $folder,$recursive,$extractMetaData) {
		$this->removeDuplicateEntries($folder,$recursive);

		$files = $this->storage->getFileIdentifiersInFolder($folder->getIdentifier(),true,$recursive);
		$this->detectChangedFilesInStorage($files);
		$this->processChangedAndNewFiles();

		$this->detectMissingFilesInFolder($folder,array_combine($files,$files),$recursive);

		if($extractMetaData) {
			$this->runMetaDataExtraction();
		}
	}

	protected function detectMissingFilesInFolder(\TYPO3\CMS\Core\Resource\Folder $folder,$fileIdentifiers,$recursive) {
		$indexedFiles = $this->getFileIndexRepository()->findByFolder($folder);
		foreach($indexedFiles as $record) {
			if (!isset($fileIdentifiers[$record['identifier']]) && !$this->storage->hasFile($record['identifier'])) {
				$this->getFileIndexRepository()->markFileAsMissing($record['uid']);
			}
		}
		if($recursive) {
			foreach($folder->getSubfolders() as $subfolder) {
				$this->detectMissingFilesInFolder($subfolder,$fileIdentifiers,$recursive);
			}
		}
	}

	protected function removeDuplicateEntries(\TYPO3\CMS\Core\Resource\Folder $folder,$recursive) {
		$rows = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
			->getConnectionForTable('sys_file')
			->select(
				['uid','identifier'],
				'sys_file',
				['folder_hash' => $folder->getHashedIdentifier()]
			);
		$seen = [];
		foreach($rows as $row) {
			if(isset($seen[$row['identifier']])) {
				$this->getFileIndexRepository()->remove($row['uid']);
			} else {
				$seen[$row['identifier']] = 1;
			}
		}
		if($recursive) {
			foreach($folder->getSubfolders() as $subfolder) {
				$this->removeDuplicateEntries($subfolder,$recursive);
			}
		}
	}

	static public function updateFolderIndex(\TYPO3\CMS\Core\Resource\Folder $folder,$recursive = true,$extractMetaData = true) {
		if(!isset(self::$indexerInstances[$folder->getStorage()->getUid()])) {
			self::$indexerInstances[$folder->getStorage()->getUid()] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(self::class,$folder->getStorage());
		}
		self::$indexerInstances[$folder->getStorage()->getUid()]->processChangesInFolder($folder,$recursive,$extractMetaData);
	}
}
