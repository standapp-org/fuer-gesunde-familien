<?php
namespace LPC\LpcBase\Configuration;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

abstract class Plugin
{
	public function getPluginName(): string {
		if (preg_match('/(\w+?)(Plugin|Element)?$/', static::class, $match) === 1) {
			return $match[1];
		}
		$name = preg_replace('/[^a-zA-z]/', '', static::class);
		if ($name === null) throw new \Exception(preg_last_error_msg(), preg_last_error());
		return $name;
	}

	public function getExtensionName(): string {
		return explode('\\', static::class, 3)[1];
	}

	public function getExtensionKey(): string {
		return GeneralUtility::camelCaseToLowerCaseUnderscored($this->getExtensionName());
	}

	public function getSignature(): string {
		return strtolower($this->getExtensionName()).'_'.strtolower($this->getPluginName());
	}

	public function getTitle(): string {
		return $this->getPluginName();
	}

	public function getDescription(): string {
		return '';
	}

	public function getIconPath(): ?string {
		return null;
	}

	public function getIconIdentifier(): ?string {
		return $this->getIconPath() === null
			? 'tx-'.strtolower($this->getExtensionName())
			: 'tx-'.strtolower($this->getExtensionName()).'-plugin-'.strtolower($this->getPluginName());
	}

	/**
	 * @return null|string|array<string, mixed>
	 */
	public function getFlexform(): null|string|array {
		$flexform = 'EXT:'.$this->getExtensionKey().'/Configuration/FlexForms/'.strtolower($this->getPluginName()).'.xml';
		return file_exists(GeneralUtility::getFileAbsFileName($flexform)) ? 'FILE:'.$flexform : null;
	}

	public function getCTypeGroup(): string {
		return 'default';
	}

	public function getWizardTab(): ?string {
		return 'common';
	}

	public function getPluginType(): string {
		return 'CType';
	}

	/**
	 * this is only interpreted if getPluginType returns 'CType'
	 * @return array{showitem: string, columnsOverrides?: array<string, mixed>}
	 */
	public function getTcaTypeConfig(): array {
		return [
			'showitem' => '--palette--;;general, --palette--;;headers, --palette--;;language, --palette--;;hidden, --palette--;;access' . ($this->getFlexform() !== null ? ', pi_flexform' : ''),
		];
	}

	protected function getTemplateBasePath(): ?string {
		return 'EXT:'.$this->getExtensionKey().'/Resources/Private/Content';
	}

	public function getTypoScriptConfig(): ?string {
		$ts = 'templateName = '.$this->getPluginName()."\n";
		$templatePath = $this->getTemplateBasePath();
		if ($templatePath !== null) {
			$ts .= "templateRootPaths.10 = $templatePath/Templates\n";
			$ts .= "layoutRootPaths.10 = $templatePath/Layouts\n";
			$ts .= "partialRootPaths.10 = $templatePath/Partials\n";
		}
		if ($this instanceof DataProcessorInterface) {
			if ($this->getFlexform() !== null) {
				$ts .= "dataProcessing.98 = TYPO3\CMS\Frontend\DataProcessing\FlexFormProcessor\n";
			}
			$ts .= 'dataProcessing.99 = '.$this::class."\n";
		}
		return $ts;
	}

	public static function register(): void {
		PluginRegistry::addPlugin(GeneralUtility::makeInstance(static::class));
	}
}
