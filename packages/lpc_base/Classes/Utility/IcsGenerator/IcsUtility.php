<?php

namespace LPC\LpcBase\Utility\IcsGenerator;

/**
 *
 * Author: Ahmad Amin, Michael Hadorn
 * Email : hello@ahmadamin.com
 * Website: http://ahmadmain.com
 *
 * Copyright (c) 2012 Ahmad Amin. All Rights Reserved.
 *
 * https://github.com/MrJack91/ics-generator
 * Fork: https://github.com/hising/ics-generator
 * 	based on commit Jun 7, 2015 (2ad7e4faa5e6ba5cf69ca857dc6522100b8e4ed7)
 *
 * @deprecated use LPC\LpcBase\Utility\ICalEvent
 */
class IcsUtility {

	/**
	 * The unique event id over all events
	 * @var string
	 */
	protected $_uid;

	/**
	 * Defines the filename for direct download
	 * @var string
	 */
	protected $_filename = '';

	/**
	 * The event real creation date of the event (not of this ics)
	 * @var \DateTime
	 */
	protected $_created;

	/**
	 * The event last modified (external)
	 * @var \DateTime
	 */
	protected $_lastModified;

	/**
	 * This property specifies the identifier for the product that created the iCalendar object.
	 * @var string
	 */
	protected $_prodid = 'LAUPER COMPUTING';

	/**
	 * If it is an allday event
	 * @var boolean
	 */
	protected $_allDay = false;

	/**
	 * The event start date
	 * @var \DateTime
	 */
	protected $_start;

	/**
	 * The event end date
	 * @var \DateTime
	 */
	protected $_end;

	/**
	 * The name of the user the invite is coming from
	 * @var string
	 */
	protected $_organizerName;

	/**
	 * Sender's email
	 * @var string
	 */
	protected $_organizerEmail;

	/**
	 * The invite description
	 * @var string
	 */
	protected $_description;

	/**
	 * The name of the event
	 * @var string
	 */
	protected $_summary;

	/**
	 * The event location
	 * @var string
	 */
	protected $_location;

	/**
	 *
	 * The content of the invite
	 * @var string
	 */
	protected $_generated;

	/**
	 * The list of attendees
	 * @var array
	 */
	protected $_attendees = array();
	protected $_savePath = "./invite/";

	/**
	 *
	 * Not downloaded constant
	 *
	 * @var const
	 */

	const NOT_DOWNLOADED = 5;

	/**
	 * Downloaded constant
	 * @var const
	 */
	const DOWNLOADED = 10;


	public function __construct($uid = null) {
		if (null === $uid) {
			$this->_uid = uniqid(rand(0, getmypid())) . "@laupercomputing.ch";
		} else {
			$this->_uid = $uid . "@laupercomputing.ch";
		}

		if (!isset($_SESSION['calander_invite_downloaded'])) {
			$_SESSION['calander_invite_downloaded'] = self::NOT_DOWNLOADED;
		}

		return $this;
	}

	/**
	 * Return the UID (construct param || random + "@laupercomputing.ch")
	 * @return string
	 */
	public function getUID() {
		return $this->_uid;
	}

	/**
	 * @return string
	 */
	public function getFilename() {
		if (strlen($this->_filename) == 0) {
			// if filename is not set, add default title
			$filename = $this->getStart()->format('Y-m-d') . '_' . $this->getSummary();
			$this->setFilename($filename);
		}
		$sanitized = $this->makeValidFileName($this->_filename, 'ics');
		return $sanitized;
	}

	/**
	 *
	 * @param $filename
	 * @param $fileExtension without point
	 * @return mixed
	 */
	protected function makeValidFileName($filename, $fileExtension) {
		$filename = str_replace(' ', '_', $filename);
		$sanitized = preg_replace('/[^a-zA-Z0-9-_\.]/','', $filename);
		// check if file Extension is ok
		if (substr($sanitized, -strlen($fileExtension)) !== $fileExtension) {
			$sanitized .= '.' . $fileExtension;
		}
		return $sanitized;
	}

