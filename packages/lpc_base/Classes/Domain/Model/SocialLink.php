<?php
namespace LPC\LpcBase\Domain\Model;

use LPC\LpcBase\Utility\LanguageUtility;
use TYPO3\CMS\Core\Imaging\IconProviderInterface;
use TYPO3\CMS\Core\Imaging\IconProvider\FontawesomeIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgSpriteIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;

class SocialLink extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
	const PLATFORMS = [
		'facebook' => [
			'title' => 'Facebook',
			'domains' => ['facebook.com'],
			'color' => '#1877f2',
		],
		'twitter' => [
			'title' => 'Twitter',
			'domains' => ['twitter.com'],
			'color' => '#1da1f2',
		],
		'linkedin' => [
			'title' => 'LinkedIn',
			'domains' => ['linkedin.com'],
			'color' => '#0077b5',
		],
		'instagram' => [
			'title' => 'Instagram',
			'domains' => ['instagram.com'],
			'color' => '#405de6',
		],
		'pinterest' => [
			'title' => 'Pinterest',
			'domains' => ['pinterest.com'],
			'color' => '#e60023',
		],
		'youtube' => [
			'title' => 'YouTube',
			'domains' => ['youtube.com'],
			'color' => '#ff0000',
		],
		'xing' => [
			'title' => 'Xing',
			'domains' => ['xing.com'],
			'color' => '#026466',
		],
		'whatsapp' => [
			'title' => 'Whatsapp',
			'domains' => ['whatsapp.com'],
			'color' => '#25d366',
		],
		'threema' => [
			'title' => 'Threema',
			'domains' => ['threema.id'],
			'color' => '#323232',
		],
		'mail' => [
			'title' => 'E-Mail',
			'pattern' => '/^mailto:/',
			'color' => '#646b72',
		],
	];

	protected string $link;

	/**
	 * @var array
	 * @phpstan-var ?array{url: string, target: string, class: string, title: string, additionalParams: string}
	 */
	private ?array $_typolinkParts = null;

	/**
	 * @var array
	 * @phpstan-var ?array{identifier: string, title: string, domains?: string[], pattern?: string, icon?: string, color: string}
	 */
	private ?array $_platform = null;

	private function getLinkPart(string $part): string {
		if($this->_typolinkParts === null) {
			$this->_typolinkParts = GeneralUtility::makeInstance(TypoLinkCodecService::class)->decode($this->link);
		}
		return $this->_typolinkParts[$part];
	}

	public function getUrl(): string {
		$url = $this->getLinkPart('url');
		//enforce https
		$url = preg_replace('#^((https?:)?//)?#', 'https://', $url);
		if ($url === null) {
			throw new \RuntimeException(preg_last_error_msg());
		}
		return $url;
	}

	public function getTitle(): string {
		return $this->getLinkPart('title') ?: ($this->getPlatform()['title'] ?? '');
	}

	public function getLabel(): string {
		$title = $this->getLinkPart('title');
		if ($title !== '') {
			return $title;
		}
		$identifier = $this->getPlatformIdentifier();
		return $identifier !== null
			? self::formatLabel($identifier, $this->getTitle())
			: $this->getTitle();
	}

	public static function getTitleForPlatform(string $identifier): ?string {
		return self::PLATFORMS[$identifier]['title'] ?? null;
	}

	public static function getLabelForPlatform(string $identifier): ?string {
		$title = self::getTitleForPlatform($identifier);
		return $title !== null ? self::formatLabel($identifier, $title) : null;
	}

	private static function formatLabel(string $identifier, string $title): string {
		$message = LocalizationUtility::translate('shareBy', 'LpcBase');
		if ($message !== null) {
			$formatter = new \MessageFormatter(LanguageUtility::getCurrentLanguage() ?? 'de', $message);
			$label = $formatter->format(['platform' => $identifier, 'title' => $title]);
			if ($label === false) {
				throw new \Exception($formatter->getErrorMessage(), $formatter->getErrorCode());
			}
			return $label;
		}
		return $title;
	}

	/**
	 * @return ?array{identifier: string, title: string, domains?: string[], pattern?: string, color: string}
	 */
	public function getPlatform(): ?array {
		return $this->_platform ??= self::getPlatformForUrl($this->link);
	}

	public function getPlatformIdentifier(): ?string {
		return $this->getPlatform()['identifier'] ?? null;
	}

	public function getColor(): ?string {
		return $this->getPlatform()['color'] ?? null;
	}

	public function getIconIdentifier(): string {
		return self::extractIconIdentifierFromPlatform($this->getPlatform());
	}

	public static function getIconIdentifierForUrl(string $url): string {
		return self::extractIconIdentifierFromPlatform(self::getPlatformForUrl($url));
	}

	public static function getIconIdentifierForPlatform(string $identifier): string {
		$platform = self::PLATFORMS[$identifier] ?? null;
		if ($platform !== null) {
			$platform['identifier'] = $identifier;
		}
		return self::extractIconIdentifierFromPlatform($platform);
	}

	/**
	 * @param ?array{identifier: string} $platform
	 */
	private static function extractIconIdentifierFromPlatform(?array $platform): string {
		return isset($platform['identifier']) ? 'lpc-sm-'.$platform['identifier'] : 'lpc-sm-unknown';
	}

	/**
	 * @return ?array{identifier: string, title: string, domains?: string[], pattern?: string, color: string}
	 */
	private static function getPlatformForUrl(string $url): ?array {
		$host = preg_match('#^(?:(?:https?:)?//)?(?<host>[\.\-a-z0-9]+)#', $url, $match) === 1 ? $match['host'] : null;
		foreach(self::PLATFORMS as $identifier => $platform) {
			if (
				(isset($platform['pattern']) && preg_match($platform['pattern'], $url) === 1) ||
				(isset($platform['domains']) && preg_match('/(^|\.)('.implode('|', array_map('preg_quote', $platform['domains'])).')$/', $match['host']) === 1)
			) {
				$platform['identifier'] = $identifier;
				return $platform;
			}
		}
		return null;
	}

	/**
	 * @return array<string, array{provider: class-string<IconProviderInterface>, name?: string, source?: string}>
	 */
	public static function getPlatformIcons(): array {
		$icons = [
			'lpc-sm-unknown' => [
				'provider' => SvgIconProvider::class,
				'source' => 'EXT:lpc_base/Resources/Public/Icons/Social/unknown.svg',
			],
		];
		foreach(self::PLATFORMS as $identifier => $platform) {
			$icons['lpc-sm-'.$identifier] = [
				'provider' => SvgIconProvider::class,
				'source' => 'EXT:lpc_base/Resources/Public/Icons/Social/'.$identifier.'.svg',
			];
		}
		return $icons;
	}
}
