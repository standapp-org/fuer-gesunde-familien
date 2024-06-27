<?php
if (!defined('TYPO3')) {
	die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('lpc_base', 'Configuration/TypoScript/FilterListStyles', 'Filter List CSS');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_lpcbase_domain_model_sociallink');
