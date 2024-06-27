<?php

use LPC\LpcBase\Utility\PluginUtility;

return [
	'ctrl' => [
		'title'	=> 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang_db.xlf:tx_lpcpetition_domain_model_entry',
		'label'     => 'lastname',
		'label_alt' => 'firstname',
		'label_alt_force' => TRUE,
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
		),
		'dividers2tabs' => TRUE,
		'searchFields' => 'firstname,lastname,mail',
		'iconfile' => 'EXT:lpc_petition/Resources/Public/Icons/Extension.svg',
		'security' => [
			'ignorePageTypeRestriction' => true,
		]
	],
	'interface' => [
		'showRecordFieldList' => 'hidden,firstname,lastname,address,zip,place,canton,country,mail,title,birthday,phone,comment,private,newsletter,allow_reuse',
	],
	'types' => [
		'1' => ['showitem' => 'hidden;;1;;1-1-1, firstname, lastname, address, zip, place, canton, country, mail, title, birthday, phone, comment, private, newsletter, allow_reuse'],
	],
	'palettes' => [
		'1' => ['showitem' => ''],
	],
	'columns' => [
		'hidden' => PluginUtility::getStandardFieldTca('hidden'),

		'firstname' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang.xlf:formLabel.firstname',
			'config' => array(
				'type' => 'input',
				'size' => '30',
			)
		),
		'lastname' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang.xlf:formLabel.lastname',
			'config' => array(
				'type' => 'input',
				'size' => '30',
			)
		),
		'address' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang.xlf:formLabel.address',
			'config' => array(
				'type' => 'input',
				'size' => '30',
			)
		),
		'zip' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang.xlf:formLabel.zip',
			'config' => array(
				'type' => 'input',
				'size' => '30',
			)
		),
		'place' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang.xlf:formLabel.place',
			'config' => array(
				'type' => 'input',
				'size' => '30',
			)
		),
		'canton' => array(
			'label' => 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang.xlf:formLabel.canton',
			'config' => array(
				'type' => 'input',
				'eval' => 'nospace,upper',
				'max' => 2,
			)
		),
		'country' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang.xlf:formLabel.country',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '2',
			)
		),
		'mail' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang.xlf:formLabel.mail',
			'config' => array(
				'type' => 'input',
				'size' => '30',
			)
		),
		'title' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang.xlf:formLabel.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
			)
		),
		'birthday' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang.xlf:formLabel.birthday',
			'config' => array(
				'type' => 'datetime',
				'format' => 'date',
				'eval' => 'int',
			)
		),
		'phone' => array(
			'label' => 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang.xlf:formLabel.phone',
			'config' => array(
				'type'     => 'input',
			)
		),
		'comment' => array(
			'label' => 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang.xlf:formLabel.comment',
			'config' => array(
				'type'     => 'text',
			)
		),
		'private' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang.xlf:formLabel.private',
			'config' => array(
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => [
					['LLL:EXT:lpc_petition/Resources/Private/Language/locallang.xlf:formLabel.private.0',0],
					['LLL:EXT:lpc_petition/Resources/Private/Language/locallang.xlf:formLabel.private.1',1],
				],
			)
		),

		'newsletter' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang.xlf:formLabel.newsletter',
			'config' => array(
				'type' => 'check',
			)
		),

		'allow_reuse' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang.xlf:formLabel.allowReuse',
			'config' => array(
				'type' => 'check',
			)
		),

		'field_data' => [
			'config' => [
				'type' => 'text',
			],
		],

		// required for field to be mapped to extbase model
		'crdate' => [
			'config' => [
				'type' => 'passthrough'
			]
		],
	],
];
