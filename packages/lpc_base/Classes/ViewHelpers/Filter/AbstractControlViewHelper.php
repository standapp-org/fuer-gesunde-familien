<?php
namespace LPC\LpcBase\ViewHelpers\Filter;

abstract class AbstractControlViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
{
	protected function registerCommonControlArguments(): void {
		$this->registerArgument('name', 'string', '', true);
		$this->registerArgument('label', 'string', '', true);
		$this->registerArgument('sorting', 'string', 'asc|desc|none', false, 'asc');
		$this->registerArgument('sortByLabel', 'boolean', '', false, false);
		$this->registerArgument('hideIfEmpty', 'bool', 'hide the control if no option is selectable', false, true);
	}

	public function render(): string {
		return $this->viewHelperVariableContainer
			->get(ListViewHelper::class, 'filterlist')
			->addFilterControl($this, $this->arguments['name']);
	}

	protected function getLabel(): string {
		return $this->arguments['label'];
	}

	public function hideIfEmpty(): bool {
		return $this->arguments['hideIfEmpty'];
	}

	/**
	 * @param array<string, string> $options
	 */
	abstract public function renderControl(array $options, string $id): string;

	abstract public function renderScript(string $callback, string $id): string;

	/**
	 * @param array<string, string> $options
	 * @return array<string, string>
	 */
	protected function sortOptions(array $options): array {
		if($this->arguments['sorting'] === 'none') {
			return $options;
		}
		if($this->arguments['sortByLabel'] === false) {
			$sortFlag = SORT_REGULAR;
			foreach($options as $k => $v) {
				if(!ctype_digit($k)) {
					$sortFlag = SORT_LOCALE_STRING;
					break;
				}
			}
			if($this->arguments['sorting'] !== 'desc') {
				ksort($options, $sortFlag);
			} else {
				krsort($options, $sortFlag);
			}
		} else {
			if($this->arguments['sorting'] !== 'desc') {
				asort($options, SORT_LOCALE_STRING);
			} else {
				arsort($options, SORT_LOCALE_STRING);
			}
		}
		return $options;
	}
}
