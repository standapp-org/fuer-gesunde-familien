<?php
namespace LPC\LpcBase\Utility;

class DateSpan
{
	private \DateTimeInterface $start;
	private ?\DateTimeInterface $end;
	private string $dayFormat = 'd.(m.)(Y)';
	private string $timeFormat = 'H:i';
	private string $separator = ' – ';
	private ?bool $useIntl = null;

	public function __construct(\DateTimeInterface $start, \DateTimeInterface $end = null) {
		$this->start = $start;
		$this->end = $end;
	}

	public function setDayFormat(string $dayFormat): void {
		$this->dayFormat = $dayFormat;
	}

	public function setTimeFormat(string $timeFormat): void {
		$this->timeFormat = $timeFormat;
	}

	public function setSeparator(string $separator): void {
		$this->separator = $separator;
	}

	public function setUseIntl(bool $useIntl): void {
		$this->useIntl = $useIntl;
	}

	public function render(): string {
		if($this->useIntl === null) {
			$this->useIntl = strpos($this->dayFormat, '%') === false && strpos($this->timeFormat, '%') === false
				&& (preg_match('/([a-zA-Z])\1+/', $this->dayFormat) === 1 || preg_match('/([a-zA-Z])\1+/', $this->timeFormat) === 1);
		}

		$start = $this->start;
		$end = $this->end;

		$allDay = $start->format('His') == '000000' && ($end === null || $end->format('His') == '000000');

		// if only days are shown and end is at midnight, subtract one day from end
// 		if($end !== null && ($allDay || ($this->timeFormat == '' && $end->format('His') == '000000'))) {
// 			$end = $end instanceof \DateTimeImmutable ? \DateTime::createFromImmutable($end) : clone $end;
// 			$end = $end->sub(new \DateInterval('P1D'));
// 		}

		$s = [];
		if($end !== null && $end != $start && ($allDay || $this->timeFormat == '')) {
			$startDayFormat = preg_replace_callback('/\(([^)]*)\)/',function($f) use ($start,$end) {
				return $this->formatDate($start,$f[1]) == $this->formatDate($end,$f[1]) ? '' : $f[1];
			},$this->dayFormat);
			if ($startDayFormat === null) {
				throw new \Exception(preg_last_error_msg());
			}
		} else {
			$startDayFormat = str_replace(['(',')'],'',$this->dayFormat);
		}
		$s[0] = $this->formatDate($start, $startDayFormat);
		if(!$allDay) {
			$s[1] = ' '.$this->formatDate($start,$this->timeFormat);
		}
		if($end !== null && $end != $start) {
			$endDayFormat = str_replace(['(',')'],'',$this->dayFormat);
			$endDay = $this->formatDate($end,$endDayFormat);
			if($this->formatDate($start,$endDayFormat) != $endDay) {
				$s[2] = $endDay;
			} else {
				$s[0] = $this->formatDate($start,$endDayFormat);
			}
			if(!$allDay) {
				if($this->formatDate($end, 'H:i') != '00:00') $s[3] = $this->formatDate($end,$this->timeFormat);
			}
		}
		return implode($this->separator,array_filter([
			trim($s[0].' '.trim($s[1] ?? '')),
			trim(($s[2] ?? '').' '.trim($s[3] ?? '')),
		]));
	}

	public function isSingleDay(): bool {
		return $this->end === null || $this->start->format('Ymd') == $this->end->format('Ymd');
	}

	private function formatDate(\DateTimeInterface $date, string $format): string {
		if($this->useIntl === true) {
			return \IntlDateFormatter::formatObject($date, $format, $GLOBALS['TYPO3_REQUEST']->getAttribute('language')->getLocale());
		} else {
			return $this->formatDateLegacy($date, $format);
		}
	}

	private function formatDateLegacy(\DateTimeInterface $date, string $format): string {
		$parts = preg_split('/((?:%[0-9]*)?[a-zA-Z])/', $format, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		if ($parts === false) {
			throw new \Exception(preg_last_error_msg());
		}
		$mode = 0;
		$format = '';
		$result = '';

		foreach($parts as $part) {
			if(ctype_alpha($part[0])) {
				if($mode === 2 && $format !== '') {
					$result .= strftime($format, $date->getTimestamp());
					$format = '';
				}
				$mode = 1;
			} else if($part[0] == '%') {
				if($mode == 1 && $format !== '') {
					$result .= $date->format($format);
					$format = '';
				}
				if(ctype_digit($part[1])) {
					if($mode == 2 && $format !== '') {
						$result .= strftime($format, $date->getTimestamp());
						$format = '';
					}
					$result .= substr(strftime('%'.substr($part,-1), $date->getTimestamp()) ?: '', 0, (int)substr($part,1,-1));
					$mode = 0;
					continue;
				} else {
					$mode = 2;
				}
			}
			$format .= $part;
		}
		if($format !== '') {
			if($mode == 1) {
				$result .= $date->format($format);
			} else {
				$result .= strftime($format, $date->getTimestamp());
			}
		}
		return $result;
	}

	public function __toString(): string {
		return $this->render();
	}
}
