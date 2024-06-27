<?php
namespace LPC\LpcBase\Utility;

use TYPO3\CMS\Core\PageTitle\PageTitleProviderInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\SingletonInterface;

class PluginUtility implements PageTitleProviderInterface, SingletonInterface
{
	private string $pageTitle = '';

	/**
	 * @deprecated extend LPC\LpcBase\Configuration\Plugin instead
	 *
	 * @param string $extKey the extension key in lower camel case
	 * @param string $name the name of the plugin like 'List'
	 * @param string $title the displayed title of the plugin, can be LLL:EXT:...
	 * @param string $decription the displayed description for the new content element wizard, can be LLL:EXT:...
	 * @param stirng $icon path to a icon file for this plugin
	 * @param boolean|string $flexform path to a flexform file, if this is boolean true, the file lower($name).xml is added, if false no flexform is added
	 * @param string $wizardTab specify in which tab the plugin is added in the new content element wizard. Default is 'plugins'. If empty, the plugin is not added
	 */
	static public function registerPlugin($extKey,$name,$title,$description,$icon,$flexform = true,$wizardTab = 'plugins') {
		$lowerName = strtolower($name);
		if(strpos($extKey, '_') !== false) {
			$extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($extKey);
		} else {
			$extensionName = $extKey;
			$extKey = GeneralUtility::camelCaseToLowerCaseUnderscored($extKey);
		}
		$pluginSignature = strtolower($extensionName).'_'.$lowerName;

		$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
		if($iconRegistry->isRegistered($icon)) {
			$iconIdentifier = $icon;
		} else {
			$iconIdentifier = 'tx-'.$extKey.'-plugin-'.$lowerName;
			$iconProvider = $iconRegistry->detectIconProvider($icon);
			$iconRegistry->registerIcon(
				$iconIdentifier,
				$iconProvider,
				['source' => $icon]
			);
		}

		\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
			'LPC.'.$extensionName,
			$name,
			$title,
			$iconIdentifier
		);

		$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'select_key,pages,recursive';

		if($flexform) {
			$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
			if($flexform === true) {
				$flexform = 'FILE:EXT:'.$extKey.'/Configuration/FlexForms/'.$lowerName.'.xml';
			}
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($pluginSignature,$flexform);
		}

