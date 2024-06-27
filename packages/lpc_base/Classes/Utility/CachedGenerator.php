<?php
namespace LPC\LpcBase\Utility;

/**
 * @template TKey
 * @template TValue
 * @template TReturn
 * @implements \IteratorAggregate<TKey, TValue>
 * @implements \ArrayAccess<TKey, TValue>
 */
class CachedGenerator implements \IteratorAggregate, \ArrayAccess, \Countable
{
	/**
	 * @var \Generator<TKey, TValue, void, TReturn>
	 */
	private $generator;

	/**
	 * @var list<array{0: TKey, 1: TValue}|false>
	 */
	private $cache = [];

	/**
	 * @param \Generator<TKey, TValue, void, TReturn> $generator
	 */
	public function __construct(\Generator $generator) {
		$this->generator = $generator;
	}

	/**
	 * @return \Generator<TKey, TValue>
	 */
	#[\ReturnTypeWillChange]
	public function getIterator(): \Generator {
		$cachePtr = 0;
		while(true) {
			if(isset($this->cache[$cachePtr])) {
				if($this->cache[$cachePtr] !== false) {
					yield $this->cache[$cachePtr][0] => $this->cache[$cachePtr][1];
				}
				$cachePtr++;
			} else if($this->generator->valid()) {
				$key = $this->generator->key();
				$value = $this->generator->current();
				$this->cache[] = [$key, $value];
				$this->generator->next();
			} else {
				return;
			}
		}
	}

	/**
	 * @return TReturn
	 */
	public function getReturn() {
		return $this->generator->getReturn();
	}

	/**
	 * @param TKey $key
	 * @return ?TValue
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($key) {
		foreach($this as $k => $v) {
			if($key == $k) {
				return $v;
			}
		}
		return null;
	}

	/**
	 * @param TKey $key
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists($key) {
		foreach($this as $k => $v) {
			if($key == $k) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param TKey $key
	 * @param TValue $value
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet($key, $value) {
		$i = 0;
		foreach($this as $k => $v) {
			if($key == $k && $this->cache[$i] !== false) {
				$this->cache[$i][1] = $v;
				return;
			}
			$i++;
		}
		$this->cache[] = [$key, $value];
	}

	/**
	 * @param TKey $key
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset($key) {
		$i = 0;
		foreach($this as $k => $v) {
			if($key == $k) {
				$this->cache[$i] = false;
				return;
			}
			$i++;
		}
	}

	#[\ReturnTypeWillChange]
	public function count() {
		foreach($this as $v);
		return count($this->cache);
	}
}
