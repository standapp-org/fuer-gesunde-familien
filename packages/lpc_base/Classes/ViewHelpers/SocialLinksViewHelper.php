<?php
namespace LPC\LpcBase\ViewHelpers;

use LPC\LpcBase\Domain\Repository\SocialLinkRepository;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

class SocialLinksViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
	protected $escapeOutput = false;

	private SocialLinkRepository $socialLinkRepository;
	private IconFactory $iconFactory;

	public function __construct(SocialLinkRepository $socialLinkRepository, IconFactory $iconFactory) {
		$this->socialLinkRepository = $socialLinkRepository;
		$this->iconFactory = $iconFactory;
	}

	public function initializeArguments(): void {
		$this->registerArgument('as', 'string', 'assign link to variable of given name and render content per link.', false);
		$this->registerArgument('brandColors', 'bool', 'add brand colors to icons', false, false);
		$this->registerArgument('stopAtSiteroot', 'bool', 'stop at siteroot when traversing up to rootline to find social link records.', false, true);
		$this->registerArgument('mergeRecursive', 'bool', 'traverse rootline and merge all found links. if false, traversing stops on first page with 1 or more links.', false, false);
		$this->registerArgument('uniquePlatforms', 'bool', 'output only one link per platform.', false, false);
		$this->registerArgument('targetBlank', 'bool', 'Add target=_blank to links', false, true);
	}

	public function render(): string {
		$links = $this->socialLinkRepository->findForPage(
			$GLOBALS['TSFE']->id,
			$this->arguments['stopAtSiteroot'],
			$this->arguments['mergeRecursive'],
			$this->arguments['uniquePlatforms']
		);

		$html = '';
		foreach($links as $link) {
			if($this->hasArgument('as')) {
				$this->templateVariableContainer->add($this->arguments['as'], $link);
				$html .= $this->renderChildren();
				$this->templateVariableContainer->remove($this->arguments['as']);
			} else {
				$icon = $this->iconFactory->getIcon($link->getIconIdentifier());
				$linkContent = $icon->getMarkup(SvgIconProvider::MARKUP_IDENTIFIER_INLINE);
				if ($this->arguments['brandColors'] == true) {
					$color = $link->getColor();
					if ($color !== null) {
						$linkContent = '<span style="color:'.$color.'">'.$linkContent.'</span>';
					}
				}

				if($this->arguments['targetBlank'] == true) {
					$target = 'target="_blank"';
				} else {
					$target = '';
				}
				$html .= '<a href="'.htmlspecialchars($link->getUrl()).'" '.$target.' title="'.htmlspecialchars($link->getTitle()).'">'.$linkContent.'</a>';
			}
		}

		return $html;
	}
}
