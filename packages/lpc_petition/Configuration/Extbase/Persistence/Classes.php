<?php

use LPC\LpcPetition\Domain\Model\Entry;
use LPC\LpcPetition\Domain\Model\Field;

return [
	Entry::class => [
		'properties' => [
			'fieldDataJson' => ['fieldName' => 'field_data'],
		]
	],
	Field::class => [
		'properties' => [
			'serializedOptions' => ['fieldName' => 'options'],
		]
	]
];
