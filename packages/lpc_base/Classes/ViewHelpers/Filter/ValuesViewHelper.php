<?php
namespace LPC\LpcBase\ViewHelpers\Filter;

use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;

class ValuesViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
	protected $escapeOutput = false;

	public function initializeArguments(): void {
		$this->registerArgument('name','string','',true);
		$this->registerArgument('values','mixed'/*'iterable|string'*/,'',false);
		$this->registerArgument('labels','mixed'/*'iterable|string'*/,'',false);
		$this->registerArgument('separator','string','if values or labels are of type string, they get exploded by separator', false, ',');
		$this->registerArgument('valueKey','string','values is an array of objects/arrays. the value can be accessed under given key.',false);
		$this->registerArgument('labelKey','string','values is an array of objects/arrays. the label can be accessed under given key.',false);
		$this->registerArgument('assoc','bool','values contains value => label pairs',false,false);
	}

	public function render(): string {
		$list = $this->viewHelperVariableContainer->get(ListViewHelper::class, 'filterlist');

		$values = $this->hasArgument('values') ? $this->arguments['values'] : $this->renderChildren();
		if(!is_iterable($values)) {
			$values = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode($this->arguments['separator'], $this->arguments['values'], true);
		}

		$assoc = $this->arguments['assoc'] ?? false;

		if($this->hasArgument('labels')) {
			if($assoc) {
				throw new \InvalidArgumentException('labels must not be set if assoc is true');
			}
			$labels = $this->arguments['labels'];
			if(!is_iterable($labels)) {
				$labels = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode($this->arguments['separator'], $labels, true);
			}
			$combined = new \MultipleIterator;
			if (is_array($values)) {
				$values = new \ArrayIterator($values);
			} else if(!$values instanceof \Iterator) {
				$values = new \IteratorIterator($values);
			}
			$combined->attachIterator($values, 'value');
			$combined->attachIterator($this->arguments['labels'], 'label');
			$values = $combined;
		}

		if($this->hasArgument('valueKey') && $assoc) {
			throw new \InvalidArgumentException('valueKey must not be set if assoc is true');
		}

		foreach($values as $k => $v) {
			if($assoc) {
				$value = $k;
				$label = $v;
			} else if($this->hasArgument('labels')) {
				$value = $v['value'];
				$label = $v['label'];
			} else {
				$value = $v;
				$label = null;
			}
			if($this->hasArgument('labelKey')) {
				$label = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($label ?: $value, $this->arguments['labelKey']);
			}
			if($this->hasArgument('valueKey')) {
				$value = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($value, $this->arguments['valueKey']);
			}
			if($value instanceof AbstractDomainObject) {
				if(!$label) $label = (string)$value;
				$value = $value->getUid();
			}
			$list->addValue($this->arguments['name'], $value, $label);
		}

		return '';
	}
}

