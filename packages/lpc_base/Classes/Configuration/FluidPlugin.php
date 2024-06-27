<?php
namespace LPC\LpcBase\Configuration;

use LPC\LpcBase\Controller\FluidController;

class FluidPlugin extends ExtbasePlugin
{
	public function getWizardTab(): string {
		return 'special';
	}

	public function getIconIdentifier(): string {
		return 'content-special-html';
	}

	public function getControllerActions(): array {
		return [
			FluidController::class => 'renderCached, renderUncached',
		];
	}

	public function getUncachedControllerActions(): array {
		return [
			FluidController::class => 'renderUncached',
		];
	}
}
