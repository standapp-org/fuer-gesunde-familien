<?php

array_walk($GLOBALS['TCA']['pages']['columns']['media']['config']['overrideChildTca']['types'], function(&$type) {
	$type = preg_replace('/(?<!\w)imageoverlayPalette(?!\w)/','headerImageOverlayPalette',$type);
});

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', [
	'social_links' => [
		'label' => 'LLL:EXT:lpc_base/Resources/Private/Language/locallang_db.xlf:pages.social_links',
		'config' => [
			'type' => 'inline',
			'foreign_table' => 'tx_lpcbase_domain_model_sociallink',
			'foreign_field' => 'pid',
		],
	],
]);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', '--div--;LLL:EXT:lpc_base/Resources/Private/Language/locallang_db.xlf:tabs.pages.social_media, social_links');