	/**
	 * Set full filename with filetype
	 * @param string $filename
	 * @return $this
	 */
	public function setFilename($filename) {
		$this->_filename = $filename;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getProdid() {
		return $this->_prodid;
	}

	/**
	 * @param string $prodid
	 */
	public function setProdid($prodid) {
		$this->_prodid = $prodid;
	}

	/**
	 * @return boolean
	 */
	public function isAllDay() {
		return $this->_allDay;
	}

	/**
	 * @param boolean $allDay
	 * @return $this
	 */
	public function setAllDay($allDay) {
		$this->_allDay = $allDay;
		return $this;
	}

	/**
	 * Get the start time set for the even
	 * @param boolean $icsFormat
	 * @return string
	 */
	public function getStart($icsFormat = false) {
		if ($icsFormat) {
			return $this->icsAllDayDate($this->_start);
		}
		return $this->_start;
	}

	/**
	 * Set the start datetime
	 * @param \DateTime $start
	 * @return $this
	 */
	public function setStart(\DateTime $start) {
		$this->_start = $start;
		return $this;
	}

	/**
	 * Get the end time set for the event
	 * @param boolean $icsFormat
	 * @return string
	 */
	public function getEnd($icsFormat = false) {
		if ($icsFormat) {
			if ($this->isAllDay()) {
				// add one day for allday events
				$this->_end->add(new \DateInterval('P1D'));
			}
			return $this->icsAllDayDate($this->_end);
		}
		return $this->_end;
	}

	/**
	 * Set the end datetime
	 * @param \DateTime $end
	 * @return $this
	 */
	public function setEnd(\DateTime $end) {
		$this->_end = $end;
		return $this;
	}

	/**
	 * Return the date formated for ics incl handling of allday events
	 * @param $dateTime
	 * @return string
	 */
	protected function icsAllDayDate($dateTime) {
		$format = 'Ymd\THis';
		if ($this->isAllDay()) {
			// only add date (without time) if allday event
			$format = 'Ymd';
		}
		return self::icsDate($dateTime, $format);
	}

	/**
	 * Get the creation time set for the event
	 * @param bool $icsFormat
	 * @return string
	 * @internal param null $formatted
	 */
	public function getCreated($icsFormat = false) {
		if ($icsFormat) {
			// return $this->_created->format("Ymd\THis\Z");
			return self::icsDate($this->_created);
		}
		return $this->_created;
	}

	/**
	 * Set the creation datetime
	 * @param \DateTime $created
	 * @return $this
	 */
	public function setCreated(\DateTime $created) {
		$this->_created = $created;
		return $this;
	}

	/**
	 * @param bool $icsFormat
	 * @return \DateTime
	 */
	public function getLastModified($icsFormat = false) {
		if ($icsFormat) {
			// return $this->_created->format("Ymd\THis\Z");
			return self::icsDate($this->_lastModified);
		}
		return $this->_lastModified;
	}

	/**
	 * @param \DateTime $lastModified
	 * @return $this
	 */
	public function setLastModified($lastModified) {
		$this->_lastModified = $lastModified;
		return $this;
	}

	/**
	 * generate a time stamp
	 * @return string
	 */
	protected function getTimestamp() {
		$date = new \DateTime();
		return self::icsDate($date);
	}

	/**
	 * @param \DateTime $dateTime
	 * @return string|boolean
	 */
	protected static function icsDate($dateTime, $format = 'Ymd\THis') {
		$return = false;
		if (get_class($dateTime) == 'DateTime') {
			$return = $dateTime->format($format);
		}
		return $return;
	}

	/**
	 * Get the name of the invite sender
	 * @return string
	 */
	public function getOrganizerName() {
		return $this->_organizerName;
	}

	/**
	 * Get the email where the email will be sent from
	 * @return string
	 */
	public function getOrganizerEmail() {
		return $this->_organizerEmail;
	}

	/**
	 *
	 * Set the of the even sender
	 *
	 * @param string $email
	 * @param string $name
	 * @return $this
	 */
	public function setOrganizer($email, $name = null) {
		if (null === $name) {
			$name = $email;
		}
		$this->_organizerEmail = $email;
		$this->_organizerName = $name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->_description;
	}

	/**
	 * @param string $desc
	 * @return $this
	 */
	public function setDescription($desc) {
		$this->_description = $desc;
		return $this;
	}

	/**
	 *
	 * Get the event name
	 * @return string
	 */
	public function getSummary() {
		return $this->_summary;
	}

	/**
	 * Set the name of the event
	 * @param string $summary
	 * @return $this
	 */
	public function setSummary($summary) {
		$this->_summary = $summary;
		return $this;
	}

	/**
	 *
	 * Get the location where the event will be held
	 * @return string
	 */
	public function getLocation() {
		return $this->_location;
	}

	/**
	 *
	 * Set the location where the event will take place
	 * @param string $location
	 * @return $this
	 */
	public function setLocation($location) {
		$this->_location = $location;
		return $this;
	}

	/**
	 *
	 * Get all attendee that's currently set for an this events.
	 * @return array
	 *
	 */
	public function getAttendees() {
		return $this->_attendees;
	}

	/**
	 *
	 * Add a attendee to the list of attendees
	 * @param string $email
	 * @param string $name
	 * @return $this
	 */
	public function addAttendee($email, $name = null) {
		if (null === $name) {
			$name = $email;
		}

		if (!isset($this->_attendees[$email])) {
			$this->_attendees[$email] = $name;
		}

		return $this;
	}

	/**
	 *
	 * Remove a attendee from the list
	 *
	 * @param string $email
	 * @return $this
	 */
	public function removeAttendee($email) {
		if (isset($this->_attendees[$email])) {
			unset($this->_attendees[$email]);
		}

		return $this;
	}

	/**
	 *
	 * Clear a the attendee list
	 * @return $this
	 */
	public function clearAttendees() {
		$this->_attendees = array();
		return $this;
	}


	/**
	 *
	 * Call this function to download the invite.
	 */
	public function download() {
		$_SESSION['calander_invite_downloaded'] = self::DOWNLOADED;
		$generate = $this->_generate();
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"".$this->getFilename()."\"");
		header("Content-Transfer-Encoding: binary");
		header("Content-Length: " . strlen($generate));
		print $generate;
	}

