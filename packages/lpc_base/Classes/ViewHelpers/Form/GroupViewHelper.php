<?php
namespace LPC\LpcBase\ViewHelpers\Form;

use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;

class GroupViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
{
	protected $tagName = 'div';

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
	 */
	protected $dataMapper;

	protected ?string $propertyPath = null;

	public function injectDataMapper(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper): void {
		$this->dataMapper = $dataMapper;
	}

	public function initializeArguments(): void {
		parent::initializeArguments();
		$this->registerUniversalTagAttributes();
		$this->registerArgument('label','string','the label to add to the input field');
		$this->registerArgument('labelKey','string','the trans-unit key for looking up the label to add to the field');
		$this->registerArgument('labelClass', 'string', 'class attribute to add to the inserted label element');
		$this->registerArgument('mandatory','boolean','');
		$this->registerArgument('readonly','boolean','');
		$this->registerArgument('validationErrors','boolean','',false,true);
		$this->registerArgument('validationPropertyPath','string','');
	}

	public function render() {
		$label = $this->arguments['label'];
		$labelKey = $this->arguments['labelKey'];

		$this->renderingContext->getViewHelperVariableContainer()->add(self::class, 'lpcFormGroup', $this);
		$content = $this->renderChildren();
		$this->renderingContext->getViewHelperVariableContainer()->remove(self::class, 'lpcFormGroup');
		$inputTags = $this->parseInputTags($content);
		$inputTag = reset($inputTags);
		$classes = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(' ',$this->tag->getAttribute('class') ?? '',true);
		$classes[] = 'lpcFormGroup';

		if(!$this->hasArgument('mandatory')) {
			$mandatory = isset($inputTag['attrs']['required']);
		} else {
			$mandatory = (bool)$this->arguments['mandatory'];
			if($this->arguments['mandatory'] == true) {
				foreach($inputTags as &$it) {
					$it['attrs']['required'] = 'required';
				}
			}
		}

		if(!$this->hasArgument('readonly')) {
			$readonly = isset($inputTag['attrs']['readonly']);
		} else {
			$readonly = (bool)$this->arguments['readonly'];
			if($this->arguments['readonly'] == true) {
				foreach($inputTags as &$it) {
					$it['attrs']['readonly'] = 'readonly';
				}
			}
		}

		$extbaseRequestParameters = $this->getExtbaseRequestParamenters();

		$errors = '';
		if($extbaseRequestParameters !== null) {
			$validationResults = $extbaseRequestParameters->getOriginalRequestMappingResults();
			$propertyPaths = [];
			if($this->propertyPath !== null) {
				$propertyPaths[] = explode('.',$this->propertyPath);
			} else if($this->arguments['validationPropertyPath'] != '') {
				foreach(explode(',',$this->arguments['validationPropertyPath']) as $path) {
					$propertyPaths[] = explode('.',$path);
				}
			} else {
				foreach($inputTags as $input) {
					if (isset($inputTag['attrs']['name']) && $inputTag['attrs']['name'] !== true && preg_match_all('/\\[([^\\]]*)\\]/',$inputTag['attrs']['name'],$matches) > 0) {
						$propertyPaths[] = $matches[1];
					}
				}
			}

			$results = [];
			foreach($propertyPaths as $path) {
				$validationResult = $validationResults;
				foreach($path as $property) {
					$validationResult = $validationResult->forProperty($property);
				}
				$results[] = $validationResult;
			}

			foreach($results as $validationResult) {
				if($this->arguments['validationErrors'] == true) {
					foreach($validationResult->getFlattenedErrors() as $validationErrors) {
						foreach($validationErrors as $error) {
							$errors = '<div class="lpcFormError error"><div>'.$error->render().'</div></div>';
						}
					}
					foreach($validationResult->getFlattenedWarnings() as $warnings) {
						foreach($warnings as $warning) {
							$errors = '<div class="lpcFormError warning">'.$warning->render().'</div>';
						}
					}
					foreach($validationResult->getFlattenedNotices() as $notices) {
						foreach($notices as $notice) {
							$errors = '<div class="lpcFormError notice">'.$notice->render().'</div>';
						}
					}
				}
				if($validationResult->hasErrors()) {
					if($inputTag !== false && !in_array('f3-form-error', $inputTag['classes'], true)) {
						foreach($inputTags as &$it) {
							$it['classes'][] = 'f3-form-error';
						}
					}
					$classes[] = 'error';
				}
			}
		}

		if(preg_match('/^(<[a-z]+(\s+[a-z]+(="[^"]*")?)*\s*>)*\s*<label/',$content) === 1) {
			// content starts with a <label> (maybe after other opening tags) dont add one.
			$label = false;
		} else if($label === null) {
			if($labelKey === null) {
				if($this->viewHelperVariableContainer->exists(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class,'formObject')) {
					$propertyName = null;
					if($this->propertyPath !== null) {
						$propertyPath = explode('.',$this->propertyPath);
						$propertyName = array_pop($propertyPath);
					} else if($inputTag !== false && isset($inputTag['attrs']['name']) && $inputTag['attrs']['name'] !== true) {
						if(preg_match('/^[a-z_]+\[[^\]]+\]\[([^\]]+)\]/',$inputTag['attrs']['name'],$match) === 1) {
							$propertyName = $match[1];
						}
					}
					if($propertyName !== null) {
						$formObject = $this->viewHelperVariableContainer->get(\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class,'formObject');
						if (is_object($formObject)) {
							$formObjectClass = get_class($formObject);
							$tableName = $this->dataMapper->getDataMap($formObjectClass)->getTableName();
							$field = \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($propertyName);
							if(!empty($GLOBALS['TCA'][$tableName]['columns'][$field]['label'])) {
								$label = $GLOBALS['TCA'][$tableName]['columns'][$field]['label'];
								if(substr($label,0,4) == 'LLL:') {
									$label = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($label);
								}
							} else {
								$parts = explode('\\',$formObjectClass);
								if(substr($formObjectClass,0,9) == 'TYPO3\\CMS\\') {
									$extensionName = $parts[2];
								} else {
									$extensionName = $parts[1];
								}
								$extensionKey = \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName);
								$label = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:'.$extensionKey.'/Resources/Private/Language/locallang_db.xlf:'.$tableName.'.'.$field);
							}
						}
					}
				}
			} else if ($extbaseRequestParameters !== null) {
				$label = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($labelKey,$extbaseRequestParameters->getControllerExtensionName());
			}
		}

