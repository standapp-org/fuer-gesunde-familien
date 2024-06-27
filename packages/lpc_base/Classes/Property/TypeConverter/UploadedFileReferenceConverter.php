<?php
namespace LPC\LpcBase\Property\TypeConverter;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * @phpstan-type Meta array{
 *     parent?: array{class: string, property: string, uid: int},
 *     provided: string[],
 *     uploadFolder?: string,
 *     conflictMode?: string,
 *     accept?: string
 * }
 */
class UploadedFileReferenceConverter extends \TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter
{
	protected $priority = 15;

	protected $sourceTypes = ['string'];

	protected $targetType = FileReference::class;

	/**
	 * Folder where the file upload should go to (including storage).
	 */
	const CONFIGURATION_UPLOAD_FOLDER = 1;

	/**
	 * How to handle a upload when the name of the uploaded file conflicts.
	 */
	const CONFIGURATION_UPLOAD_CONFLICT_MODE = 2;

	/**
	 * @var string
	 */
	protected $defaultUploadFolder = '1:/user_upload/';

	/**
	 * One of 'cancel', 'replace', 'rename'
	 *
	 * @var string
	 */
	protected $defaultConflictMode = 'rename';

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceFactory
	 */
	protected $resourceFactory;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\CMS\Core\Database\ConnectionPool
	 */
	protected $connectionPool;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory
	 */
	protected $dataMapFactory;

	/**
	 * @var array<string, int>
	 */
	protected $sortingCache = [];

	/**
	 * @var array<string, Meta|false>
	 */
	protected $metaCache = [];

	public function injectResourceFactory(\TYPO3\CMS\Core\Resource\ResourceFactory $resourceFactory): void {
		$this->resourceFactory = $resourceFactory;
	}

