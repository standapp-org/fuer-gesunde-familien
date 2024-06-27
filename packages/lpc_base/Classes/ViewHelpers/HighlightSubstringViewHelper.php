<?php
namespace LPC\LpcBase\ViewHelpers;

class HighlightSubstringViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
	protected $escapeOutput = false;

	public function initializeArguments(): void {
		$this->registerArgument('substring', 'string', 'the substring', true);
		$this->registerArgument('tag', 'string', 'the tag name with which highlighted substrings are wrapped.', false, 'em');
		$this->registerArgument('class', 'string', 'class(es) to add to the tag wrapping highlighted substrings.');
		$this->registerArgument('caseSensitive', 'bool', 'make the search case sensitive', false, false);
	}

	public function render(): string {
		$content = $this->renderChildren();
		$openingTag = '<'.$this->arguments['tag'];
		if($this->hasArgument('class')) {
			$openingTag .= ' class="'.$this->arguments['class'].'"';
		}
		$openingTag .= '>';
		$closingTag = '</'.$this->arguments['tag'].'>';

		$replacement = is_array($this->arguments['substring'])
			? array_map(fn($s) => $openingTag.$s.$closingTag, $this->arguments['substring'])
			: $openingTag.$this->arguments['substring'].$closingTag;

		return $this->arguments['caseSensitive']
			? str_replace($this->arguments['substring'], $replacement, $content)
			: str_ireplace($this->arguments['substring'], $replacement, $content);
	}
}
