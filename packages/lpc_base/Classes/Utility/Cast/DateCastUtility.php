<?php

namespace LPC\LpcBase\Utility\Cast;

/**
 * Class DateCastUtility
 * @author Michael Hadorn <michael.hadorn@laupercomputing.ch>
 * @package TYPO3
 * @subpackage
 * @package Lpc\LpcBase\Utility\Cast
 */
class DateCastUtility {

	public static $formatDate = 'd.m.y';
	public static $formatDatetime = 'd.m.Y H:i:s';

	/**
	 * Convert a int (timestamp), string (strtotime) to a dateobject
	 * @param int|string|dateobject $date
	 * @return \DateTime|null
	 */
	public static function getDateObject($date) {
		// not a datetime object
		if (!is_object($date) || !(get_class($date) == 'DateTime')) {
			// is timestamp, cast
			if (intval($date) > 0 && $date == intval($date)) {
				$timestamp = $date;
			} else if (is_string($date) && strlen($date) > 0 && $date !== '0000-00-00') {
				// if string cast to datetime object
				$timestamp = strtotime($date);
			}

			if ($timestamp) {
				$date = new \DateTime();
				$date->setTimestamp($timestamp);
			}
		}

		if (!is_object($date) || !(get_class($date) == 'DateTime')) {
			// invalid date: set to null
			$date = null;
		}
		return $date;
	}

	/**
	 * Gets the start and end date by a week number (with custom rules)
	 * @param int $week the week number (kw)
	 * @param int $dayOffset offset of days (1 = monday, 0/7 = sunday)
	 * @param int $year optional year (default is current year)
	 * @param bool|false $returnObject true if the date objects should be returned
	 * @param int $hour
	 * @param int $min
	 * @param int $sec
	 * @return array|string
	 */
	public static function getDateByWeekNumber($week, $dayOffset = 1, $year = 0, $returnObject = false, $hour = 0, $min = 0, $sec = 0) {
		$return = array();

		$week = intval($week);
		if ($week == 0) {
			return '';
		}

		$year = intval($year);
		if ($year == 0) {
			$year = date('Y');
		}

		$weekStart = new \DateTime();
		$weekStart->setISODate($year, $week, $dayOffset);
		$weekStart->setTime($hour, $min, $sec);
		$weekEnd = clone $weekStart;
		$weekEnd = $weekEnd->add(new \DateInterval('P7D'));

		$return['start'] = $weekStart->format(self::$formatDate);
		$return['end'] = $weekEnd->format(self::$formatDate);
		// $return['value'] = $return['start'] . ' - ' . $return['end'];
		$return['value'] = self::shortTimeRange($weekStart, $weekEnd);

		if ($returnObject) {
			$return['object'] = array();
			$return['object']['start'] = $weekStart;
			$return['object']['end'] = $weekEnd;
			return $return;
		} else {
			return $return['value'];
		}

	}

	/**
	 * Gets the calendar week by a date with custom rules
	 * @param null|\DateTime $date default is now
	 * @param int $dayOffset which day the week should start (1 = monday, 0/7 = sunday)
	 * @param int $hour the hour, the new week should start
	 * @param int $min the min, the new week should start
	 * @param int $sec the sec, the new week should start
	 * @return int week the week number based on the configuration
	 */
	public static function getWeekNumberByDate($date = null, $dayOffset = 1, $hour = 0, $min = 0, $sec = 0) {
		if (!$date instanceof \DateTime) {
			$date = new \DateTime();
		}
		// fix offset different to setIsoDate -> 0 is weekchange on sunday
		$date->add(new \DateInterval('P1D'));

		// sub the offset
		$date->sub(new \DateInterval('P'.$dayOffset.'DT'.$hour.'H'.$min.'M'.$sec.'S'));
		$week = $date->format('W');

		// the year can be different (special january cases...)
		$year = $date->format('Y');
		/** @var \DateTime $dateOfWeek */
		$dateOfWeek = self::getDateByWeekNumber($week, 0, $year, true, $hour = 0, $min = 0, $sec = 0);
		$dtStart = $dateOfWeek['object']['start'];
		if ($dtStart->getTimestamp() > $date->getTimestamp()) {
			$year--;
		}

		return array(
			'week' => $week,
			'year' => $year
		);
	}

