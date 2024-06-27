<?php
namespace LPC\LpcBase\ViewHelpers;

class MetaTagViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
	public function initializeArguments() {
		$this->registerArgument('property','string','');
		$this->registerArgument('content','string','',true);
		$this->registerArgument('http-equiv','string','');
		$this->registerArgument('name','string','');
		$this->registerArgument('replace', 'boolean', 'override a previous set meta tag, if one exists.', false, true);
	}

	/**
	 * @param string $property
	 * @param mixed $content
	 */
	public function render() {
		$type = null;
		$types = ['property','name','http-equiv'];

		foreach($types as $arg) {
			if(!empty($this->arguments[$arg])) {
				if($type !== null) {
					throw new \Exception('only one of '.implode(',',$types).' can be set at the same time');
				}
				$type = $arg;
			}
		}

		if($type === null) {
			throw new \Exception('on of '.implode(',',$types).' must be set');
		}

		\LPC\LpcBase\Utility\PluginUtility::addMetaTags([
			$this->arguments[$type] => $this->arguments['content']
		], $type, $this->arguments['replace']);
	}
}