	/**
	 *
	 * Save the invite to a file
	 *
	 * @param string $path
	 * @param string $name
	 * @return $this
	 *
	 */
	public function save($path = null, $name = null) {
		if (null === $path) {
			$path = $this->_savePath;
		}

		if (null === $name) {
			$name = $this->getUID() . '.ics';
		}

		// create path if it doesn't exist
		if (!is_dir($path)) {
			try {
				mkdir($path, 0777, TRUE);
			} catch (\Exception $e) {
				die('Unabled to create save path.');
			}
		}

		if (($data = $this->getInviteContent()) == TRUE) {
			try {
				$handler = fopen($path . $name, 'w+');
				fwrite($handler, $data);
				fclose($handler);

				// saving the save name
				$_SESSION['savepath'] = $path . $name;
			} catch (\Exception $e) {
				die('Unabled to write invite to file.');
			}
		}

		return $this;
	}

	/**
	 * Get the saved invite path
	 * @return string|boolean
	 */
	public static function getSavedPath() {
		if (isset($_SESSION['savepath'])) {
			return $_SESSION['savepath'];
		}

		return false;
	}


	/**
	 *
	 * Check to see if the invite has been downloaded or not
	 *
	 * @return boolean
	 *
	 */
	public static function isDownloaded() {
		if ($_SESSION['calander_invite_downloaded'] == self::DOWNLOADED) {
			return true;
		}

		return false;
	}

	public function isValid() {
		if ($this->_created || $this->_start || $this->_end || $this->_summary ||
			$this->_organizerEmail || $this->_organizerName || is_array($this->_attendees)
		) {
			return true;
		}

		return false;
	}

	/**
	 *
	 * Get the content of for and invite. Returns false if the invite
	 * was unable to be generated.
	 * @return string|boolean
	 */
	public function getInviteContent() {
		if (!$this->_generated) {
			if ($this->isValid()) {
				if ($this->_generate()) {
					return $this->_generated;
				}
			}
			return false;
		}

		return $this->_generated;
	}

	/**
	 *
	 * Generate the content for the invite.
	 *
	 * @return $this
	 *
	 */
	public function generate() {
		$this->_generate();
		return $this;
	}

