<?php
namespace LPC\LpcPetition\Configuration;

use LPC\LpcBase\Configuration\ExtbasePlugin;
use LPC\LpcPetition\Controller\PetitionController;

class ListPlugin extends ExtbasePlugin
{
	public function getTitle(): string {
		return 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang_be.xlf:plugin.list.title';
	}

	public function getDescription(): string {
		return 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang_be.xlf:plugin.list.description';
	}

	public function getControllerActions(): array {
		return [
			PetitionController::class => 'list',
		];
	}

	public function getUncachedControllerActions(): array {
		return [
			PetitionController::class => 'list',
		];
	}
}
