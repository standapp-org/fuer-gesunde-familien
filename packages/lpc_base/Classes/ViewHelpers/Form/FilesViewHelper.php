<?php
namespace LPC\LpcBase\ViewHelpers\Form;

class FilesViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper
{
	protected $tagName = 'div';

	/**
	 * @var \TYPO3\CMS\Extbase\Service\ImageService
	 */
	protected $imageService;

	public function injectImageService(\TYPO3\CMS\Extbase\Service\ImageService $imageService): void {
		$this->imageService = $imageService;
	}

	public function initializeArguments(): void {
		parent::initializeArguments();
		$this->registerArgument('multiple', 'bool', '', false, false);
		$this->registerArgument('uploadFolder', 'mixed', 'folder object or combined identifier', false, '1:/user_upload/');
		$this->registerArgument('conflictMode', 'mixed', 'upload conflict mode (cancel|replace|rename)', false, 'rename');
		$this->registerArgument('accept', 'string', 'One or more unique file type specifiers describing file types to allow', false);
		$this->registerArgument('readonly', 'bool', '', false, false);
		$this->registerArgument('provided', 'array<\TYPO3\CMS\Core\Resource\FileInterface>', 'provide predefined files for selection', false, []);
	}

	public function render(): string {
		$group = $this->renderingContext->getViewHelperVariableContainer()->get(GroupViewHelper::class, 'lpcFormGroup');
		if ($group !== null) {
			if (preg_match_all('/\\[([^\\]]*)\\]/',$this->getName(),$matches) > 0) {
				$group->setPropertyPath(implode('.',$matches[1]));
			}
		}

		$pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
		$pageRenderer->addJsFile('EXT:lpc_base/Resources/Public/JS/form-files.js');
		$pageRenderer->addCssFile('EXT:lpc_base/Resources/Public/CSS/form-files.css');

		$content = $this->renderFileList();
		if ($this->arguments['readonly'] == false) {
			$content .= $this->renderUploadButton();
			$content .= $this->renderPersistentObjectMetaField();
		}
		$this->tag->setContent($content);

		$this->tag->addAttribute(
			'class',
			($this->tag->hasAttribute('class') ? $this->tag->getAttribute('class').' ' : '')
				.'lpcFormInput '
				.($this->arguments['readonly'] == true ? '' : 'lpcFormFiles ')
				.($this->arguments['multiple'] == true ? 'multi' : 'single')
		);

		return $this->tag->render();
	}

	private function renderFileList(): string {
		$fileList = '<input type="hidden" name="'.$this->getName().'" value="__lpcFormFilesEmpty"';
		$fileList .= ' />';
		$fileList .= '<div class="top"><div class="files" data-namespace="'.$this->getName().'">';

		// allowing 100 new images to be added. there does not seem to be a smarter way.
		for($i = 0; $i < 100; $i++) {
			$this->registerFieldNameForFormTokenGeneration($this->getName().'[]');
		}

		$files = $this->getValueAttribute() ?: [];
		if (!is_iterable($files)) {
			throw new \InvalidArgumentException('the value of a form.files viewhelpers must be iterable');
		}

		$selectedProvided = [];
		foreach($this->arguments['provided'] as $file) {
			foreach($files as $k => $other) {
				$fileObj = $file instanceof \TYPO3\CMS\Core\Resource\FileReference
					? $file->getOriginalFile()
					: $file;
				if($other->getOriginalResource()->getOriginalFile()->getUid() === $fileObj->getUid()) {
					$selectedProvided[] = $k;
					continue 2;
				}
			}
			$fileList .= $this->renderFile($file, true, false);
		}

		foreach($files as $k => $file) {
			if(in_array($k, $selectedProvided, true)) {
				$fileList .= $this->renderFile($file->getOriginalResource(), true, true);
			} else {
				$fileList .= $this->renderFile($file->getOriginalResource(), false);
			}
		}
		$fileList .= '</div>';

		if ($this->arguments['multiple'] == true) {
			$fileList .= '<div class="trash"><i class="fa fa-trash-o"></i></div>';
		}

		$fileList .= '</div>';
		return $fileList;
	}

