<?php
namespace LPC\LpcPetition\Domain\Model;

class Field extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
	protected string $type = '';

	protected string $name = '';

	/**
	 * @var ?array<mixed>
	 */
	private ?array $options = null;

	protected string $serializedOptions;

	/**
	 * @var bool
	 */
	protected $mandatory = false;

	/**
	 * @var string
	 */
	protected $title = '';

	public function getType(): string {
		return $this->type;
	}

	public function setType(string $type): void {
		$this->type = $type;
	}

	public function getName(): string {
		return $this->name;
	}

	public function setName(string $name): void {
		$this->name = $name;
	}

	/**
	 * @return array<string, string>
	 */
	public function getOptions(): array {
		if($this->options === null) {
			$this->options = [];
			preg_match_all('/^\s*(?<label>.+?)(?:\s*\|\s*(?<value>.+))?\s*$/m', $this->serializedOptions, $matches, PREG_SET_ORDER);
			foreach($matches as $match) {
				$this->options[$match['value'] ?? $match['label']] = $match['label'];
			}
		}
		return $this->options;
	}

	/**
	 * @param array<mixed> $options
	 */
	public function setOptions(array $options): void {
		$this->options = $options;
		// update is never called, so serialization into ->options is not required
	}

	public function getMandatory(): bool {
		return $this->mandatory;
	}

	public function setMandatory(bool $mandatory): void {
		$this->mandatory = $mandatory;
	}

	public function getTitle(): string {
		return $this->title;
	}

	public function setTitle(string $title): void {
		$this->title = $title;
	}
}
