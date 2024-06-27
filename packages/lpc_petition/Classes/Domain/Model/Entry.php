<?php
namespace LPC\LpcPetition\Domain\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;

class Entry extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
	protected bool $hidden = false;

	protected ?\DateTime $crdate = null;

	protected string $firstname = '';

	protected string $lastname = '';

	protected string $address = '';

	protected string $zip = '';

	protected string $place = '';

	protected string $canton = '';

	protected string $country = '';

	protected string $mail = '';

	protected string $title = '';

	protected ?\DateTime $birthday = null;

	protected string $phone = '';

	protected string $comment = '';

	protected int $private = 0;

	protected int $newsletter = 0;

	protected int $allowReuse = 0;

	/**
	 * @var ?array<mixed>
	 */
	protected ?array $fieldData = null;

	protected string $fieldDataJson;

	public function getHidden(): bool {
		return $this->hidden;
	}

	public function setHidden(bool $hidden): void {
		$this->hidden = $hidden;
	}

	public function getCrdate(): ?\DateTime {
		return $this->crdate;
	}

	public function getLastname(): string {
		return $this->lastname;
	}

	public function getFirstLetter(): string {
		$firstLetter = mb_substr($this->lastname, 0, 1);
		$firstLetter = strtoupper(substr(iconv('UTF-8', 'ASCII//TRANSLIT', $firstLetter) ?: $firstLetter, 0, 1));
		if (ctype_alpha($firstLetter)) {
			// normal uppercase letter
			return $firstLetter;
		} else if (ctype_digit($firstLetter)) {
			// starting with a number
			return '0-9';
		} else {
			// starting with something exotic
			return '#';
		}
	}

	public function setLastname(string $lastname): void {
		$this->lastname = $lastname;
	}

	public function getFirstname(): string {
		return $this->firstname;
	}

	public function setFirstname(string $firstname): void {
		$this->firstname = $firstname;
	}

	public function getAddress(): string {
		return $this->address;
	}

	public function setAddress(string $address): void {
		$this->address = $address;
	}

	public function getZip(): string {
		return $this->zip;
	}

	public function setZip(string $zip): void {
		$this->zip = $zip;
	}

	public function getPlace(): string {
		return $this->place;
	}

	public function setPlace(string $place): void {
		$this->place = $place;
	}

	public function getCanton(): string {
		return $this->canton;
	}

	public function setCanton(string $canton): void {
		$this->canton = $canton;
	}

	public function getCountry(): string {
		return $this->country;
	}

	public function setCountry(string $country): void {
		$this->country = $country;
	}

	public function getMail(): string {
		return $this->mail;
	}

	public function setMail(string $mail): void {
		$this->mail = $mail;
	}

	public function getTitle(): string {
		return $this->title;
	}

	public function setTitle(string $title): void {
		$this->title = $title;
	}

	public function getBirthday(): ?\DateTime {
		return $this->birthday;
	}

	public function setBirthday(?\DateTime $birthday): void {
		$this->birthday = $birthday;
	}

	public function getPhone(): string {
		return $this->phone;
	}

	public function setPhone(string $phone): void {
		$this->phone = $phone;
	}

	public function getComment(): string {
		return $this->comment ?? '';
	}

	public function setComment(string $comment): void {
		$this->comment = $comment;
	}

	public function getPrivate(): int {
		return $this->private;
	}

	public function setPrivate(int $private): void {
		$this->private = $private;
	}

	public function getNewsletter(): int {
		return $this->newsletter;
	}

	public function setNewsletter(?int $newsletter): void {
		$this->newsletter = $newsletter ?? 0;
	}

	public function getAllowReuse(): int {
		return $this->allowReuse;
	}

	public function setAllowReuse(int $allowReuse): void {
		$this->allowReuse = $allowReuse;
	}

	/**
	 * @return array<mixed>
	 */
	public function getFieldData(): array {
		if($this->fieldData === null) {
			$this->fieldData = $this->fieldDataJson ? json_decode($this->fieldDataJson, true) : [];
		}
		return $this->fieldData;
	}

	/**
	 * @param array<mixed> $fieldData
	 */
	public function setFieldData(array $fieldData): void {
		$this->fieldData = $fieldData;
		$this->fieldDataJson = json_encode($fieldData, JSON_THROW_ON_ERROR);
	}

	public function getOptInHash(): string {
		return GeneralUtility::makeInstance(HashService::class)->appendHmac(base_convert((string)$this->uid, 10, 36));
	}
}
