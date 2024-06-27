<?php
namespace LPC\LpcBase\Configuration;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationManager extends \TYPO3\CMS\Extbase\Configuration\ConfigurationManager
{
	protected function initializeConcreteConfigurationManager(): void
	{
		if ($this->isFrontend()) {
			parent::initializeConcreteConfigurationManager();
		} else {
			$this->concreteConfigurationManager = GeneralUtility::makeInstance(BackendConfigurationManager::class);
		}
	}

	public function getCurrentPageId(): int {
		if ($this->isFrontend()) {
			return $GLOBALS['TYPO3_REQUEST']->getAttribute('routing')->getPageId();
		} else {
			assert($this->concreteConfigurationManager instanceof BackendConfigurationManager);
			return $this->concreteConfigurationManager->getCurrentPageId();
		}
	}

	public function setBackendPageId(int $pageId): void {
		if(!$this->isFrontend()) {
			assert($this->concreteConfigurationManager instanceof BackendConfigurationManager);
			$this->concreteConfigurationManager->setPageId($pageId);
		}
	}

	private function isFrontend(): bool {
		return ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
			&& ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend();
	}
}