	/**
	 *
	 * The function generates the actual content of the ICS
	 * file and returns it.
	 *
	 * @return string|bool
	 */
	protected function _generate() {
		define('CRLF', chr(13).chr(10));

		if ($this->isValid()) {

			$content = "BEGIN:VCALENDAR".CRLF;
			$content .= "VERSION:2.0".CRLF;
			$content .= "PRODID:" . $this->getProdid().CRLF;
			$content .= "CALSCALE:GREGORIAN".CRLF;
			$content .= "METHOD:PUBLISH".CRLF; // will ask in which calendar (at least on apple calendar)

			$timezoneIdentifier = '';
			if (!$this->isAllDay()) {
				// define timezone static -> will break in outlook if allday and timezone is set
				$timezoneIdentifier = ';TZID=Europe/Zurich';
				$content .= "BEGIN:VTIMEZONE".CRLF;
				$content .= "TZID:Europe/Zurich".CRLF;
				$content .= "X-LIC-LOCATION:Europe/Zurich".CRLF;
				$content .= "BEGIN:DAYLIGHT".CRLF;
				$content .= "TZOFFSETFROM:+0100".CRLF;
				$content .= "TZOFFSETTO:+0200".CRLF;
				$content .= "TZNAME:CEST".CRLF;
				$content .= "DTSTART:19700329T020000".CRLF;
				$content .= "RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=3".CRLF;
				$content .= "END:DAYLIGHT".CRLF;
				$content .= "BEGIN:STANDARD".CRLF;
				$content .= "TZOFFSETFROM:+0200".CRLF;
				$content .= "TZOFFSETTO:+0100".CRLF;
				$content .= "TZNAME:CET".CRLF;
				$content .= "DTSTART:19701025T030000".CRLF;
				$content .= "RRULE:FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=10".CRLF;
				$content .= "END:STANDARD".CRLF;
				$content .= "END:VTIMEZONE".CRLF;
			}

			$content .= "BEGIN:VEVENT".CRLF;
			$content .= "UID:{$this->getUID()}".CRLF;
			$content .= "DTSTART".$timezoneIdentifier.":{$this->getStart(true)}".CRLF;
			$content .= "DTEND".$timezoneIdentifier.":{$this->getEnd(true)}".CRLF;
			$content .= "DTSTAMP".$timezoneIdentifier.":{$this->getTimestamp()}".CRLF;
			$content .= "ORGANIZER;CN={$this->getOrganizerName()}:mailto:{$this->getOrganizerEmail()}".CRLF;

			foreach ($this->getAttendees() as $email => $name) {
				$content .= "ATTENDEE;PARTSTAT=NEEDS-ACTION;RSVP=TRUE;CN={$name};X-NUM-GUESTS=0:mailto:{$email}".CRLF;
			}

			// allow multiline textes in description field (replace real line breaks with textual "\n")
			$description = $this->getDescription();
			$description = str_replace("\r", "", $description);
			$description = str_replace("\n", "\\n", $description);
			$strippedDesc = strip_tags($description);

			$content .= "CREATED".$timezoneIdentifier.":{$this->getCreated(true)}".CRLF;
			$content .= "DESCRIPTION:{$strippedDesc}".CRLF;
			if($description != $strippedDesc) {
				$content .= "X-ALT-DESC;FMTTYPE=text/html:{$description}".CRLF;
			}
			$content .= "LAST-MODIFIED".$timezoneIdentifier.":{$this->getLastModified(true)}".CRLF;
			$content .= "LOCATION:{$this->getLocation()}".CRLF;
			$content .= "SUMMARY:{$this->getSummary()}".CRLF;
			$content .= "SEQUENCE:0".CRLF;
			// $content .= "STATUS:NEEDS-ACTION".CRLF;
			$content .= "TRANSP:OPAQUE".CRLF;
			$content .= "END:VEVENT".CRLF;
			$content .= "END:VCALENDAR";

			if ($_GET['debug'] == 'show') {
				print_r($content);
				exit();
			}

			$this->_generated = $content;
			return $this->_generated;
		}

		return false;
	}

}
