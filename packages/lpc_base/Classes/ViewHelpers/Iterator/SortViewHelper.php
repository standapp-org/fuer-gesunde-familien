<?php
namespace LPC\LpcBase\ViewHelpers\Iterator;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * sorts and returns an iterable type by rendering e.g. a section or partial
 * usage example:
 *  <f:section name="llDistrictName"><f:translate key="LLL:EXT:lpc_base/Resources/Private/Language/cantons.xlf:{district.name}" /></f:section>
 *  <f:for each="{lpc:iterator.sort(subject:election.districts,itemAs:'district',section:'llDistrictName',flags:'SORT_LOCALE_STRING')}" as="district">...</f:for>
 */
class SortViewHelper extends \TYPO3Fluid\Fluid\ViewHelpers\RenderViewHelper
{
	public function initializeArguments()
	{
		parent::initializeArguments();
		unset($this->argumentDefinitions['contentAs']);
		$this->registerArgument('subject','mixed','');
		$this->registerArgument('itemAs','string','');
		$this->registerArgument('order','string','asc|desc',false,'asc');
		$this->registerArgument('flags','string','flags for php\'s sort() separated by |',false,'SORT_REGULAR');
	}

	public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
	{
		$subject = $arguments['subject'];

		if(!is_array($subject)) {
			$subject = iterator_to_array($subject);
		}
		$contents = [];
		foreach($subject as $key => $item) {
			$arguments['arguments'][$arguments['itemAs']] = $item;
			$contents[$key] = parent::renderStatic($arguments,$renderChildrenClosure,$renderingContext);
		}

		if(isset($arguments['flags'])) {
			$flags = 0;
			foreach(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|',$arguments['flags'],true) as $flag) {
				$flags |= constant($flag);
			}
		} else {
			$flags = SORT_REGULAR;
		}

		$order = isset($arguments['order']) && strtolower($arguments['order']) == 'desc' ? SORT_DESC : SORT_ASC;

		array_multisort($contents,$order,$flags,$subject);
		return $subject;
	}
}
