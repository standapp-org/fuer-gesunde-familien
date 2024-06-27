<?php
namespace LPC\LpcBase\Routing;

class TCASlugHelper
{
	public static function modifySlug(array $params): string {
		$aspectConfig = $params['configuration']['generatorOptions']['aspectConfig'];
		$aspectClassName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects'][$aspectConfig['type']];
		$aspectConfig['pathSegmentFieldName'] = $params['fieldName'];
		$aspect = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($aspectClassName, $aspectConfig);
		if($aspect instanceof AbstractGeneratedAliasMapper) {
			return $aspect->generate($params['record']['uid']);
		}
		return $params['slug'];
	}
}
