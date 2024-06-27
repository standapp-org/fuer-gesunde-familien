<?php
namespace LPC\LpcBase\ViewHelpers\Format;

use LPC\LpcBase\Utility\DateSpan;

class DateSpanViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
	protected $escapeOutput = false;

	public function initializeArguments(): void {
		$this->registerArgument('start', 'DateTimeInterface', '', true);
		$this->registerArgument('end', 'DateTimeInterface', '');
		$this->registerArgument('dayFormat', 'string', 'https://unicode-org.github.io/icu/userguide/format_parse/datetime/', false, 'dd.(MM.)(y)');
		$this->registerArgument('timeFormat', 'string', 'https://unicode-org.github.io/icu/userguide/format_parse/datetime/', false, 'HH:mm');
		$this->registerArgument('multiDayTimeFormat', 'string', 'time format to use if span covers more than one day');
		$this->registerArgument('dash', 'string', '', false, ' – ');
		$this->registerArgument('useIntl', 'boolean', 'use IntlDateFormatter');
	}

	public function render(): string {
		$dateSpan = new DateSpan($this->arguments['start'], $this->arguments['end']);
		$dateSpan->setDayFormat($this->arguments['dayFormat']);
		$dateSpan->setTimeFormat($dateSpan->isSingleDay()
			? $this->arguments['timeFormat']
			: ($this->arguments['multiDayTimeFormat'] ?? $this->arguments['timeFormat'] )
		);
		$dateSpan->setSeparator($this->arguments['dash']);
		if($this->hasArgument('useIntl')) {
			$dateSpan->setUseIntl($this->arguments['useIntl']);
		}

		return $dateSpan->render();
	}
}

