<?php
namespace LPC\LpcBase\Domain\Type;

use LPC\LpcBase\Utility\LanguageUtility;
use TYPO3\CMS\Core\Type\TypeInterface;

class Date implements TypeInterface
{
	private string $date;

	public function __construct(string $date) {
		$this->date = $date;
	}

	public function __toString(): string {
		return $this->date;
	}

	public function format(string $format): string {
		$datetime = $this->getDateTime();
		return $datetime !== null ? $datetime->format($format) : '';
	}

	/**
	 * @param string|int $format
	 */
	public function formatIntl($format = \IntlDateFormatter::LONG, ?string $locale = null): string {
		$datetime = $this->getDateTime();
		if ($datetime === null) {
			return '';
		}

		$locale ??= LanguageUtility::getCurrentLocale();

		$formatter = is_int($format)
			? \IntlDateFormatter::create($locale, $format, \IntlDateFormatter::NONE)
			: \IntlDateFormatter::create($locale, \IntlDateFormatter::FULL, \IntlDateFormatter::NONE, null, null, $format);

		$formatted = $formatter->format($datetime);
		if (is_string($formatted)) {
			return $formatted;
		}

		throw new \Exception($formatter->getErrorMessage(), $formatter->getErrorCode());
	}

	public function getFull(): string {
		return $this->formatIntl(\IntlDateFormatter::FULL);
	}

	public function getLong(): string {
		return $this->formatIntl(\IntlDateFormatter::LONG);
	}

	public function getMedium(): string {
		return $this->formatIntl(\IntlDateFormatter::MEDIUM);
	}

	public function getShort(): string {
		return $this->formatIntl(\IntlDateFormatter::SHORT);
	}

	public function getDateTime(): ?\DateTime {
		if (empty($this->date)) {
			return null;
		}

		$datetime = \DateTime::createFromFormat('!Y-m-d', $this->date);
		if ($datetime === false) {
			$errors = \DateTime::getLastErrors();
			throw new \Exception($errors === false ? 'Cloud not parse date string.' : 'Error when parsing "'.$this->date.'": '.implode(' ', [...$errors['errors'], ...$errors['warnings']]));
		}
		return $datetime;
	}

	public static function createFromFormat(string $format, string $date): self {
		if ($format !== 'Y-m-d') {
			$date = \DateTime::createFromFormat($format, $date);
			if ($date === false) {
				$errors = \DateTime::getLastErrors();
				throw new \Exception($errors === false ? 'Cloud not parse date string.' : implode(' ', [...$errors['errors'], ...$errors['warnings']]));
			}
			$date = $date->format('Y-m-d');
		}
		return new self($date);
	}

	public function isEmpty(): bool {
		return strspn($this->date, '0-') === strlen($this->date);
	}

	public function getYear(): int {
		return (int)substr($this->date, 0, 4);
	}

	public function getMonth(): int {
		return (int)substr($this->date, 5, 2);
	}

	public function getDay(): int {
		return (int)substr($this->date, 8, 2);
	}
}