		if($label) {
			if(count($inputTags) == 1 && empty($inputTag['attrs']['id'])) {
				$inputTags[0]['attrs']['id'] = 'lpcForm'.substr(uniqid(),-8);
			}
		}

		$delta = 0;
		foreach($inputTags as &$it) {
			$it['classes'][] = 'lpcFormControl';
			$delta += $this->replaceInputTag($content,$it,$delta);
		}

		if($label) {
			$labelTag = '<label';
			if(count($inputTags) === 1 && isset($inputTags[0]['attrs']['id'])) {
				$labelTag .= ' for="'.$inputTags[0]['attrs']['id'].'"';
			}
			if($this->hasArgument('labelClass')) {
				$labelTag .= ' class="'.$this->arguments['labelClass'].'"';
			}
			$labelTag .= '>'.$label.'</label>';
			$content = $labelTag.$content;
		}

		if($mandatory) {
			$classes[] = 'mandatory';
		}

		if($readonly) {
			$classes[] = 'readonly';
		}

		$this->tag->setContent($content);
		$this->tag->addAttribute('class',implode(' ',$classes));
		return $this->tag->render().$errors;
	}

	/**
	 * @return list<array{0: string, 1: string, 2: int, '/': bool, classes: string[], attrs?: array<string, string|true>}>
	 */
	protected function parseInputTags(string $content): array {
		$tags = [];
// 		$name = null;
		if(preg_match_all('/<(input|textarea|select)([^>]*)>/',$content,$matches,PREG_OFFSET_CAPTURE|PREG_SET_ORDER) > 0) {
			foreach($matches as $i => $match) {
				if(preg_match('/type="([^"]*)"/',$match[2][0],$m) === 1) {
					if($m[1] == 'hidden') {
						continue;
					}
				}
				$tag = [
					0 => $match[0][0],
					1 => $match[1][0],
					2 => $match[0][1],
					'/' => substr($match[2][0],-1) == '/',
					'classes' => [],
				];

				if($tag['/']) {
					$match[2][0] = substr($match[2][0],0,-1);
				}
				preg_match_all('/([^ =]+)(?:="([^"]*)")?/',$match[2][0],$attrs,PREG_SET_ORDER);
				foreach($attrs as $attr) {
					if($attr[1] == 'class') {
						$tag['classes'] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(' ',$attr[2],true);
					} else {
						$tag['attrs'][$attr[1]] = $attr[2] ?? true;
					}
				}
				if(!empty($tag['attrs']['name'])) {
					$tags[] = $tag;
// 					if($name === null) {
// 						$name = $tag['attrs']['name'];
// 					}
// 					if($name == $tag['attrs']['name']) {
// 						$tags[] = $tag;
// 					}
				}
			}
		}
		return $tags;
	}

	/**
	 * @param array{0: string, 1: string, 2: int, '/': bool, classes: string[], attrs?: array<string, string|true>} $tag
	 */
	protected function replaceInputTag(string &$content, array $tag, int $delta): int {
		if ($tag[1] === '') return 0;
		$s = '<'.$tag[1];
		if(!empty($tag['classes'])) {
			$s .= ' class="'.implode(' ',$tag['classes']).'"';
		}
		foreach($tag['attrs'] ?? [] as $name => $value) {
			if($value === true) {
				$s .= ' '.$name;
			} else {
				$s .= ' '.$name.'="'.$value.'"';
			}
		}
		if($tag['/']) {
			$s .= ' /';
		}
		$s .= '>';
		$content = substr_replace($content,$s,$tag[2]+$delta,strlen($tag[0]));
		return strlen($s)-strlen($tag[0]);
	}

	public function setPropertyPath(string $propertyPath): void {
		$this->propertyPath = $propertyPath;
	}

	private function getExtbaseRequestParamenters(): ?ExtbaseRequestParameters {
		if($this->renderingContext instanceof RenderingContext) {
			$extbaseRequestParameters = $this->renderingContext->getRequest()?->getAttribute('extbase');
			if ($extbaseRequestParameters instanceof ExtbaseRequestParameters) {
				return $extbaseRequestParameters;
			}
		}
		return null;
	}
}
