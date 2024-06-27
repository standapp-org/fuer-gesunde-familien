<?php
namespace LPC\LpcBase\Persistence;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * @template TEntity
 * @implements QueryResultInterface<TEntity>
 */
class EmptyResult implements QueryResultInterface
{
	/**
	 * @return int
	 * @phpstan-return 0
	 */
	#[\ReturnTypeWillChange]
	public function count() {
		return 0;
	}

	/**
	 * @return bool
	 * @phpstan-return false
	 */
	#[\ReturnTypeWillChange]
	public function current() {
		return false;
	}

	/**
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function next() {}

	/**
	 * @return ?string
	 * @phpstan-return null
	 */
	#[\ReturnTypeWillChange]
	public function key() {
		return null;
	}

	/**
	 * @return bool
	 * @phpstan-return false
	 */
	#[\ReturnTypeWillChange]
	public function valid() {
		return false;
	}

	/**
	 * @return void
	 */
	#[\ReturnTypeWillChange]
	public function rewind() {}

	/**
	 * @return bool
	 * @phpstan-return false
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists($offset) {
		return false;
	}

	/**
	 * @return ?TEntity
	 * @phpstan-return null
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset) {
		return null;
	}

	/**
	 * @param mixed $offset
	 * @param TEntity $value
	 * @return void
	 * @phpstan-return never
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet($offset, $value) {
		throw new \BadMethodCallException('Cannot mutate '.self::class);
	}

	/**
	 * @param mixed $offset
	 * @return void
	 * @phpstan-return never
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset($offset) {
		throw new \BadMethodCallException('Cannot mutate '.self::class);
	}

	/**
	 * @return never
	 */
	public function getQuery() {
		throw new \BadMethodCallException('Empty results have no query');
	}

	/**
	 * @return never
	 */
	public function setQuery(QueryInterface $query): void {
		throw new \BadMethodCallException('Empty results have no query');
	}

	/**
	 * @return ?TEntity
	 * @phpstan-return null
	 */
	public function getFirst() {
		return null;
	}

	/**
	 * @return array
	 * @phpstan-return array{}
	 */
	public function toArray(): array {
		return [];
	}
}