	public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager): void {
		$this->persistenceManager = $persistenceManager;
	}

	public function injectConnectionPool(\TYPO3\CMS\Core\Database\ConnectionPool $connectionPool): void {
		$this->connectionPool = $connectionPool;
	}

	public function injectDataMapFactory(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory $dataMapFactory): void {
		$this->dataMapFactory = $dataMapFactory;
	}

	public function canConvertFrom($source, $targetType): bool
	{
		if(preg_match('/\[(new|copy|file)?(\d+)\]$/', $source, $matches) === 1) {
			if($matches[1] === 'new') {
				if(!isset($_FILES['lpc_form_files']['error'])) {
					return false;
				}

				$params = $_FILES['lpc_form_files']['error'];
				$segments = new \CachingIterator(new \ArrayIterator(explode('[',$source)));
				foreach($segments as $seg) {
					$seg = rtrim($seg,']');
					if(!$segments->hasNext()) {
						$seg = substr($seg, 3);
					}
					if(!isset($params[$seg])) {
						return false;
					}
					$params = $params[$seg];
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * @param array<mixed> $convertedChildProperties
	 * @return FileReference|Error
	 */
	public function convertFrom(mixed $source, string $targetType, array $convertedChildProperties = [], \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null)
	{
		/** @var class-string<FileReference> $targetType */

		if(preg_match('/^(?<namespace>.*)\[(?<action>new|copy|file)?(?<id>\d+)\]$/', $source, $parsed) !== 1) {
			throw new \Exception('could not map '.$source);
		}

		if(!isset($this->sortingCache[$parsed['namespace']])) {
			$this->sortingCache[$parsed['namespace']] = 0;
		}
		$sorting = ++$this->sortingCache[$parsed['namespace']];

		if(empty($parsed['action'])) {
			/** @var FileReference */
			$fileReference = $this->persistenceManager->getObjectByIdentifier($parsed['id'], $targetType);
			$this->connectionPool
				->getConnectionForTable('sys_file_reference')
				->update(
					'sys_file_reference',
					['sorting_foreign' => $sorting],
					['uid' => (int)$parsed['id']]
				);
		} else {
			$path = array_map(function($seg) { return rtrim($seg, ']'); }, explode('[', $source));
			$path[count($path)-1] = $parsed['id'];

			$meta = $this->parseMeta(array_slice($path, 0, -1));

			if($parsed['action'] === 'file') {
				if($meta === null || !in_array('file'.$parsed['id'], $meta['provided'], true)) {
					throw new \Exception('Only provided files can be referenced!');
				}

				$fileReference = $this->addFileReference($meta, $targetType, (int)$parsed['id'], $sorting);

			} else if($parsed['action'] === 'copy') {
				if($meta === null || !in_array($parsed['id'], $meta['provided'], true)) {
					throw new \Exception('Only provided file references can be copied!');
				}

				$row = $this->connectionPool->getConnectionForTable('sys_file_reference')->select(
					['uid_local', 'crop'],
					'sys_file_reference',
					['uid' => $parsed['id']]
				)->fetchAssociative();
				if($row === false) {
					throw new \Exception('Tried to copy a file reference that does not exists!');
				}

				$fileReference = $this->addFileReference($meta, $targetType, $row['uid_local'], $sorting, $row['crop']);

			} else if($parsed['action'] === 'new') {
				$uploadInfo = [];
				foreach($_FILES['lpc_form_files'] as $key => $params) {
					foreach($path as $seg) {
						$params = $params[$seg];
					}
					$uploadInfo[$key] = $params;
				}

				if(in_array(strtolower(pathinfo($uploadInfo['name'], PATHINFO_EXTENSION)), ['php','inc'], true)) {
					throw new \Exception('Uploading files with PHP file extensions is not allowed!', 1600350440);
				}

				if ($uploadInfo['error'] !== \UPLOAD_ERR_OK) {
					return new Error($this->getUploadErrorMessage($uploadInfo['error']) ?? 'upload error '.$uploadInfo['error'], 1600347453);
				}

				if ($configuration !== null) {
					$uploadFolderId = $configuration->getConfigurationValue(self::class, (string)self::CONFIGURATION_UPLOAD_FOLDER) ?: null;
					$conflictMode = $configuration->getConfigurationValue(self::class, (string)self::CONFIGURATION_UPLOAD_CONFLICT_MODE) ?: null;
				}
				$uploadFolderId ??= $meta['uploadFolder'] ?? $this->defaultUploadFolder;
				$conflictMode ??= $meta['conflictMode'] ?? $this->defaultConflictMode;

				if(isset($meta['accept'])) {
					if(!$this->validateAcceptFilter($uploadInfo, $meta['accept'])) {
						new Error(
							LocalizationUtility::translate(
								'uploadError.unacceptableType',
								'LpcBase',
								[$uploadInfo['type']]
							) ?? 'unacceptable file type',
							1600438287
						);
					}
				}

				$folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($uploadFolderId);
				$file = $folder->addUploadedFile($uploadInfo, $conflictMode);

				$fileReference = $this->addFileReference($meta, $targetType, $file->getUid(), $sorting);
			} else {
				throw new \LogicException('unknown file action: '.$parsed['action']);
			}
		}

		return $fileReference;
	}

	/**
	 * @param ?Meta $meta
	 * @param class-string<FileReference> $targetType
	 */
	private function addFileReference(?array $meta, string $targetType, int $fileUid, int $sorting, string $crop = ''): FileReference {
		$parentIdentity = $meta === null ? null : $this->detectParentObjectIdentity($meta);
		if($parentIdentity !== null) {
			// HACK
			// new objects needs to be persisted directly. otherwise the ordering will be ignored and they
			// will get appended to the end by \Symfony\Component\PropertyAccess\PropertyAccessor::writeCollection
			$conn = $this->connectionPool->getConnectionForTable('sys_file_reference');
			$conn->insert('sys_file_reference',[
				'uid_local' => $fileUid,
				'tablenames' => $parentIdentity['tablename'],
				'fieldname' => $parentIdentity['fieldname'],
				'uid_foreign' => $parentIdentity['uid'],
				'sorting_foreign' => $sorting,
				'crop' => $crop,
			]);
			/** @var FileReference */
			$fileReference = $this->persistenceManager->getObjectByIdentifier($conn->lastInsertId(), $targetType);
		} else {
			$fileReference = new $targetType;
			$fileReference->setOriginalResource(
				$this->resourceFactory->createFileReferenceObject(
					[
						'uid_local' => $fileUid,
						'uid_foreign' => uniqid('NEW_'),
						'uid' => uniqid('NEW_'),
						'crop' => $crop,
					]
				)
			);
		}
		return $fileReference;
	}

	/**
	 * @param string[] $path
	 * @return ?Meta
	 */
	private function parseMeta(array $path): ?array {
		$cacheKey = implode('.',$path);
		if(!isset($this->metaCache[$cacheKey])) {
			$meta = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('lpc_form_filesmeta');
			foreach($path as $seg) {
				if(!isset($meta[$seg])) {
					$meta = null;
					break;
				}
				$meta = $meta[$seg];
			}
			if(is_string($meta)) {
				$meta = base64_decode($meta, true);
				if ($meta !== false) {
					$nonce = substr($meta, -SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
					$encrypted = substr($meta, 0, -SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
					$key = hash_hkdf('sha256', $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'], SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
					$meta = sodium_crypto_secretbox_open($encrypted, $nonce, $key);
					if ($meta !== false) {
						$meta = json_decode($meta, true);
					}
				}
			}

			$this->metaCache[$cacheKey] = $meta ?: false;
		}
		return $this->metaCache[$cacheKey] ?: null;
	}

	/**
	 * @param Meta $meta
	 * @return ?array{tablename: string, fieldname: string, uid: int}
	 */
	private function detectParentObjectIdentity(array $meta): ?array {
		if(isset($meta['parent'])) {
			$dataMap = $this->dataMapFactory->buildDataMap($meta['parent']['class']);
			$columnMap = $dataMap->getColumnMap($meta['parent']['property']);
			if ($columnMap !== null) {
				return [
					'tablename' => $dataMap->getTableName(),
					'fieldname' => $columnMap->getColumnName(),
					'uid' => $meta['parent']['uid'],
				];
			}
		}
		return null;
	}

	/**
	 * @param array<string> $uploadInfo
	 */
	private function validateAcceptFilter(array $uploadInfo, string $filters): bool {
		foreach(explode(',',$filters) as $filter) {
			$filter = trim($filter);
			if(empty($filter)) continue;

			if(substr($filter, 0, 1) == '.') {
				if(strcasecmp(substr($filter, 1), pathinfo($uploadInfo['name'], PATHINFO_EXTENSION)) === 0) {
					return true;
				}
			}

			else if(substr($filter, -2) == '/*') {
				if(strpos($uploadInfo['type'], substr($filter, 0, -1)) === 0) {
					return true;
				}
			}

			else {
				if($uploadInfo['type'] == $filter) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @source \TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter::getUploadErrorMessage
	 */
	protected function getUploadErrorMessage(int $errorCode): ?string
	{
		$logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)
			->getLogger(static::class);
		switch ($errorCode) {
			case \UPLOAD_ERR_INI_SIZE:
				$logger->error('The uploaded file exceeds the upload_max_filesize directive in php.ini.', []);
				return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('uploadError.size', 'LpcBase');
			case \UPLOAD_ERR_FORM_SIZE:
				$logger->error('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.', []);
				return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('uploadError.size', 'LpcBase');
			case \UPLOAD_ERR_PARTIAL:
				$logger->error('The uploaded file was only partially uploaded.', []);
				return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('uploadError.partial', 'LpcBase');
			case \UPLOAD_ERR_NO_FILE:
				$logger->error('No file was uploaded.', []);
				return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('uploadError.noFile', 'LpcBase');
			case \UPLOAD_ERR_NO_TMP_DIR:
				$logger->error('Missing a temporary folder.', []);
				return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('uploadError.other', 'LpcBase');
			case \UPLOAD_ERR_CANT_WRITE:
				$logger->error('Failed to write file to disk.', []);
				return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('uploadError.other', 'LpcBase');
			case \UPLOAD_ERR_EXTENSION:
				$logger->error('File upload stopped by extension.', []);
				return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('uploadError.other', 'LpcBase');
			default:
				$logger->error('Unknown upload error.', []);
				return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('uploadError.other', 'LpcBase');
		}
	}
}
