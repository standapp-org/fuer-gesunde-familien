<?php
namespace LPC\LpcBase\Mail;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder;
use TYPO3\CMS\Fluid\View\TemplatePaths;


/**
 * extends the core FluidEmail class with:
 *  - automatically load templates from templateRootPaths of current extension
 *  - conditionally render html and plain parts if a matching template can be found
 *  - add send method
 */
class FluidEmail extends \TYPO3\CMS\Core\Mail\FluidEmail
{
	protected function initializeView(TemplatePaths $templatePaths = null): void
	{
		$request = GeneralUtility::makeInstance(RequestBuilder::class)->build($GLOBALS['TYPO3_REQUEST']);
		$configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);

		$configuration = $configurationManager->getConfiguration(
			\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
			$request->getControllerExtensionName()
		);

		if($templatePaths === null) {
			$extensionKey = \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($request->getControllerExtensionName());
			$templateRootPaths = $this->getViewProperty($configuration,'templateRootPaths');
			if($templateRootPaths) {
				$templateRootPaths = array_map(\TYPO3\CMS\Core\Utility\GeneralUtility::class.'::getFileAbsFileName',$templateRootPaths);
			} else {
				$templateRootPaths = array(
					\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey).'Resources/Private/Templates'
				);
			}
			$layoutRootPaths = $this->getViewProperty($configuration,'layoutRootPaths');
			if($layoutRootPaths) {
				$templateRootPaths = array_map(\TYPO3\CMS\Core\Utility\GeneralUtility::class.'::getFileAbsFileName',$templateRootPaths);
			} else {
				$layoutRootPaths = array(
					\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey).'Resources/Private/Layouts'
				);
			}
			$partialRootPaths = $this->getViewProperty($configuration,'partialRootPaths');
			if($partialRootPaths) {
				$partialRootPaths = array_map(\TYPO3\CMS\Core\Utility\GeneralUtility::class.'::getFileAbsFileName',$partialRootPaths);
			} else {
				$partialRootPaths = array(
					\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey).'Resources/Private/Partials'
				);
			}
			$templatePaths = new \TYPO3\CMS\Fluid\View\TemplatePaths([
				'templateRootPaths' => $templateRootPaths,
				'layoutRootPaths' => $layoutRootPaths,
				'partialRootPaths' => $partialRootPaths,
			]);
		}

		$contentObject = $configurationManager->getContentObject();
		parent::initializeView($templatePaths);
		if ($contentObject !== null) {
			// constructor of StandaloneView overwrites contentObject. we restore it here
			$configurationManager->setContentObject($contentObject);
		}

		$this->view->getRenderingContext()->getControllerContext()->setRequest($request);
		$this->view->getRenderingContext()->getControllerContext()->getUriBuilder()->setRequest($request);
		$this->view->getRenderingContext()->setControllerName($request->getControllerName());
	}

	/**
	 * @see \TYPO3\CMS\Extbase\Mvc\Controller\ActionController->getViewProperty
	 */
	protected function getViewProperty($extbaseFrameworkConfiguration, $setting)
	{
		$values = array();
		if (
			!empty($extbaseFrameworkConfiguration['view'][$setting])
			&& is_array($extbaseFrameworkConfiguration['view'][$setting])
		) {
			$values = $extbaseFrameworkConfiguration['view'][$setting];
			ksort($values);
			$values = array_reverse($values, true);
		}

		return $values;
	}

	public function setTemplate(string $templateName): static {
		$candidates = [
			$this->view->getRenderingContext()->getControllerName().'/'.$templateName,
			$templateName,
		];
		foreach($this->view->getTemplatePaths()->getTemplateRootPaths() as $path) {
			$formats = [];
			$path = rtrim($path, '/');
			foreach($this->format as $format) {
				$extension = [
					'html' => 'html',
					'plain' => 'txt',
				][$format];
				foreach($candidates as $candidate) {
					if(is_file($path.'/'.$candidate.'.'.$extension)) {
						$formats[] = $format;
						break;
					}
				}
			}
			if($formats) {
				$this->format = $formats;
			}
		}
		return parent::setTemplate($templateName);
	}

	public function send()
	{
		\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\Mailer::class)
			->send($this);
	}
}
