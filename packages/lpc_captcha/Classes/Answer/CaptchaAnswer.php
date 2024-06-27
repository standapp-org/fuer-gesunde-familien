<?php
namespace LPC\Captcha\Answer;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

class CaptchaAnswer
{
	/**
	 * @var array{hmac: string, answer: string, type: ?string}
	 */
	#[Validate(['validator' => DataValidator::class])]
	protected array $data;

	public function __construct(string $hmac, string $answer, ?string $type = null) {
		$this->data = [
			'hmac' => $hmac,
			'answer' => $answer,
			'type' => $type,
		];
	}

	/**
	 * @param array<mixed> $data
	 */
	public static function isDataValid(array $data): bool {
		if (self::validateHmac($data) === false) {
			return false;
		}

		return match ($data['type'] ?? null) {
			'recaptcha', 'recaptchaInvisible' => self::validateReCaptcha($data),
			'math', 'dummy', 'shield' => true, // validated using hmac
			default => false,
		};
	}

	/**
	 * @return array{hmac: string, answer: string, type: ?string}
	 */
	public function getData(): array {
		return $this->data;
	}

	public function isValid(): bool {
		return self::isDataValid($this->data);
	}

	/**
	 * @param array<mixed> $data
	 */
	private static function validateHmac(array $data): bool {
		if (!isset($data['hmac']) || !isset($data['type'])) return false;
		$encryptionKey = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
		if (!$encryptionKey) {
			throw new \Exception('Encryption key was empty!');
		}
		$raw = base64_decode($data['hmac'], true);
		if ($raw === false) return false;
		$time = unpack('V', $raw)[1] ?? null;
		if ($time === null || time()-$time < 3 || time()-$time > 14400) return false;
		return hash_hmac('sha256', $data['type'].($data['answer'] ?? '').$time, $encryptionKey, true) === substr($raw, 4);
	}

	/**
	 * @param array<mixed> $answerData
	 */
	private static function validateReCaptcha(array $answerData): bool {
		static $validatedAnswers = [];
		$key = $answerData['type'].$answerData['answer'];
		if(!isset($validatedAnswers[$key])) {
			$config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('lpc_captcha');
			$data = [
				'secret' => $config[$answerData['type']]['sitekeyPrivate'],
				'response' => $answerData['answer'],
			];
			$ip = self::getClientIP();
			if ($ip !== null) {
				$data['remoteip'] = $ip;
			}
			$response = GeneralUtility::makeInstance(RequestFactory::class)->request(
				'https://www.google.com/recaptcha/api/siteverify',
				'POST',
				['form_params' => $data]
			);

			$validatedAnswers[$key] = false;
			if($response->getStatusCode() == 200) {
				$r = json_decode($response->getBody()->getContents(), true);
				if (is_array($r)) {
					$validatedAnswers[$key] = $r['success'] && $r['hostname'] == $_SERVER['SERVER_NAME'];
				}
			}
		}

		return $validatedAnswers[$key];
	}

	protected static function getClientIP(): ?string {
		return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
	}
}