		if($wizardTab) {
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('mod.wizards.newContentElement.wizardItems {
				'.$wizardTab.' {
					elements {
						'.$pluginSignature.' {
							title = '.$title.'
							description = '.$description.'
							iconIdentifier = '.$iconIdentifier.'
							tt_content_defValues {
								CType = list
								list_type = '.$pluginSignature.'
							}
						}
					}
					show := addToList('.$pluginSignature.')
				}
			}');
		}
	}

	/**
	 * allows to override the page <title> for cached and uncached plugins
	 *
	 * @param string $title the title to set
	 */
	static public function setPageTitle($title) {
		GeneralUtility::makeInstance(self::class)->pageTitle = $title;
	}

	public function getTitle(): string {
		return $this->pageTitle;
	}

	/**
	 * @param array $tags array of key/value pairs
	 * @param string|null $unique|$type needed if the same tag is added several times (e.g. for og:image:width) @deprecated not needed as of typo3 9 any more
	 */
	static public function addMetaTags($tags, $type = 'property', $replace = true) {
		$managerRegistry =  \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry::class);

		foreach($tags as $property => $value) {
			$manager = $managerRegistry->getManagerForProperty($property);

			if(!$replace) {
				// standard behaviour is to append the meta tag if replace is not set. we want to skip it instead.
				if(!empty($manager->getProperty($property, $type))) {
					continue;
				}
			}

			$manager->addProperty($property, $value, [], $replace, $type);
		}
	}

	static public function addOpenGraphTags($tags, $replace = true) {
		$metaTags = [];
		foreach($tags as $property => $value) {
			if($property == 'image') {
				self::addOpenGraphTag($property, $value, $replace);
			} else if($value) {
				$metaTags['og:'.$property] = strip_tags($value);
			}
		}
		$defaultMetaTags = [
			'og:type' => 'website',
			'og:title' => $GLOBALS['TSFE']->altPageTitle ?: $GLOBALS['TSFE']->page['title'],
			'og:url' => \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'),
		];
		if($replace) {
			if($defaultMetaTags = array_diff_key($defaultMetaTags, $metaTags)) {
				self::addMetaTags($defaultMetaTags, 'property', false);
			}
		} else {
			$metaTags = array_replace($defaultMetaTags, $metaTags);
		}
		self::addMetaTags($metaTags, 'property', $replace);
	}

	static public function addOpenGraphTag($property, $value, $replace = true) {
		if($property == 'image') {
			if(is_array($value) || $value instanceof \Traversable) {
				foreach($value as $image) {
					self::addOpenGraphImage($image, $replace);
				}
			} else {
				if(!is_object($value)) {
					if($value == 'firstImageOnPage') {
						$value = self::getFirstImageOnPage();
						if(!$value) return;
					} else {
						$value = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class)->retrieveFileOrFolderObject($value);
					}
				}
				self::addOpenGraphImage($value, $replace);
			}
		} else if($value) {
			self::addMetaTags(['og:'.$property => strip_tags($value)], 'property', $replace);
		}
	}

	static protected function addOpenGraphImage($image, $replace = true) {
		if(is_callable([$image,'getOriginalResource'])) {
			$image = $image->getOriginalResource();
		}
		$alt = $image->getProperty('alternative') ?: $image->getProperty('title');
		$cropString = '';
		if($image->hasProperty('crop')) {
			$cropString = $image->getProperty('crop');
		}

		$alt = $alt ?: $image->getProperty('alternative') ?: $image->getProperty('title');

		// make sure the smaller dimension is at least 200px
		$w = $image->getProperty('width');
		$h = $image->getProperty('height');
		if(min($w,$h) < 200) {
			return;
		}
		$processingInstructions = ['maxWidth' => 1200,'maxHeight' => 1200];
		if($h/$w > 6) {
			$processingInstructions['maxHeight'] = $h/$w*200;
		}
		if($w/$h > 6) {
			$processingInstructions['maxWidth'] = $w/$h*200;
		}

		$cropVariantCollection = \TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection::create((string)$cropString);
		$cropVariant = 'default';
		$cropArea = $cropVariantCollection->getCropArea($cropVariant);
		$processingInstructions['crop'] = $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($image);

		if(is_callable([$image,'getOriginalFile'])) {
			$image = $image->getOriginalFile();
		}

		$processed = $image->process(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,$processingInstructions);

		$managerRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry::class);

		$manager = $managerRegistry->getManagerForProperty('og:image');

		if(!$replace) {
			// standard behaviour is to append the meta tag if replace is not set. we want to skip it instead.
			if(!empty($manager->getProperty('og:image', 'property'))) {
				return;
			}
		}

		$manager->addProperty(
			'og:image',
			\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL').$processed->getPublicUrl(),
			[
				'width' => $processed->getProperty('width'),
				'height' => $processed->getProperty('height'),
				'type' => $processed->getMimeType(),
			],
			$replace,
			'property'
		);
	}

	static public function user_openGraphTagFromTS($content,$conf) {
		self::addOpenGraphTag($conf['property'],$content);
	}

	static public function getFirstImageOnPage($pid = null, $colPos = 0) {
		if($pid === null) {
			$pid = $GLOBALS['TSFE']->id;
		}
		$queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
			->getQueryBuilderForTable('tt_content');
		$queryBuilder
			->select('sys_file_reference.*')
			->from('sys_file_reference')
			->join(
				'sys_file_reference',
				'tt_content',
				'tt_content',
				$queryBuilder->expr()->andX(
					$queryBuilder->expr()->eq('sys_file_reference.tablenames',$queryBuilder->createNamedParameter('tt_content')),
					$queryBuilder->expr()->orX(
						$queryBuilder->expr()->andX(
							$queryBuilder->expr()->eq('sys_file_reference.fieldname',$queryBuilder->createNamedParameter('assets')),
							$queryBuilder->expr()->eq('tt_content.CType',$queryBuilder->createNamedParameter('textmedia'))
						),
						$queryBuilder->expr()->andX(
							$queryBuilder->expr()->eq('sys_file_reference.fieldname',$queryBuilder->createNamedParameter('image')),
							$queryBuilder->expr()->neq('tt_content.CType',$queryBuilder->createNamedParameter('textmedia'))
						)
					),
					$queryBuilder->expr()->eq('sys_file_reference.uid_foreign',$queryBuilder->quoteIdentifier('tt_content.uid'))
				)
			)
			->where(
				$queryBuilder->expr()->eq('tt_content.pid',$queryBuilder->createNamedParameter($pid))
			);

		if($colPos === null) {
			$queryBuilder->addOrderBy('tt_content.colPos');
		} else {
			$queryBuilder->andWhere(
				$queryBuilder->expr()->eq('tt_content.colPos',$queryBuilder->createNamedParameter($colPos, \PDO::PARAM_INT))
			);
		}

		$row = $queryBuilder
			->addOrderBy('tt_content.sorting')
			->addOrderBy('sys_file_reference.sorting_foreign')
			->setMaxResults(1)
			->execute()
			->fetch();

		if(!$row) return null;

		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class)
			->getFileReferenceObject($row['uid'], $row);
	}

	// see https://docs.typo3.org/m/typo3/reference-tca/main/en-us/BestPractises/CommonFields.html
	static public function getStandardFieldTca(string $field, string $table = '', array $overrides = []): array {
		if ($field )

		$tca = match ($field) {
			'hidden' => [
				'exclude' => true,
				'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
				'config' => [
					'type' => 'check',
					'renderType' => 'checkboxToggle',
					'items' => [
						[
							'label' => '',
							'invertStateDisplay' => true
						]
					],
				]
			],
			'starttime' => [
				'exclude' => true,
				'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
				'config' => [
					'type' => 'datetime',
					'default' => 0,
				],
				'l10n_mode' => 'exclude',
				'l10n_display' => 'defaultAsReadonly',
			],
			'endtime' => [
				'exclude' => true,
				'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
				'config' => [
					'type' => 'datetime',
					'default' => 0,
					'range' => [
						'upper' => mktime(0, 0, 0, 1, 1, 2038),
					],
				],
				'l10n_mode' => 'exclude',
				'l10n_display' => 'defaultAsReadonly',
			],
			'sys_language_uid' => [
				'exclude' => true,
				'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
				'config' => [
					'type' => 'language',
				],
			],
			'l18n_parent', 'l10n_parent' => $table === ''
				? throw new \Exception('Table name must be given for generating tca for field '.$field.'.')
			: [
				'displayCond' => 'FIELD:sys_language_uid:>:0',
				'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
				'config' => [
					'type' => 'select',
					'renderType' => 'selectSingle',
					'items' => [
						[
							'label' => '',
							'value' => 0,
						],
					],
					'foreign_table' => $table,
					'foreign_table_where' =>
						'AND {#'.$table.'}.{#pid}=###CURRENT_PID###'
						. ' AND {#'.$table.'}.{#sys_language_uid} IN (-1,0)',
					'default' => 0,
				],
			],
			'l18n_source', 'l10n_source' => [
				'config' => [
					'type' => 'passthrough',
				],
			],
			'l18n_diffsource', 'l10n_diffsource' => [
				'config' => [
					'type' => 'passthrough',
					'default' => '',
				],
			],
			't3ver_label' => [
				'displayCond' => 'FIELD:t3ver_label:REQ:true',
				'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.versionLabel',
				'config' => [
					'type' => 'none',
				],
			],
			default => throw new \Exception('Standard tca definition for "'.$field.'" is not defined.'),
		};
		ArrayUtility::mergeRecursiveWithOverrule($tca, $overrides);
		return $tca;
	}

	static public function getStandardFieldsTca(string $table, string ...$fields): array {
		$tca = [];
		foreach ($fields as $field) {
			$tca[$field] = self::getStandardFieldTca($field, $table);
		}
		return $tca;
	}
}
