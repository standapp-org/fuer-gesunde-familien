<?php

$EM_CONF[$_EXTKEY] = [
	'title' => 'LPC Base',
	'description' => 'Supports developing for LPC',
	'category' => 'misc',
	'author' => 'Michael Hadorn',
	'author_email' => 'michael.hadorn@laupercomputing.ch',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => '0',
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'version' => '2.4.1',
	'constraints' => [
		'depends' => [
			'typo3' => '8.7.0-11.5.99',
		],
		'conflicts' => [],
		'suggests' => [
			'vhs' => '3.1.0',
		],
	],
	'autoload' => [
		'psr-4' => [
			'LPC\\LpcBase\\' => 'Classes',

			// from vendor/composer/autoload_psr4.php for non composer installs
			'DASPRiD\\Enum\\' => 'vendor/dasprid/enum/src',
			'BaconQrCode\\' => 'vendor/bacon/bacon-qr-code/src',
		],
	],
];
