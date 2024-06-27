<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_file_reference',[
	'header_ypos' => [
		'exclude' => true,
		'label' => 'LLL:EXT:lpc_base/Resources/Private/Language/locallang_db.xlf:sys_file_reference.header_ypos',
		'config' => [
			'type' => 'input',
			'eval' => 'null,int',
			'range' => [
				'lower' => 0,
				'upper' => 100,
			],
			'default' => null,
		],
	],
	'header_xpos' => [
		'exclude' => true,
		'label' => 'LLL:EXT:lpc_base/Resources/Private/Language/locallang_db.xlf:sys_file_reference.header_xpos',
		'config' => [
			'type' => 'input',
			'eval' => 'null,int',
			'range' => [
				'lower' => 0,
				'upper' => 100,
			],
			'default' => null,
		],
	],
]);

$GLOBALS['TCA']['sys_file_reference']['palettes']['headerImageOverlayPalette'] = $GLOBALS['TCA']['sys_file_reference']['palettes']['imageoverlayPalette'];
$GLOBALS['TCA']['sys_file_reference']['palettes']['headerImageOverlayPalette']['showitem'] .= ',header_ypos,header_xpos';
