<?php
namespace LPC\LpcPetition\Configuration;

use LPC\LpcBase\Configuration\ExtbasePlugin;
use LPC\LpcPetition\Controller\PetitionController;

class FormPlugin extends ExtbasePlugin
{
	public function getTitle(): string {
		return 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang_be.xlf:plugin.form.title';
	}

	public function getDescription(): string {
		return 'LLL:EXT:lpc_petition/Resources/Private/Language/locallang_be.xlf:plugin.form.description';
	}

	public function getControllerActions(): array {
		return [
			PetitionController::class => 'form,sign,doubleOptIn,thanks,force',
		];
	}

	public function getUncachedControllerActions(): array {
		return [
			PetitionController::class => 'form,sign,doubleOptIn,force',
		];
	}
}