	private function renderFile(\TYPO3\CMS\Core\Resource\AbstractFile $file, bool $provided, ?bool $selected = null): string {
		static $imgExts = null;
		if($imgExts === null) {
			$imgExts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']);
		}
		$class = 'file '.($provided ? 'provided' : 'added');
		if ($selected === true) {
			$class .= ' selected';
		}
		$out = '<div class="'.$class.'" title="'.$file->getName().'">';
		if(in_array($file->getExtension(), $imgExts, true)) {
			$processingInstructions = [
				'maxWidth' => 80,
				'maxHeight' => 80,
			];
			if($file->hasProperty('crop') && $file->getProperty('crop') !== '') {
				$cropVariantCollection = \TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection::create($file->getProperty('crop'));
				$cropArea = $cropVariantCollection->getCropArea('default');
				if(!$cropArea->isEmpty()) {
					$processingInstructions['crop'] = $cropArea->makeAbsoluteBasedOnFile($file);
				}
			}
			$processed = $this->imageService->applyProcessingInstructions($file, $processingInstructions);
			$out .= '<img src="'.$processed->getPublicUrl().'" />';
		} else {
			$mimeParts = explode('/', $file->getMimeType(), 2);
			$candidates = [
				implode('-', $mimeParts),
				$mimeParts[0],
				'unknown',
			];
			foreach($candidates as $candidate) {
				$path = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
					'EXT:lpc_base/Resources/Public/Icons/Mime/'.$candidate.'.svg'
				);
				if(file_exists($path)) {
					$out .= '<img src="/'.\TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($path).'" />';
					break;
				}
			}
		}
		$name = $this->getName().'[]';
		$prependUid = '';
		if($provided && $selected !== true) {
			$prependUid = 'copy';
		} else {
			$prependUid = 'file';
		}
		$value = $this->getName().'['.$prependUid.$file->getUid().']';
		$out .= '<input type="hidden" name="'.$name.'" value="'.$value.'"'.($selected === false ? ' disabled' : '').' />';
		$out .= '</div>';
		$this->registerFieldNameForFormTokenGeneration($name);
		return $out;
	}

	private function renderUploadButton(): string {
		$tag = '<button type="button" class="lpcUploadButton">';
		$tag .= \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
			'form.files.'.($this->arguments['multiple'] == true ? 'addFiles' : 'selectFile'),
			'LpcBase'
		);

		$tag .= '<input type="file"';
		$tag .= ' name="'.$this->extendInputNamespace('lpc_form_files').'[]"';
		if($this->hasArgument('accept')) {
			$tag .= ' accept="'.$this->arguments['accept'].'"';
		}
		if($this->arguments['multiple'] == true) {
			$tag .= ' multiple';
		}
		$tag .= '></button>';

		return $tag;
	}

	private function renderPersistentObjectMetaField(): string {
		$meta = [
			'uploadFolder' => $this->arguments['uploadFolder'],
			'conflictMode' => $this->arguments['conflictMode'],
		];
		if($this->hasArgument('accept')) {
			$meta['accept'] = $this->arguments['accept'];
		}
		if($this->arguments['property'] !== null) {
			$objectName = $this->renderingContext->getViewHelperVariableContainer()->get(
				\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class,
				'formObjectName'
			);
			$fieldNamePrefix = $this->renderingContext->getViewHelperVariableContainer()->get(
				\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class,
				'fieldNamePrefix'
			);
			if($objectName !== null && $fieldNamePrefix !== null && strpos($this->getName(), $fieldNamePrefix.'['.$objectName.']') === 0) {
				$formObject = $this->renderingContext->getViewHelperVariableContainer()->get(
					\TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class,
					'formObject'
				);
				if($formObject !== null && $uid = $formObject->getUid()) {
					$name = $this->extendInputNamespace('lpc_form_filesmeta');
					$meta['parent']['class'] = get_class($formObject);
					$meta['parent']['property'] = $this->arguments['property'];
					$meta['parent']['uid'] = $formObject->getUid();
				}
			}
		}
		if($this->hasArgument('provided')) {
			$meta['provided'] = [];
			foreach($this->arguments['provided'] as $file) {
				$meta['provided'][] = ($file instanceof \TYPO3\CMS\Core\Resource\AbstractFile ? 'file' : '').$file->getUid();
			}
		}

		$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$key = hash_hkdf('sha256', $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'], SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
		$encrypted = sodium_crypto_secretbox(json_encode($meta, JSON_THROW_ON_ERROR), $nonce, $key);
		return '<input type="hidden" name="'.$this->extendInputNamespace('lpc_form_filesmeta').'" value="'.base64_encode($encrypted.$nonce).'">';
	}

	private function extendInputNamespace(string $namespace): string {
		return preg_replace_callback('/^[^\[]+/', function($m) use($namespace) {
			return $namespace.'['.$m[0].']';
		}, $this->getName(), 1) ?: throw new \Exception(preg_last_error_msg(), preg_last_error());
	}
}
