<?php

namespace LPC\LpcBase\Utility;

/**
 * Class CastUtility
 *
 * @author Michael Hadorn <michael.hadorn@laupercomputing.ch>
 * @package TYPO3
 * @subpackage
 * @package Lpc\LpcSermons\Utility
 */
class FileUtility {

	/**
	 * Generate a unique file name
	 * @param $prefix
	 * @param $extension
	 * @param $path
	 * @return string
	 */
	public static function getUniqueFilename($prefix, $extension, $path) {
		$i = 0;
		while (true && $i < 100) {
			$i++;
			$fileName = uniqid($prefix, true) . $extension;
			if (!file_exists($path . $fileName)) break;
		}
		return $fileName;
	}

	/**
	 * Gets the file extension of a file name / path
	 * @param $path
	 * @param array|null $allowed allowed
	 * @return bool|string
	 */
	public static function getFileExtension($path, $allowed = null) {
		$parts = explode('.', $path);
		$extension = array_pop($parts);
		$extension = strtolower($extension);
		$return = $extension;
		if (!is_null($allowed)) {
			$return = false;
			if (in_array($extension, $allowed)) {
				$return = true;
			}
		}
		return $return;
	}

}
