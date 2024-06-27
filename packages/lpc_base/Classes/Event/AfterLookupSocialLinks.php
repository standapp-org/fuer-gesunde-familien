<?php
namespace LPC\LpcBase\Event;

use LPC\LpcBase\Domain\Model\SocialLink;

class AfterLookupSocialLinks
{
	/**
	 * @param SocialLink[] $socialLinks
	 */
	public function __construct(
		private array $socialLinks,
		private int $pageUid,
		private bool $stopAtSiteroot,
		private bool $mergeRecursive,
		private bool $uniquePlatforms,
	) {}

	public function getPageUid(): int {
		return $this->pageUid;
	}

	public function stopAtSiteroot(): bool {
		return $this->stopAtSiteroot;
	}

	public function mergeRecursive(): bool {
		return $this->mergeRecursive;
	}

	/**
	 * @return SocialLink[]
	 */
	public function getSocialLinks(): array {
		return $this->socialLinks;
	}

	/**
	 * @param SocialLink[] $socialLinks
	 */
	public function setSocialLinks(array $socialLinks): void {
		$this->socialLinks = [];
		foreach ($socialLinks as $socialLink) {
			$this->addSocialLink($socialLink);
		}
	}

	public function addSocialLink(SocialLink $socialLink): void {
		$platform = $socialLink->getPlatform();
		if ($this->uniquePlatforms && $platform !== null) {
			$this->socialLinks[$platform['identifier']] ??= $socialLink;
		} else {
			$this->socialLinks[] = $socialLink;
		}
	}
}
