<?php
namespace LPC\LpcBase\Utility;

use TYPO3Fluid\Fluid\View\ViewInterface;

class ViewResolver extends \TYPO3\CMS\Extbase\Mvc\View\GenericViewResolver
{
	public function resolve(string $controllerObjectName, string $actionName, string $format): ViewInterface {
		if($format !== '' && $format !== 'html') {
			if(preg_match('/^(\w+\\\\\w+\\\\)Controller(\\\\\w+)Controller$/', $controllerObjectName, $match) === 1) {
				$class = $match[1].'View'.$match[2].'\\'.ucfirst($actionName).ucfirst($format);
				if(is_subclass_of($class, ViewInterface::class, true)) {
					return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($class);
				}
			}
		}
		return parent::resolve($controllerObjectName, $actionName, $format);
	}
}
