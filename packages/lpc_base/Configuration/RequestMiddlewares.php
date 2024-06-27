<?php
return [
	'frontend' => [
		'lpc/lpcbase-ajax-handler' => [
			'target' => \LPC\LpcBase\Middleware\AjaxHandlerMiddleware::class,
			'after' => [
				'typo3/cms-frontend/prepare-tsfe-rendering',
			],
		],
		'lpc/server-timing' => [
			'target' => \LPC\LpcBase\Middleware\ServerTiming::class,
		],
	]
];
