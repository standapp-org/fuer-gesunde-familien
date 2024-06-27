<?php
namespace LPC\LpcBase\Configuration;

abstract class ExtbasePlugin extends Plugin
{
	public function getPluginType(): string {
		return 'list_type';
	}

	public function getWizardTab(): ?string {
		return 'plugins';
	}

	/**
	 * @return array<string, string>
	 */
	public function getControllerActions(): array {
		return [];
	}

	/**
	 * @return array<string, string>
	 */
	public function getUncachedControllerActions(): array {
		return [];
	}
}
