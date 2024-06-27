<?php
namespace LPC\LpcBase\ViewHelpers\Format;

class HyphenateViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
	/**
	 * @var \LPC\LpcBase\Service\TypographyService
	 */
	protected $typographyService;

	public function __construct(\LPC\LpcBase\Service\TypographyService $typographyService) {
		$this->typographyService = $typographyService;
	}

	/**
	 * @param string $content
	 * @param boolean $smartCharacters
	 * @param boolean $smartSpacing
	 * @param boolean $characterStyling
	 */
	public function render($content = null,$smartCharacters = false,$smartSpacing = false,$characterStyling = false) {
		if($content === null) {
			$content = $this->renderChildren();
		}
		$this->typographyService->setSmartCharacters($smartCharacters);
		$this->typographyService->setSmartSpacing($smartSpacing);
		$this->typographyService->setCharacterStyling($characterStyling);
		return $this->typographyService->process($content);
	}
}
