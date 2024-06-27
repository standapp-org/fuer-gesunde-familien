<?php
namespace LPC\LpcBase\ViewHelpers\Format;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class ImplodeObjectsViewHelper
 * A view helper to implode properties of objects (e.g. implode all titles of categories)
 *
 * E.g.
 * {lpcBase:format.implodeObjects(collection: '{event.categories}', accessPaths: {0: 'getTitle'}, glue: '|', additionalElements: {0: "{f:translate(key: 'list.allCategory')}"})}
 *
 * @author Michael Hadorn <michael.hadorn@laupercomputing.ch>
 * @package TYPO3
 * @subpackage
 * @package Lpc\LpcBase\ViewHelpers
 *
 * @deprecated
 * use {collection -> v:iterator.extract(key:'property') -> v:iterator.implode(glue:', ')} (collection must be non empty)
 * additional Elements: {collection -> v:iterator.extract(key:'property') -> v:iterator.merge(b:'string or array of strings') -> v:iterator.implode(glue:', ')}
 */
class ImplodeObjectsViewHelper extends AbstractViewHelper {

	/**
	 * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
	 */
	public function initializeArguments() {
		$this->registerArgument('collection', 'mixed', 'defines the object to implode', true);
		$this->registerArgument('accessPaths', 'mixed', 'defines the methods to call to get a string to implode', false, array());
		$this->registerArgument('glue', 'string', 'defines the glue for implode', false, ', ');
		$this->registerArgument('additionalElements', 'mixed', 'this elements will also be added', false, array());
	}

	public function render() {
		$collection = $this->arguments['collection'];
		$accessPaths = $this->arguments['accessPaths'];
		$glue = $this->arguments['glue'];
		$additionalElements = $this->arguments['additionalElements'];

		$parts = array();

		foreach ($collection as $capsula) {
			foreach ($accessPaths as $accessPath) {
				if (method_exists($capsula, $accessPath)) {
					$capsula = $capsula->{$accessPath}();
				} else {
					$capsula = '';
				}
			}
			$parts[] = $capsula;
		}

		$parts = array_merge($parts, $additionalElements);
		$content = implode($glue, $parts);

		return $content;
	}
}
