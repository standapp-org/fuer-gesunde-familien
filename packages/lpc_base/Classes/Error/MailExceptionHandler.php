<?php
namespace LPC\LpcBase\Error;

use TYPO3\CMS\Core\Error\ProductionExceptionHandler;
use TYPO3\CMS\Core\Information\Typo3Information;


/**
 * production exception handler that additionally sends a bunch of data to report@laupercomputing.ch
 *
 * register it like this:
 * $GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'] = 'LPC\\LpcBase\\Error\\MailExceptionHandler';
 */
class MailExceptionHandler extends ProductionExceptionHandler
{
	protected string $to = 'report@laupercomputing.ch';

	public function handleException(\Throwable $exception): void {
		$this->sendExceptionMail($exception);
		parent::handleException($exception);
	}

	protected function sendExceptionMail(\Throwable $exception): void {
		try {
			$data = [
				'_SERVER' => $_SERVER,
				'_GET' => $_GET,
				'_POST' => $_POST,
				'_COOKIE' => $_COOKIE,
				'_SESSION' => $_SESSION,
				'exception' => $this->getExceptionData($exception),
			];
			try {
				$data['ses'] = $GLOBALS['TSFE']->fe_user->fetchUserSession();
				$data['ses']['ses_data'] = unserialize($data['ses']['ses_data']);
			} catch(\Throwable $e) {
				$data['ses'] = $e->getMessage();
			}
			$mail = new \TYPO3\CMS\Core\Mail\MailMessage;
			$mail->to($this->to);
			$mail->subject('Exception on '.$_SERVER['HTTP_HOST']);
			foreach($data as $name => $d) {
				$json = json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR|JSON_UNESCAPED_SLASHES);
				if ($json !== false) {
					$mail->attach($json, $name.'.json', 'application/json');
				}
			}
			$mail->html($this->getExceptionHtml($exception));
			$mail->send();
		} catch(\Throwable $e) {}
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function getExceptionData(\Throwable $exception): array {
		$data = [
			'message' => $exception->getMessage(),
			'file' => $exception->getFile(),
			'line' => $exception->getLine(),
			'trace' => $exception->getTrace(),
		];
		$previous = $exception->getPrevious();
		if ($previous !== null) {
			$data['previous'] = $this->getExceptionData($previous);
		}
		return $data;
	}

	/**
	 * Formats and echoes the exception as XHTML.
	 *
	 * @see \TYPO3\CMS\Core\Error\DebugExceptionHandler::getExceptionWeb
	 *
	 * @param \Throwable $exception The throwable object.
	 */
	public function getExceptionHtml(\Throwable $exception): string
	{
		$filePathAndName = $exception->getFile();
		$exceptionCodeNumber = $exception->getCode() > 0 ? '#' . $exception->getCode() . ': ' : '';
		$moreInformationLink = $exceptionCodeNumber !== ''
			? '(<a href="' . Typo3Information::URL_EXCEPTION . 'debug/' . $exception->getCode() . '" target="_blank">More information</a>)'
			: '';
		$backtraceCode = $this->getBacktraceCode($exception->getTrace());
		return '
			<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
				<head>
					<title>TYPO3 Exception</title>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
					<style type="text/css">
						.ExceptionProperty {
							color: #101010;
						}
						pre {
							margin: 0;
							font-size: 11px;
							color: #515151;
							background-color: #D0D0D0;
							padding-left: 30px;
						}
					</style>
				</head>
				<body>
					<div style="
							position: absolute;
							left: 10px;
							background-color: #B9B9B9;
							outline: 1px solid #515151;
							color: #515151;
							font-family: Arial, Helvetica, sans-serif;
							font-size: 12px;
							margin: 10px;
							padding: 0;
						">
						<div style="width: 100%; background-color: #515151; color: white; padding: 2px; margin: 0 0 6px 0;">Uncaught TYPO3 Exception</div>
						<div style="width: 100%; padding: 2px; margin: 0 0 6px 0;">
							<strong style="color: #BE0027;">' . $exceptionCodeNumber . htmlspecialchars($exception->getMessage()) . '</strong> ' . $moreInformationLink . '<br />
							<br />
							<span class="ExceptionProperty">' . get_class($exception) . '</span> thrown in file<br />
							<span class="ExceptionProperty">' . htmlspecialchars($filePathAndName) . '</span> in line
							<span class="ExceptionProperty">' . $exception->getLine() . '</span>.<br />
							<br />
							' . $backtraceCode . '
						</div>
					</div>
				</body>
			</html>
		';
	}

