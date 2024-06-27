<?php
namespace LPC\LpcBase\Utility;

use LPC\LpcBase\Http\StreamDecoratorTrait;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Http\SelfEmittableStreamInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class ICalEvent implements SelfEmittableStreamInterface
{
	use StreamDecoratorTrait;

	const TIMEZONE = [
		'TZID' => 'Europe/Zurich',
		'DAYLIGHT' => [
			'TZOFFSETFROM' => '+0100',
			'TZOFFSETTO' => '+0200',
			'TZNAME' => 'CEST',
			'DTSTART' => '19700329T020000',
			'RRULE' => 'FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=3',
		],
		'STANDARD' => [
			'TZOFFSETFROM' => '+0200',
			'TZOFFSETTO' => '+0100',
			'TZNAME' => 'CET',
			'DTSTART' => '19701025T030000',
			'RRULE' => 'FREQ=YEARLY;INTERVAL=1;BYDAY=-1SU;BYMONTH=10',
		],
	];

	private string $uid;
	private ?string $summary = null;
	private ?string $description = null;
	private string $productId = 'LAUPER COMPUTING';
	private \DateTimeInterface $start;
	private \DateTimeInterface $end;
	private bool $fullDay;
	private ?\DateTimeInterface $created = null;
	private ?\DateTimeInterface $modified = null;
	private ?string $organizerEmail = null;
	private ?string $organizerName = null;
	private ?string $location = null;
	private ?string $contact = null;

	/**
	 * @var array<string, string>
	 */
	private array $attendees = [];

	/**
	 * @var ?array{lat: float, lng: float}
	 */
	private ?array $coordinates = null;

	private ?string $rendered = null;

	public function __construct(\DateTimeInterface $start, \DateTimeInterface $end, bool $fullDay = false) {
		$this->start = $start;
		$this->end = $end;
		$this->fullDay = $fullDay;
	}

	public static function timespan(\DateTimeInterface $start, \DateTimeInterface $end): self {
		return new self($start, $end, false);
	}

	public static function singleDay(\DateTimeInterface $day): self {
		return new self($day, $day, true);
	}

	public static function multiDay(\DateTimeInterface $start, \DateTimeInterface $end): self {
		return new self($start, $end, true);
	}

	public function uid(string $uid): self {
		$this->uid = $uid;
		return $this;
	}

	public function uidByEntity(AbstractEntity $entity): self {
		$uid = $entity->getUid();
		if ($uid !== null) {
			$hash = md5(get_class($entity).':'.$uid, true);
		} else {
			$hash = random_bytes(16);
		}
		$hash = sodium_bin2base64($hash, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);

		$pid = $entity->getPid();
		$siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
		if ($pid === null) {
			$allSites = $siteFinder->getAllSites();
			ksort($allSites);
			$site = array_shift($allSites);
		} else {
			$site = $siteFinder->getSiteByPageId($pid);
		}
		$domain = $site === null
			? 'laupercomputing.ch'
			: $site->getBase()->getHost();

		return $this->uid($hash.'@'.$domain);
	}

	public function summary(string $summary): self {
		$this->summary = $summary;
		return $this;
	}

	public function description(string $description): self {
		$this->description = $description;
		return $this;
	}

	public function productId(string $productId): self {
		$this->productId = $productId;
		return $this;
	}

	public function created(\DateTimeInterface $created): self {
		$this->created = $created;
		return $this;
	}

	public function modified(\DateTimeInterface $modified): self {
		$this->modified = $modified;
		return $this;
	}

	public function organizer(string $email, string $name = ''): self {
		$this->organizerEmail = $email;
		$this->organizerName = $name;
		return $this;
	}

	public function location(string $location): self {
		$this->location = $location;
		return $this;
	}

	public function contact(string $contact): self {
		$this->contact = $contact;
		return $this;
	}

	public function attendee(string $email, string $name = ''): self {
		$this->attendees[$email] = $name;
		return $this;
	}

	public function coordinates(float $lat, float $lng): self {
		$this->coordinates = ['lat' => $lat, 'lng' => $lng];
		return $this;
	}

	public function toResponse(ResponseFactoryInterface $responseFactory): ResponseInterface {
		return $responseFactory
			->createResponse()
			->withBody($this)
			->withHeader('Content-Type', 'text/calendar');
	}

	protected function createStream(): StreamInterface {
		return GeneralUtility::makeInstance(StreamFactoryInterface::class)->createStream($this->render());
	}

	public function emit(): void {
		$this->rendered = null;
		$this->writeCalendar();
	}

	public function render(): string {
		$this->rendered = '';
		$this->writeCalendar();
		assert($this->rendered !== null);
		return $this->rendered;
	}

	private function writeCalendar(): void {
		$this->writeProperty('BEGIN', 'VCALENDAR');
		$this->writeProperty('VERSION', '2.0');
		$this->writeProperty('PRODID', $this->productId);
		$this->writeProperty('CALSCALE', 'GREGORIAN');
		$this->writeProperty('METHOD', 'PUBLISH'); // will ask in which calendar (at least on apple calendar)
		$this->writeTimezone();
		$this->writeEvent();
		$this->writeProperty('END', 'VCALENDAR');
	}

	private function writeTimezone(): void {
		$this->writeProperty('BEGIN', 'VTIMEZONE');
		foreach (self::TIMEZONE as $name => $value) {
			if (is_array($value)) {
				$this->writeProperty('BEGIN', $name);
				foreach($value as $sname => $svalue) {
					$this->writeProperty($sname, $svalue);
				}
				$this->writeProperty('END', $name);
			} else {
				$this->writeProperty($name, $value);
			}
		}
		$this->writeProperty('END', 'VTIMEZONE');
	}

	private function writeEvent(): void {
		$this->writeProperty('BEGIN', 'VEVENT');
		$this->writeProperty('UID', $this->uid ?? bin2hex(random_bytes(32)).'@laupercomputing.ch');

		$this->writeDateTime('DTSTART', $this->start, $this->fullDay);
		if (!$this->fullDay || $this->start->format('Ymd') !== $this->end->format('Ymd')) {
			$this->writeDateTime('DTEND', $this->end, $this->fullDay);
		}

		$this->writeMailto('ORGANIZER', $this->organizerEmail, $this->organizerName);
		foreach ($this->attendees as $email => $name) {
			$this->writeMailto('ATTNEDEE', $email, $name);
		}

		if ($this->description !== null) {
			$description = $this->escapeText($this->description);
			$stripped = strip_tags($description);
			$this->writeProperty('DESCRIPTION', $stripped);
			if ($stripped !== $description) {
				$this->writeProperty('X-ALT-DESC', $description, ['FMTTYPE' => 'text/html']);
			}
		}

		$this->writeDateTime('DTSTAMP', new \DateTime());
		$this->writeDateTime('CREATED', $this->created);
		$this->writeDateTime('LAST-MODIFIED', $this->modified);

		$this->writeProperty('LOCATION', $this->escapeText($this->location));
		if ($this->coordinates !== null) {
			$this->writeProperty('GEO', $this->coordinates['lat'].';'.$this->coordinates['lng']);
		}
		$this->writeProperty('CONTACT', $this->escapeText($this->contact));
		$this->writeProperty('SUMMARY', $this->escapeText($this->summary));
		$this->writeProperty('SEQUENCE', '0');
		$this->writeProperty('END', 'VEVENT');
	}

	private function writeDateTime(string $property, ?\DateTimeInterface $date, bool $dateOnly = false): void {
		if ($date === null) return;
		if ($dateOnly) {
			$this->writeProperty($property, $date->format('Ymd'), ['VALUE' => 'DATE']);
		} else {
			$date = \DateTime::createFromInterface($date);
			$date->setTimezone(new \DateTimeZone(self::TIMEZONE['TZID']));
			$this->writeProperty($property, $date->format('Ymd\\THis'), ['TZID' => self::TIMEZONE['TZID']]);
		}
	}

	private function writeMailto(string $property, ?string $email, ?string $name): void {
		if ($email === null) return;
		$this->writeProperty($property, 'MAILTO:'.$email, ['CN' => $name]);
	}

	/**
	 * @return ($value is null ? null : string)
	 */
	private function escapeText(?string $value): ?string {
		return $value === null ? null : strtr($value, [
			"\\" => "\\\\",
			";" => "\\;",
			"," => "\\,",
			"\n" => "\\n",
			"\r" => "",
		]);
	}

	/**
	 * @param array<string, null|string|string[]> $params
	 */
	private function writeProperty(string $name, ?string $value, array $params = []): void {
		if ($value === null) return;
		$line = $name;
		foreach ($params as $pname => $pvals) {
			if ($pvals === null) continue;
			$line .= ';' . $pname . '=';
			$first = true;
			foreach (is_array($pvals) ? $pvals : [$pvals] as $pval) {
				if ($first) {
					$first = false;
				} else {
					$line .= ',';
				}
				if (strcspn($pval, ':;,') === strlen($pval)) {
					$line .= $pval;
				} else {
					$line .= '"'.$pval.'"';
				}
			}
		}
		$line .= ':' . $value;

		$length = mb_strlen($line, '8bit');
		$firstLine = true;
		$p = 0;
		while ($p < $length) {
			$chunk = mb_strcut($line, $p, $firstLine ? 73 : 72);
			$p += mb_strlen($chunk, '8bit');
			$chunk = ($firstLine ? '' : ' ').$chunk."\r\n";
			if ($this->rendered === null) {
				echo $chunk;
			} else {
				$this->rendered .= $chunk;
			}
			$firstLine = false;
		}
	}
}
