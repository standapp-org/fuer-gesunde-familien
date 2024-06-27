<?php

if (!defined('TYPO3')) {
	die('Access denied.');
}

$GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['lpc_base'] = LPC\LpcBase\Hook\FixEmptyStoragePidHook::class;

\LPC\LpcBase\Configuration\FluidPlugin::register();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(
	\LPC\LpcBase\Property\TypeConverter\UploadedFileReferenceConverter::class
);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(
	\LPC\LpcBase\Property\TypeConverter\DateConverter::class
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['GeneratedAliasMapper'] = \LPC\LpcBase\Routing\GeneratedAliasMapper::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['SluggableDomainModelMapper'] = \LPC\LpcBase\Routing\SluggableDomainModelMapper::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook']['lpc_base'] = \LPC\LpcBase\Configuration\PluginRegistry::class;