	/**
	 * Short a from to date -> remove all obsolete (duplicate) information from first date
	 * @param \DateTime $dateFrom
	 * @param \DateTime $dateTo
	 * @param bool $alwaysShowMonth enforce to print month always
	 * @return string
	 */
	public static function shortTimeRange($dateFrom, $dateTo, $alwaysShowMonth = true) {
		if (!($dateTo instanceof \DateTime) || $dateFrom->format('d.m.Y') == $dateTo->format('d.m.Y')) {
			// fallback: return fromDate
			return $dateFrom->format('d.m.Y');
		}

		$timeRange = $dateFrom->format('d').'.';
		if ($dateFrom->format('m') !== $dateTo->format('m') || $alwaysShowMonth) {
			$timeRange .= $dateFrom->format('m') . '.';
		}
		if ($dateFrom->format('Y') !== $dateTo->format('Y')) {
			$timeRange .= $dateFrom->format('Y');
		}
		$timeRange .= ' - ' . $dateTo->format('d.m.Y');
		return $timeRange;
	}

	/**
	 * Gets the parts of two dates who are different. If one date is null, y is returned (= all is different)
	 * @param \DateTime|object $date1
	 * @param \DateTime|object $date2
	 * @return string 'd' [day], 'm' [month], 'y' [year] or empty if dates have the same day
	 */
	public static function getChangedDateParts($date1, $date2) {
		$date1 = self::getDateObject($date1);
		$date2 = self::getDateObject($date2);

		// empty date
		if (is_null($date1) || is_null($date2)) {
			return 'y';
		}

		// year has changed
		if ($date1->format('Y') - $date2->format('Y') <> 0) {
			return 'y';
		}

		// month has changed, but same year
		if ($date1->format('n') - $date2->format('n') <> 0) {
			return 'm';
		}

		// day has changed, but same month and year
		if ($date1->format('j') - $date2->format('j') <> 0) {
			return 'd';
		}
	}

	/**
	 * Search the last weekday before date
	 	!! needs fitting -> imported from gon
	 * @param $date timestamp of the date
	 * @param $day	the english 3-letter abbreviation (mon, tue, wed, thu, fri, sat, sun) - default: calendarStartDay from ts
	 * @return string with date
	 */
 	/*
	function findLastWeekday($date, $day = null) {
		if (is_null($day)) {
			$day = $this->calendarStartDay;
		}
		if (strtolower(date('D', $date)) !== strtolower($day)) {
			$date = strtotime('last ' . $day . ' ' . date('Y-m-d', $date));
		}
		$date = date('Y-m-d', $date);
		return $date;
	}
	*/

	/**
	 * Src: http://stackoverflow.com/questions/336127/calculate-business-days
	 *  used by LPC\LpcFlyer\Utility\SendDateUtility
	 * @param $tsFrom
	 * @param $tsTo
	 * @return int
	 */
	/*
	public static function numberOfWorkingDays($tsFrom, $tsTo) {
		$workingDays = [1, 2, 3, 4, 5]; # date format = N (1 = Monday, ...)
		// $holidayDays = ['*-12-25', '*-01-01', '2013-12-23']; # variable and fixed holidays

		$from = new \DateTime();
		$to = new \DateTime();
		$from->setTimestamp($tsFrom);
		$to->setTimestamp($tsTo);

		$to->modify('+1 day'); // including endday
		$interval = new \DateInterval('P1D');
		$periods = new \DatePeriod($from, $interval, $to);

		// better approach would be: count workdays of first and last weeks, add day (diff / 7) * 5
		$days = 0;
		foreach ($periods as $period) {
			if (!in_array($period->format('N'), $workingDays)) continue;
			// if (in_array($period->format('Y-m-d'), $holidayDays)) continue;
			// if (in_array($period->format('*-m-d'), $holidayDays)) continue;
			$days++;
		}
		return $days;
	}
	*/

}
