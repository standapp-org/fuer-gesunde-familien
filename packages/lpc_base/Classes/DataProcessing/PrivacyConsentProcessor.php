<?php

namespace LPC\LpcBase\DataProcessing;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

class PrivacyConsentProcessor implements DataProcessorInterface
{
	const GLOBAL_JS_OBJECT_NAME = 'LPCPrivacyConsent';

	/**
	 * @param array<mixed> $contentObjectConfiguration
	 * @param array<mixed> $processorConfiguration
	 * @param array<mixed> $processedData
	 * @return array<mixed>
	 */
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData): array {
		$data = [];
		$callbacks = [];
		foreach ($processorConfiguration['options.'] ?? [] as $key => $option) {
			if (isset($option['label']) && isset($option['label.'])) {
				$option['label'] = $cObj->cObjGetSingle($option['label'], $option['label.']);
				unset($option['label.']);
			}
			if (isset($option['onAccept'])) {
				if (isset($option['onAccept.'])) {
					$onAccept = $cObj->cObjGetSingle($option['onAccept'], $option['onAccept.']);
					unset($option['onAccept.']);
				} else {
					$onAccept = $option['onAccept'];
				}
				$id = trim($key, '.');
				$option['inputName'] = 'consent-'.$id;
				$callbacks[] = '\''.$id.'\':()=>{'.$onAccept.'}';
				// $js .= 'consent.addCallback(\''.$id.'\', ()=>{'.$onAccept.'});'."\n";
			}
			$option['dismissable'] = (bool)($option['dismissable'] ?? true);
			$option['checked'] = !$option['dismissable'] || !empty($option['default']);
			$option['disabled'] = !$option['dismissable'];
			$data['options'][] = $option;
		}

		$js = "import PrivacyConsent from '".PathUtility::getPublicResourceWebPath('EXT:lpc_base/Resources/Public/JS/privacyconsent.js')."';\n";
		$js .= 'new PrivacyConsent({'.implode(',', $callbacks).'}';
		if (isset($processorConfiguration['onAcceptAll'])) {
			if (isset($processorConfiguration['onAcceptAll.'])) {
				$jsCode = $cObj->cObjGetSingle($processorConfiguration['onAcceptAll'], $processorConfiguration['onAcceptAll.']);
			} else {
				$jsCode = $processorConfiguration['onAcceptAll'];
			}
			$js .= ',()=>{'.$jsCode.'}';
		}
		$js .= ");\n";

		$pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
		$pageRenderer->addHeaderData("<script type=\"module\">\n$js</script>");
		$pageRenderer->addCssFile('EXT:lpc_base/Resources/Public/CSS/privacyconsent.css');

		if (isset($processorConfiguration['as'])) {
			$processedData[$processorConfiguration['as']] = $data;
		} else {
			$processedData += $data;
		}
		return $processedData;
    }
}
