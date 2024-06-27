<?php
namespace LPC\LpcBase\ViewHelpers\Format;


class DurationViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
	public function initializeArguments() {
		$this->registerArgument('start','DateTimeInterface','');
		$this->registerArgument('end','DateTimeInterface','');
		$this->registerArgument('format','string','',false);
		$this->registerArgument('unit','string','the unit in which the duration is returned. one of s,i,h,d,m,y',false);
		$this->registerArgument('as','string','assign to this variable for rendering children',false);
		$this->registerArgument('partAs','string','assign part as [unit=>..,value=>..] for rendering children',false);
		$this->registerArgument('autoSepartor','string','auto formatting: use this string to separate parts',false,' ');
		$this->registerArgument('autoLastSeparator','string','auto formatting: use this string to separate the last part',false,' ');
	}

	public function render() {
		$value = $this->format();

		if(empty($this->arguments['as'])) {
			return $value;
		}

		$this->templateVariableContainer->add($this->arguments['as'],$value);
		$output = $this->renderChildren();
		$this->templateVariableContainer->remove($this->arguments['as']);
		return $output;
	}

	public function format() {
		$start = $this->arguments['start'];
		if(!$start) $start = new \DateTime();
		$end = $this->arguments['end'];
		if(!$end) $end = new \DateTime();

		$format = $this->arguments['format'];

		$interval = $end->diff($start);

		if(!empty($this->arguments['unit'])) {
			$seconds = $end->getTimestamp() - $start->getTimestamp();
			switch($this->arguments['unit']) {
				case 's':
					return $seconds;
				case 'i':
					return $seconds/60;
				case 'h':
					return $seconds/3600;
				case 'd':
					return $interval->days;
				case 'm':
					return $interval->y*12 + $interval->m;
				case 'y':
					return $interval->y;
			}
		}

		if(!empty($this->arguments['format'])) {
			return $interval->format($format);
		}

		// craft a smart duration string, you can use partAs argument to individually output parts
		$output = [];
		if($interval->y) {
			$output[] = $this->withUnit($interval->y,'y');
		}
		if($interval->m) {
			$output[] = $this->withUnit($interval->m,'m');
		}
		if($interval->days > 1) {
			if($w = floor($interval->d/7)) {
				$output[] = $this->withUnit($w,'w');
			}
			if($d = ($interval->d % 7)) {
				$output[] = $this->withUnit($d,'d');
			}
			if($interval->h) {
				$output[] = $this->withUnit($interval->h,'h');
			}
		} else {
			$m = $interval->i + ($interval->s ? 1 : 0);
			$h = $interval->h;
			$d = $interval->d;

			if($m == 60) {
				$m = 0;
				$h++;
			}
			if($h == 24) {
				$h = 0;
				$d++;
			}

			if($d) {
				$output[] = $this->withUnit($d,'d');
			}
			if($h) {
				$output[] = $this->withUnit($h,'h');
			}
			if($m) {
				$output[] = $this->withUnit($m,'i');
			}
		}

		$result = array_pop($output);
		if($output) {
			$result = implode($this->arguments['autoSepartor'],$output).$this->arguments['autoLastSeparator'].$result;
		}
		return $result;
	}

	private function withUnit($v,$unit) {
		if(!empty($this->arguments['partAs'])) {
			$this->templateVariableContainer->add($this->arguments['partAs'],['unit' => $unit, 'value' => $v]);
			$result = trim($this->renderChildren());
			$this->templateVariableContainer->remove($this->arguments['partAs']);

			if($result) {
				return $result;
			}
		}

		$key = [
			's' => 'second',
			'i' => 'minute',
			'h' => 'hour',
			'd' => 'day',
			'w' => 'week',
			'm' => 'month',
			'y' => 'year',
		][$unit].($v == 1 ? '' : 's');

		return $v.' '.\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key,'LpcBase');
	}
}

