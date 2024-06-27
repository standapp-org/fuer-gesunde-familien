<?php
namespace LPC\LpcPetition\Hook;

use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

// class PetitionFormPreviewRenderer implements \TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface
// {
// 	public function preProcess(
// 		\TYPO3\CMS\Backend\View\PageLayoutView &$parentView,
// 		&$drawItem,
// 		&$headerContent,
// 		&$itemContent,
// 		array &$row
// 	) {
// 		if($row['CType'] === 'list' && $row['list_type'] === 'lpcpetition_form' && $GLOBALS['BE_USER']->check('tables_select', 'tx_lpcpetition_domain_model_entry')) {
// 			$itemContent .= '<p><b>'.\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:lpc_petition/Resources/Private/Language/locallang_be.xlf:plugin.form.title').'</b></p>';
// 			$uri = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class)
// 				->buildUriFromRoute('lpcpetition_export', ['ce' => $row['uid']]);
// 			$itemContent .= '<p><a class="btn btn-sm btn-default" href="'.$uri.'"><i class="fa fa-download"></i> CSV exportiern</a></p>';
// 			$drawItem = false;
// 		}
// 	}
// }

class PetitionFormPreviewRenderer extends StandardContentPreviewRenderer
{
	public function renderPageModulePreviewContent(GridColumnItem $item): string {
		$content = parent::renderPageModulePreviewContent($item);
		if ($item->getContext()->getBackendUser()->check('tables_select', 'tx_lpcpetition_domain_model_entry')) {
			$uri = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class)
				->buildUriFromRoute('lpcpetition_export', ['ce' => $item->getRecord()['uid']]);
			$content .= '<p><a class="btn btn-sm btn-default" href="'.$uri.'"><i class="fa fa-download"></i> CSV exportiern</a></p>';
		}
		return $content;
	}
}