	/**
	 * Renders some backtrace
	 *
	 * @see \TYPO3\CMS\Core\Error\DebugExceptionHandler::getBacktraceCode
	 *
	 * @param array<int, array<string, mixed>> $trace The trace
	 * @return string Backtrace information
	 */
	protected function getBacktraceCode(array $trace): string
	{
		$backtraceCode = '';
		if (!empty($trace)) {
			foreach ($trace as $index => $step) {
				$class = isset($step['class']) ? htmlspecialchars($step['class']) . '<span style="color:white;">::</span>' : '';
				$arguments = '';
				if (isset($step['args']) && is_array($step['args'])) {
					foreach ($step['args'] as $argument) {
						$arguments .= $arguments === '' ? '' : '<span style="color:white;">,</span> ';
						if (is_object($argument)) {
							$arguments .= '<span style="color:#FF8700;"><em>' . htmlspecialchars(get_class($argument)) . '</em></span>';
						} elseif (is_string($argument)) {
							$preparedArgument = strlen($argument) < 100
								? $argument
								: substr($argument, 0, 50) . '#tripleDot#' . substr($argument, -50);
							$preparedArgument = str_replace(
								[
									'#tripleDot#',
									LF],
								[
									'<span style="color:white;">&hellip;</span>',
									'<span style="color:white;">&crarr;</span>'
								],
								htmlspecialchars($preparedArgument)
							);
							$arguments .= '"<span style="color:#FF8700;" title="' . htmlspecialchars($argument) . '">'
								. $preparedArgument . '</span>"';
						} elseif (is_numeric($argument)) {
							$arguments .= '<span style="color:#FF8700;">' . (string)$argument . '</span>';
						} else {
							$arguments .= '<span style="color:#FF8700;"><em>' . gettype($argument) . '</em></span>';
						}
					}
				}
				$backtraceCode .= '<pre style="color:#69A550; background-color: #414141; padding: 4px 2px 4px 2px;">';
				$backtraceCode .= '<span style="color:white;">' . (count($trace) - $index) . '</span> ' . $class
					. $step['function'] . '<span style="color:white;">(' . $arguments . ')</span>';
				$backtraceCode .= '</pre>';
				if (isset($step['file'])) {
					$backtraceCode .= $this->getCodeSnippet($step['file'], $step['line']) . '<br />';
				}
			}
		}
		return $backtraceCode;
	}

	/**
	 * Returns a code snippet from the specified file.
	 *
	 * @see \TYPO3\CMS\Core\Error\DebugExceptionHandler::getCodeSnippet
	 *
	 * @param string $filePathAndName Absolute path and file name of the PHP file
	 * @param int $lineNumber Line number defining the center of the code snippet
	 * @return string The code snippet
	 */
	protected function getCodeSnippet($filePathAndName, $lineNumber)
	{
		$codeSnippet = '<br />';
		if (@file_exists($filePathAndName)) {
			$phpFile = @file($filePathAndName);
			if (is_array($phpFile)) {
				$startLine = $lineNumber > 2 ? $lineNumber - 2 : 1;
				$phpFileCount = count($phpFile);
				$endLine = $lineNumber < $phpFileCount - 2 ? $lineNumber + 3 : $phpFileCount + 1;
				if ($endLine > $startLine) {
					$codeSnippet = '<br /><span style="font-size:10px;">' . htmlspecialchars($filePathAndName) . ':</span><br /><pre>';
					for ($line = $startLine; $line < $endLine; $line++) {
						$codeLine = str_replace("\t", ' ', $phpFile[$line - 1]);
						if ($line === $lineNumber) {
							$codeSnippet .= '</pre><pre style="background-color: #F1F1F1; color: black;">';
						}
						$codeSnippet .= sprintf('%05d', $line) . ': ' . htmlspecialchars($codeLine);
						if ($line === $lineNumber) {
							$codeSnippet .= '</pre><pre>';
						}
					}
					$codeSnippet .= '</pre>';
				}
			}
		}
		return $codeSnippet;
	}
}
