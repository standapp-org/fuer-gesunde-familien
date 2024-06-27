<?php
namespace LPC\LpcBase\ViewHelpers\Be;

use LPC\LpcBase\Configuration\BackendConfigurationManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

class EditLinkViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
{
	/**
	 * @var string
	 */
	protected $tagName = 'a';

	/**
	 * Initialize arguments
	 *
	 * @return void
	 * @api
	 */
	public function initializeArguments()
	{
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('name', 'string', 'Specifies the name of an anchor');
		$this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
		$this->registerArgument('action','string','Action to perform (new, edit)',false,'edit');
		$this->registerArgument('table','string','Name of the related table');
		$this->registerArgument('uid','int','Id of the record to edit');
		$this->registerArgument('object','object','DomainObject to edit');
		$this->registerArgument('columnsOnly','string','Comma-separated list of fields to restrict editing to',false,'');
		$this->registerArgument('defaultValues','array','List of default values for some fields (key-value pairs)',false,[]);
		$this->registerArgument('returnUrl','string','URL to return to',false,'');
		$this->registerArgument('checkAccess','boolean','Check if the user has permission to modify/create the given record',false,true);
	}

	/**
	 * Crafts a link to edit a database record or create a new one
	 *
	 * @see \TYPO3\CMS\Backend\Utility::editOnClick()
	 */
	public function render()
	{
		$action = $this->arguments['action'];
		$uid = $this->arguments['uid'];
		$pid = null;
		$table = $this->arguments['table'];

		if($this->arguments['object']) {
			if(!$table) {

				$table = GeneralUtility::makeInstance(DataMapper::class)->getDataMap(get_class($this->arguments['object']))->getTableName();
			}
			if(!$uid) {
				$uid = $this->arguments['object']->getUid();
			}
		}

		if($action == 'new' && !$uid) {
			$pid = GeneralUtility::makeInstane(BackendConfigurationManager::class)->getCurrentPageId();
			if(!$uid) {
				$uid = $pid;
			}
		}

		if($this->arguments['checkAccess']) {
			if(!$GLOBALS['BE_USER']->check('tables_modify',$table)) {
				return '';
			}
			if($action == 'edit') {
				$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table,$uid);
				$pid = $record['pid'];
				if(!$GLOBALS['BE_USER']->recordEditAccessInternals($table,$record)) {
					return '';
				}
			}
			if(!$GLOBALS['BE_USER']->doesUserHaveAccess(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages',$pid),17)) {
				return '';
			}
		}

		if($pid === null) {
			$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table,$uid,'pid');
			$pid = $record['pid'];
		}

		$urlParameters = [
			'id' => $pid,
			'edit' => [
				$table => [
					$uid => $action
				]
			],
			'columnsOnly' => $this->arguments['columnsOnly'],
			'createExtension' => 0,
			'returnUrl' => $this->arguments['returnUrl'] ?: \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')
		];
		if (count($this->arguments['defaultValues']) > 0) {
			$urlParameters['defVals'] = $this->arguments['defaultValues'];
		}
		$uri = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class)->buildUriFromRoute('record_edit',$urlParameters);

		$this->tag->addAttribute('href', $uri);
		$this->tag->setContent($this->renderChildren());
		$this->tag->forceClosingTag(true);
		return $this->tag->render();
	}
}
