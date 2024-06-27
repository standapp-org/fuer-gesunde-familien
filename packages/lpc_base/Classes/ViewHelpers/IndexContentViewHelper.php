<?php
namespace LPC\LpcBase\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\IndexedSearch\Hook\TypoScriptFrontendHook;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Core\Context\LanguageAspect;

class IndexContentViewHelper extends AbstractViewHelper
{
	use CompileWithRenderStatic;

	static ?TypoScriptFrontendHook $hook = null;

	public function initializeArguments(): void {
		$this->registerArgument('mtime', 'mixed', 'use as mtime');
		$this->registerArgument('title', 'string', 'set title of index entry');
	}

	/**
	 * @param array<string, mixed> $arguments
	 */
	public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): void {
		if(!ExtensionManagementUtility::isLoaded('indexed_search')) {
			return;
		}

		if(self::isDifferentPluginRequested($renderingContext)) {
			return;
		}

		$hook = self::setupHook();

		$hook->addContent(trim($renderChildrenClosure()).' ', self::getMtime($arguments));

		if(isset($arguments['title'])) {
			$hook->setTitle($arguments['title']);
		}
	}

	private static function isDifferentPluginRequested(RenderingContextInterface $renderingContext): bool {
		// check if arguments for any other plugin are given, return if so
		$pluginNamespace = null;
		foreach($GLOBALS['TYPO3_REQUEST']->getQueryParams() as $key => $value) {
			if(preg_match('/^tx_[a-z]+_[a-z]+$/',$key)) {
				if($pluginNamespace === null) {
					$pluginNamespace = GeneralUtility::makeInstance(ExtensionService::class)
						->getPluginNamespace(
							$renderingContext->getControllerContext()->getRequest()->getControllerExtensionName(),
							$renderingContext->getControllerContext()->getRequest()->getPluginName()
						);
				}
				if($pluginNamespace !== $key) {
					return true;
				}
			}
		}
		return false;
	}

	private static function getMtime(array $arguments): int {
		if(isset($arguments['mtime'])) {
			$mtime = $arguments['mtime'];
			if(is_string($mtime) && !ctype_digit($mtime)) {
				$mtime = strtotime($mtime);
			} else if($mtime instanceof \DateTimeInterface) {
				$mtime = $mtime->getTimestamp();
			}
		}

		// check if fluid template has been recompiled
		$frame = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,1);
		if(isset($frame[0]['file'])) {
			$fluidmtime = filemtime($frame[0]['file']);
			$mtime = isset($mtime) ? max($mtime,$fluidmtime) : $fluidmtime;
		}

		return $mtime ?? time();
	}

	private static function setupHook() {
		if(self::$hook === null) {
			self::$hook = new class extends TypoScriptFrontendHook {
				private string $content = '';
				private ?int $mtime = null;
				private ?string $title = null;

				public function addContent(string $content, int $mtime) {
					$this->content .= $content;
					$this->mtime = $this->mtime === null ? $mtime : max($this->mtime, $mtime);
				}

				public function setTitle(string $title) {
					$this->title = $title;
				}

				protected function initializeIndexerConfiguration(TypoScriptFrontendController $tsfe, LanguageAspect $languageAspect): array {
					$configuration = parent::initializeIndexerConfiguration($tsfe, $languageAspect);
					if($this->mtime !== null) {
						$configuration['content'] = '<body>'.$this->content.'</body>';
						$configuration['mtime'] = $this->mtime;
					}
					if($this->title !== null) {
						$configuration['indexedDocTitle'] = $this->title;
					}
					return $configuration;
				}
			};

			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess'][__CLASS__] = function($params, $pObj) {
				self::$hook->indexPageContent([], $GLOBALS['TSFE']);
			};
		}
		return self::$hook;
	}
}
