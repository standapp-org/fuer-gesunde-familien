<?php

use LPC\Captcha\Middleware\Shield;

return [
	'frontend' => [
		'lpc-captcha-shield' => [
			'target' => Shield::class,
			'after' => [
				'typo3/cms-core/normalized-params-attribute',
				'typo3/cms-frontend/maintenance-mode',
			],
			'before' => [
				'typo3/cms-frontend/page-resolver',
			]
		]
	]
];
