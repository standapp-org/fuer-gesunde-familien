<?php
namespace LPC\Captcha\Middleware;

use LPC\Captcha\Answer\CaptchaAnswer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\View\TemplatePaths;

class Shield implements MiddlewareInterface
{
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		if (str_ends_with($request->getUri()->getPath(), 'bots-see-here.html')) {
			$ip = $request->getAttribute('normalizedParams')?->getRemoteAddress() ?? $request->getServerParams()['REMOTE_ADDR'] ?? '';
			$spammer = $request->getMethod() === 'POST';
			if ($ip !== '') {
				GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_captcha_ip')->executeStatement(
					'INSERT INTO tx_captcha_ip (`ip`, `bot`, `spammer`, `tstamp`) VALUES (INET6_ATON(?), 1, ?, ?)
						ON DUPLICATE KEY UPDATE `bot` = 1, `spammer` = VALUE(`spammer`), `tstamp` = VALUE(`tstamp`)',
					[sha256($ip), $spammer, time()]
				);
			}

			return new HtmlResponse(GeneralUtility::getFileAbsFileName('EXT:lpc_captcha/Resources/Private/bots-see-here.html'));
		} else if ($request->getUri()->getPath() === '/lpc-captcha-shield-check') {
			return $this->checkFormPost($request);
		}

		return $handler->handle($request);
	}

	private function renderShield(): string {
		$renderingContext = GeneralUtility::makeInstance(RenderingContextFactory::class)->create();
		$renderingContext->setTemplatePaths(new TemplatePaths('lpc_captcha'));
		$view = new StandaloneView($renderingContext);
		$view->setTemplate("Shield");
		return $view->render();
	}

	private function checkFormPost(ServerRequestInterface $request): ResponseInterface {
		$captchaAnswer = $request->getParsedBody()['captcha'] ?? null;
		if (isset($captchaAnswer)) {
			if (CaptchaAnswer::isDataValid($captchaAnswer) !== true) {
				return $this->textResponse(LocalizationUtility::translate('shieldAnswerWrong', 'LpcCaptcha') ?? 'answer is wrong', 403);
			}
		} else {
			$file = $request->getUploadedFiles()['lpcCaptchaShieldValue'] ?? null;
			if ($file === null || $this->checkShieldValue($file->getStream()->getContents()) === false) {
				return new HtmlResponse($this->renderShield(), 403);
			}
		}

		$encryptedAnswer = $request->getParsedBody()['lpcCaptchaShieldAnswer'] ?? null;
		if ($encryptedAnswer === null) return $this->textResponse('missing encrypted captcha answer', 400);
		$encryptedAnswer = base64_decode($encryptedAnswer, true);
		if ($encryptedAnswer === false) return $this->textResponse('encrypted captcha answer is invalid', 400);
		$nonce = substr($encryptedAnswer, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$cipher = substr($encryptedAnswer, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$key = hash_hkdf('sha256', $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'], SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
		$decryptedAnswer = sodium_crypto_secretbox_open($cipher, $nonce, $key);
		if ($decryptedAnswer === false) return $this->textResponse('encrypted captcha answer is invalid', 400);
		return $this->textResponse(base64_encode($decryptedAnswer), 200);
	}

	private function textResponse(string $content, int $status): ResponseInterface {
		$body = GeneralUtility::makeInstance(StreamFactory::class)->createStream($content);
		return new Response($body, $status, ['Content-Type' => 'text/plain']);
	}

	private function checkShieldValue(string $value): bool {
		$shieldValue = gzdecode($value);

		if ($shieldValue === false || strlen($shieldValue) < 10) {
			return false;
		}
		$pos = 0;
		$events = [];
		$time = 0;
		$repeatedUpper = 0;
		$count = 0;
		while ($pos < strlen($shieldValue)) {
			$event = $shieldValue[$pos++];
			$l = ord($shieldValue[$pos++]);
			$interval = 0;
			for ($i = 0; $i < $l; $i++) {
				$interval |= (ord($shieldValue[$pos + $i]) << ($i * 8));
			}
			$pos += $l;

			$time += $interval;

			$eventInterval = $time - ($events[$event]['l'] ?? 0);
			for ($i = -1; $i <= 1; $i++) {
				$events[$event]['i'][$eventInterval+$i] ??= 0;
				$events[$event]['i'][$eventInterval+$i] += $i === 0 ? 2 : 1;
			}
			$events[$event]['l'] = $time;

			$levent = strtolower($event);
			if (ctype_upper($event) && isset($events[$levent]['e']) && ctype_upper($events[$levent]['e'])) {
				$repeatedUpper++;
			}
			$events[$levent]['e'] = $event;

			$count++;
		}

		if ($repeatedUpper > 4 && $repeatedUpper * 200 > $count) {
			// too many consecutive end events without start events (e.g. keyup without keydown)
			return false;
		}

		if ($time < 3000) {
			// total time is less than 3 seconds
			return false;
		}

		if (count($events) < 4) {
			return false;
		}

		$clower = 0;
		unset($event);

		foreach ($events as $k => $event) {
			if ($k !== 'M' && isset($event['i'])) {
				$isum = array_sum($event['i']);
				if ($isum > 10) {
					foreach ($event['i'] as $int => $c) {
						if ($int > 20 && $c*4 > $isum) {
							// many events with similiar interval
							return false;
						}
						if ($int === 0 && $c*4 > $isum) {
							// many events with 0 interval
							return false;
						}
					}
				}
			}
			if (isset($event['e']) && ctype_lower($event['e'])) {
				if ($k === 'i') {
					// form was not visible during submit
					return false;
				}
				$clower++;
			}
		}
		if ($clower > 2) {
			return false;
		}

		return true;
	}
}
