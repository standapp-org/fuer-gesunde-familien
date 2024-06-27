<?php
return [
	'ctrl' => [
		'title'	=> 'LLL:EXT:lpc_base/Resources/Private/Language/locallang_db.xlf:tx_lpcbase_domain_model_sociallink',
		'label' => 'link',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'dividers2tabs' => true,
		'versioningWS' => 2,
		'versioning_followPages' => true,
		'sortby' => 'sorting',
		'delete' => 'deleted',
		'enablecolumns' => [
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		],
		'searchFields' => '',
		'iconfile' => 'EXT:lpc_base/Resources/Public/ForkAwesome/svg/link.svg',
		'typeicon_column' => 'link',
		'typeicon_classes' => [
			'userFunc' => \LPC\LpcBase\FlexForm\DataProvider::class.'->getSocialLinkIcon',
		],
		'rootLevel' => -1,
	],
	'interface' => [
		'showRecordFieldList' => '
			link,
			sys_language_uid,
			l10n_parent,
			l10n_diffsource,
			hidden,
			starttime,
			endtime
		',
	],
	'types' => [
		'1' => [
			'showitem' => '
				link,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
				hidden,
				starttime,
				endtime,
				--div--;LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language,
				sys_language_uid,
				l10n_parent,
				l10n_diffsource
			',
		],
	],
	'columns' => [
		't3ver_label' => [
			'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.versionLabel',
			'config' => [
				'type' => 'input',
				'size' => 30,
				'max' => 30
			]
		],
		'hidden' => [
			'exclude' => true,
			'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
			'config' => [
				'type' => 'check',
				'renderType' => 'checkboxToggle',
				'items' => [
					[
						0 => '',
						1 => '',
						'invertStateDisplay' => true
					]
				],
			]
		],
		'starttime' => [
			'exclude' => true,
			'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
			'config' => [
				'type' => 'input',
				'renderType' => 'inputDateTime',
				'eval' => 'datetime,int',
				'default' => 0,
				'behaviour' => [
					'allowLanguageSynchronization' => true,
				]
			]
		],
		'endtime' => [
			'exclude' => true,
			'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
			'config' => [
				'type' => 'input',
				'renderType' => 'inputDateTime',
				'eval' => 'datetime,int',
				'default' => 0,
					'range' => [
					'upper' => mktime(0, 0, 0, 1, 1, 2038),
				],
				'behaviour' => [
					'allowLanguageSynchronization' => true,
				]
			]
		],
		'link' => [
			'label' => 'LLL:EXT:lpc_base/Resources/Private/Language/locallang_db.xlf:tx_lpcbase_domain_model_sociallink.link',
			'config' => [
				'type' => 'input',
				'renderType' => 'inputLink',
				'fieldControl' => [
					'linkPopup' => [
						'options' => [
							'blindLinkOptions' => 'file,folder,mail,page,spec,telephone',
							'blindLinkFields' => 'target,class',
						],
					],
				]
			],
		],
	],
];

