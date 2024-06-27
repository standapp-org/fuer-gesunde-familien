<?php
namespace LPC\LpcBase\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use LPC\LpcBase\Domain\Model\SocialLink;
use TYPO3\CMS\Core\Imaging\{Icon, IconFactory};
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\PageTitle\PageTitleProviderManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class ShareLinkViewHelper extends AbstractTagBasedViewHelper
{
	protected $escapeChildren = false;

	protected $tagName = 'a';

	private IconFactory $iconFactory;

	public function injectIconFactory(IconFactory $iconFactory): void {
		$this->iconFactory = $iconFactory;
	}

	public function initializeArguments(): void {
		$this->registerUniversalTagAttributes();
		$this->registerArgument('platform', 'string', 'facebook | twitter | xing | linkedin | whatsapp | mail', true);
		$this->registerArgument('iconAs', 'string', 'assign icon to variable for rendering content');
		$this->registerArgument('iconMarkupAs', 'string', 'assign rendered icon markup to variable for rendering content');
		$this->registerArgument('colorAs', 'string', 'assign brand color to variable for rendering content');
		$this->registerTagAttribute('target', 'string', 'the target attribute', false, '_blank');
	}

	public function render(): string {
		$this->tag->addAttribute('href', $this->getShareLink($this->arguments['platform']));

		$variablesToRemove = [];
		if($this->hasArgument('iconAs')) {
			$this->templateVariableContainer->add($this->arguments['iconAs'], $this->getIcon());
			$variablesToRemove[] = $this->arguments['iconAs'];
		} else if($this->hasArgument('iconMarkupAs')) {
			$this->templateVariableContainer->add($this->arguments['iconMarkupAs'], $this->getIcon()->getMarkup(SvgIconProvider::MARKUP_IDENTIFIER_INLINE));
			$variablesToRemove[] = $this->arguments['iconMarkupAs'];
		}
		if($this->hasArgument('colorAs')) {
			$this->templateVariableContainer->add($this->arguments['colorAs'], $this->getColor());
			$variablesToRemove[] = $this->arguments['colorAs'];
		}
		$content = $this->renderChildren();
		foreach($variablesToRemove as $variableName) {
			$this->templateVariableContainer->remove($variableName);
		}

		if (empty($content)) {
			$content = $this->getIcon()->getMarkup(SvgIconProvider::MARKUP_IDENTIFIER_INLINE);
		}
		$this->tag->setContent($content);

		$this->tag->addAttribute('title', SocialLink::getLabelForPlatform($this->arguments['platform']));

		return $this->tag->render();
	}

	private function getIcon(): Icon {
		return $this->iconFactory->getIcon(SocialLink::getIconIdentifierForPlatform($this->arguments['platform']));
	}

	private function getColor(): ?string {
		return SocialLink::PLATFORMS[$this->arguments['platform']]['color'] ?? null;
	}

	private function getShareLink(string $platform): string {
		$uri = $GLOBALS['TYPO3_REQUEST']->getUri();
		$url = (string)$uri->withHost(idn_to_utf8($uri->getHost()));
		switch($platform) {
			case 'facebook':
				return 'https://www.facebook.com/sharer/sharer.php?u='.urlencode($url);
			case 'twitter':
				return 'https://twitter.com/intent/tweet?url='.urlencode($url);
			case 'xing':
				return 'https://www.xing.com/spi/shares/new?url='.urlencode($url);
			case 'linkedin':
				return 'https://www.linkedin.com/shareArticle?mini=true&url='.urlencode($url);
			case 'whatsapp':
				return 'https://api.whatsapp.com/send?text='.urlencode($url);
			case 'threema':
				return 'https://threema.id/compose?text='.urlencode($url);
			case 'mail':
				return 'mailto:?'.http_build_query([
					'subject' => GeneralUtility::makeInstance(PageTitleProviderManager::class)->getTitle(),
					'body' => $url,
				]);
			default:
				throw new \InvalidArgumentException('Unknown platform '.$this->arguments['platform']);
		}
	}
}
