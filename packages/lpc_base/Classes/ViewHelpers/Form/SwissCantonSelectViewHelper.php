<?php
namespace LPC\LpcBase\ViewHelpers\Form;

class SwissCantonSelectViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper
{
	protected $tagName = 'select';

	public function render(): string {
		$content = '<option></option>';
		$value = $this->getValueAttribute();
		foreach(\LPC\LpcBase\Utility\SwissCantonUtility::getSortedByName() as $canton => $name) {
			$content .= '<option value="'.$canton.'"'.($canton == $value ? ' selected' : '').'>'.$name.'</option>';
		}
		$this->tag->setContent($content);

		$name = $this->getName();
		$this->registerFieldNameForFormTokenGeneration($name);
		$this->tag->addAttribute('name',$name);

		return $this->tag->render();
	}
}
