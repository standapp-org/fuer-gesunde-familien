<?php

use LPC\LpcBase\Utility\PluginUtility;

return [
	'ctrl' => [
		'title'	=> 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang_db.xlf:tx_lpcpetition_domain_model_field',
		'label'     => 'name',
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
		),
		'dividers2tabs' => TRUE,
		'searchFields' => '',
		'type' => 'type',
		'iconfile' => 'EXT:lpc_petition/Resources/Public/Icons/Extension.svg',
		'security' => [
			'ignorePageTypeRestriction' => true,
		]
	],
	'interface' => [
		'showRecordFieldList' => 'hidden,name,type,options',
	],
	'types' => [
		0 => ['showitem' => 'hidden, type, name'],
		'select' => ['showitem' => 'hidden, type, name, options'],
		'checkboxes' => ['showitem' => 'hidden, type, name, options'],
		'radios' => ['showitem' => 'hidden, type, name, options'],
	],
	'palettes' => [
		'1' => ['showitem' => ''],
	],
	'columns' => [
		'hidden' => PluginUtility::getStandardFieldTca('hidden'),

		'type' => [
			'label' => 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang_db.xlf:tx_lpcpetition_domain_model_field.type',
			'config' => [
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => [
					['LLL:EXT:lpc_petition/Resources/Private/Language/locallang_db.xlf:tx_lpcpetition_domain_model_field.type.input', 'input'],
					['LLL:EXT:lpc_petition/Resources/Private/Language/locallang_db.xlf:tx_lpcpetition_domain_model_field.type.checkbox', 'checkbox'],
					['LLL:EXT:lpc_petition/Resources/Private/Language/locallang_db.xlf:tx_lpcpetition_domain_model_field.type.select', 'select'],
					['LLL:EXT:lpc_petition/Resources/Private/Language/locallang_db.xlf:tx_lpcpetition_domain_model_field.type.checkboxes', 'checkboxes'],
					['LLL:EXT:lpc_petition/Resources/Private/Language/locallang_db.xlf:tx_lpcpetition_domain_model_field.type.radios', 'radios'],
					['LLL:EXT:lpc_petition/Resources/Private/Language/locallang_db.xlf:tx_lpcpetition_domain_model_field.type.textarea', 'textarea'],
					['LLL:EXT:lpc_petition/Resources/Private/Language/locallang_db.xlf:tx_lpcpetition_domain_model_field.type.date', 'date'],
					['LLL:EXT:lpc_petition/Resources/Private/Language/locallang_db.xlf:tx_lpcpetition_domain_model_field.type.email', 'email'],
				],
			]
		],

		'name' => [
			'label' => 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang_db.xlf:tx_lpcpetition_domain_model_field.name',
			'config' => [
				'type' => 'input',
			],
		],

		'options' => [
			'label' => 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang_db.xlf:tx_lpcpetition_domain_model_field.options',
			'config' => [
				'type' => 'text',
			],
		],
	],
];
