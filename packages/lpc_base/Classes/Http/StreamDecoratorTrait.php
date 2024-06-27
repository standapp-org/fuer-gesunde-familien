<?php
namespace LPC\LpcBase\Http;

use Psr\Http\Message\StreamInterface;

trait StreamDecoratorTrait
{
	private ?StreamInterface $stream = null;

	abstract protected function createStream(): StreamInterface;

	protected function getStream(): StreamInterface {
		return $this->stream ??= $this->createStream();
	}

	public function __toString(): string {
		if ($this->isSeekable()) {
			$this->seek(0);
		}
		return $this->getContents();
    }

    public function close(): void {
		$this->stream?->close();
    }

	/**
	 * @return ?resource
	 */
    public function detach() {
		return $this->getStream()->detach();
    }

	public function getSize(): ?int {
		return $this->getStream()->getSize();
	}

    public function tell(): int {
		return $this->getStream()->tell();
    }

    public function eof(): bool {
		return $this->getStream()->eof();
    }

    public function isSeekable(): bool {
		return $this->getStream()->isSeekable();
	}

    public function seek($offset, $whence = 0): void {
		$this->getStream()->seek($offset, $whence);
    }

    public function rewind(): void {
		$this->getStream()->rewind();
    }

    public function isWritable(): bool {
		return $this->getStream()->isWritable();
    }

    public function write($string): int {
		return $this->getStream()->write($string);
    }

    public function isReadable(): bool {
		return $this->getStream()->isReadable();
    }

    public function read($length): string {
		return $this->getStream()->read($length);
    }

    public function getContents(): string {
		return $this->getStream()->getContents();
    }

    public function getMetadata($key = null): mixed {
		return $this->getStream()->getMetadata($key);
    }
}
