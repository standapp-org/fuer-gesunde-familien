<?php
namespace LPC\LpcBase\Utility;

class TimeTracker
{
	private static $level = null;
	private static $metrics = [];
	private static $started = [];
	private static $descriptions = [];

	public static function isActive(): bool {
		if(self::$level === null) {
			self::$level = $GLOBALS['TYPO3_CONF_VARS']['LPC']['timetracker'] ?? 0;
			if(self::$level < 0) {
				self::$level = \TYPO3\CMS\Core\Utility\GeneralUtility::cmpIP(
					\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REMOTE_ADDR'),
					$GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask']
				) ? -self::$level : 0;
			}
		}
		return self::$level > 0;
	}

	public static function getMetrics() {
		return self::getMetricsRecursive(self::$metrics, '');
	}

	public static function start(string $metric, string $description = null): ?string {
		if(!self::isActive()) return null;
		$metric = self::startRecursive(explode('.', $metric), self::$metrics, microtime(true));
		if($description !== null && self::$level >= 2) {
			self::$descriptions[$metric] = $description;
		}
		return $metric;
	}

	public static function stop(?string $metric, string $description = null) {
		if(!self::isActive() || !$metric) return;
		self::stopRecursive(explode('.', $metric), self::$metrics, microtime(true));
		if($description !== null && self::$level >= 2) {
			self::$descriptions[$metric] = $description;
		}
	}

	private static function getMetricsRecursive(array $metrics, string $parentName) {
		foreach($metrics as $name => $metric) {
			$name = $parentName.($parentName ? '.' : '').$name;
			if(count($metric['sub']) !== 1) {
				$value = ['dur' => $metric['sum']];
				if(isset(self::$descriptions[$name])) {
					$value['desc'] = self::$descriptions[$name];
				}
				yield $name => $value;
			}
			yield from self::getMetricsRecursive($metric['sub'], $name);
		}
	}

	private static function startRecursive(array $path, array &$metrics, float $time): string {
		$name = array_shift($path);
		if($name === '#') {
			$metrics[] = [
				'on' => $time,
				'num' => 0,
				'sum' => 0,
				'sub' => [],
			];
			end($metrics);
			$name = key($metrics);
		} else if(!isset($metrics[$name])) {
			$metrics[$name] = [
				'on' => $time,
				'num' => 0,
				'sum' => 0,
				'sub' => [],
			];
		} else if(!isset($metrics[$name]['on'])) {
			$metrics[$name]['on'] = $time;
		}
		$metric = $name;
		if($path && (reset($path) !== '#' || self::$level >= 3)) {
			$metric .= '.'.self::startRecursive($path, $metrics[$name]['sub'], $time);
		} else {
			$metrics[$name]['num']++;
		}
		return $metric;
	}

	private static function stopRecursive(array $path, array &$metrics, float $time): bool {
		$name = array_shift($path);
		if(!isset($metrics[$name])) {
			throw new \Exception('cannot stop a timing metric that has not been started');
		}
		$childActive = false;
		if($path) {
			$childActive = self::stopRecursive($path, $metrics[$name]['sub'], $time);
			if(!$childActive) {
				foreach($metrics[$name]['sub'] as $sub) {
					if(isset($sub['on'])) {
						$childActive = true;
						break;
					}
				}
			}
		} else if($metrics[$name]['num'] > 0) {
			$metrics[$name]['num']--;
		} else {
			throw new \Exception('cannot stop a timing metric that has not been started');
		}
		if(!$childActive && $metrics[$name]['num'] === 0) {
			$metrics[$name]['sum'] += $time - $metrics[$name]['on'];
			unset($metrics[$name]['on']);
			return false;
		} else if(count($path) === 0 && $childActive) {
			throw new \Exception('cannot stop a timing metric  while sub metrics are still running');
		}
		return true;
	}
}
