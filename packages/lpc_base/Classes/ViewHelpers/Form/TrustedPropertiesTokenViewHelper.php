<?php
namespace LPC\LpcBase\ViewHelpers\Form;

class TrustedPropertiesTokenViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
	protected $escapeOutput = false;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService
	 */
	protected $mvcPropertyMappingConfigurationService;

	public function injectMvcPropertyMappingConfigurationService(\TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfigurationService $mvcPropertyMappingConfigurationService): void {
		$this->mvcPropertyMappingConfigurationService = $mvcPropertyMappingConfigurationService;
	}

	public function initializeArguments(): void {
		$this->registerArgument('properties','array','');
	}

	public function render(): string {
		return $this->mvcPropertyMappingConfigurationService->generateTrustedPropertiesToken($this->arguments['properties']);
	}
}
