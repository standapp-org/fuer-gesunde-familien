<?php
namespace LPC\LpcBase\ViewHelpers\Format;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Class UrlFinderViewHelper
 * A view helper for make clickable links in text
 * 
 * @author Michael Hadorn <michael.hadorn@laupercomputing.ch>
 * @package TYPO3
 * @subpackage
 * @package Lpc\LpcBase\ViewHelpers
 */
class UrlFinderViewHelper extends AbstractTagBasedViewHelper {

	public function initializeArguments() {
		$this->registerArgument('text', 'string', 'the text to search for url', false);
	}

	public function render() {
		$text = ($this->arguments['text']) ? $this->arguments['text'] : $this->renderChildren();


		// find all urls
		// http://www.regexguru.com/2008/11/detecting-urls-in-a-block-of-text/

		// regex to get all urls (without email)
		// $regex = '/(?:(?:https?|ftp|file):\/\/|www\.|ftp\.)(?:\([-A-Z0-9+&@#\/%=~_|$?!:;,.]*\)|[-A-Z0-9+&@#\/%=~_|$?!:;,.])*(?:\([-A-Z0-9+&@#\/%=~_|$?!:;,.]*\)|[A-Z0-9+&@#\/%=~_|$])/i';

		// regex to get all urls (incl. email)
		$regex = '/'.
				'\b(?:(?:(?:https?|ftp|file):\/\/|www\.|ftp\.)[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[-A-Z0-9+&@#\/%=~_|$]'.
				'|((?:mailto:)?[A-Z0-9._%+-]+@[A-Z0-9._%-]+\.[A-Z]{2,4})\b)'.
				'|"(?:(?:https?|ftp|file):\/\/|www\.|ftp\.)[^"\r\n]+"?'.
				'|\'(?:(?:https?|ftp|file):\/\/|www\.|ftp\.)[^\'\r\n]+\'?'.
			'/i';

		preg_match_all($regex, $text, $matches);

		// link to replace (all urls)
		$links = $matches[0];
		// link to replace (all mail addresses)
		$mails = $matches[1];

		// sort by length (longest at first) to avoid double replacement
		usort($links, array($this, 'cmp'));

		$replaced = array();
		foreach ($links as $link) {
			// skip duplicates
			if (!array_key_exists($link, $replaced)) {
				// generate identify protector
				$bProtectorFound = false;
				while (!$bProtectorFound) {
					$matchProtector = md5($link.mt_rand());
					if (!in_array($matchProtector, $replaced)) {
						$bProtectorFound = true;
					}
				}

				$replaced[$link] = $matchProtector;

				$target = '_blank';
				if (in_array($link, $mails)) {
					$target = '_self';
				}

				$text = str_replace($link, '<a href="href_'.$matchProtector.'" target="'.$target.'">show_'.$matchProtector.'</a>', $text);
			}
		}

		// remove protectors
		foreach ($replaced as $link => $protector) {
			$linkHref = $link;

			// special case mail
			if (in_array($link, $mails)) {
				$linkHref = 'mailto:'.$linkHref;
			}

			// special: www. without protocol -> guess http://
			if (strpos($linkHref, 'www.') === 0) {
				$linkHref = 'http://'.$linkHref;
			}

			$text = str_replace('href_'.$protector, $linkHref, $text);
			$text = str_replace('show_'.$protector, $link, $text);
		}

		return $text;
	}

	protected function cmp($a, $b) {
		$aLen = strlen($a);
		$bLen = strlen($b);

		if ($aLen == $bLen) {
			return 0;
		}
		return ($aLen < $bLen) ? 1 : -1;
	}
}
