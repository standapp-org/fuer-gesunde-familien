<?php
namespace LPC\LpcPetition\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Http\Stream;
use LPC\LpcPetition\Domain\Repository\{EntryRepository, FieldRepository};
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

class BackendController
{
	public function __construct(
		private ConnectionPool $pool,
		private ResponseFactory $responseFactory,
		private FlexFormService $flexformService,
		private FieldRepository $fieldRepository,
		private EntryRepository $entryRepository,
	) {}

	public function exportAction(ServerRequestInterface $request): ResponseInterface {
		$db = $this->pool->getConnectionForTable('tt_content');

		$contentRow = $db->select(['pid', 'pi_flexform'], 'tt_content', ['uid' => $request->getQueryParams()['ce']])->fetchAssociative();
		if (!$contentRow || !$contentRow['pi_flexform']) {
			return $this->responseFactory->createResponse(404);
		}

		if (!$GLOBALS['BE_USER']->check('tables_select', 'tx_lpcpetition_domain_model_entry') || !$GLOBALS['BE_USER']->isInWebMount($contentRow['pid'])) {
			return $this->responseFactory->createResponse(403);
		}

		$config = $this->flexformService->convertFlexFormContentToArray($contentRow['pi_flexform']);
		$fields = GeneralUtility::trimExplode(',', $config['settings']['formFields'], true);

		$csv = fopen('php://memory', 'r+') ?: throw new \Exception('could not open memory');

		$fields = $this->fieldRepository->getFormFieldsFromSettings($config['settings']);
		$labels = array_map(fn($field) => $field->getName(), $fields);
		array_unshift($labels, LocalizationUtility::translate('entry.crdate', 'LpcPetition'));
		fputcsv($csv, $labels);

		$querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
		$querySettings->setStoragePageIds([$contentRow['pid']]);
		$this->entryRepository->setDefaultQuerySettings($querySettings);

		$entries = $this->entryRepository->findAll();
		foreach($entries as $entry) {
			$crdate = $entry->getCrdate();
			$row = [$crdate ? $crdate->format('d.m.Y H:i') : ''];

			foreach($fields as $property => $field) {
				$value = ObjectAccess::getPropertyPath($entry, $property);
				switch($field->getType()) {
					case 'checkboxes':
						$options = $field->getOptions();
						$row[] = implode(', ', array_map(fn($option) => $options[$option], $value ?: []));
						break;
					case 'select':
					case 'radios':
						$row[] = $field->getOptions()[$value];
						break;
					case 'checkbox':
						$row[] = LocalizationUtility::translate($value ? 'yes' : 'no', 'LpcBase');
						break;
					case 'date':
						if(!$value instanceof \DateTimeInterface) {
							$value = new \DateTime($value);
						}
						$row[] = $value->format('d.m.Y');
						break;
					default:
						$row[] = $value;
				}
			}
			fputcsv($csv, $row);
		}

		return $this->responseFactory->createResponse()
			->withHeader('Content-Type', 'text/csv')
			->withHeader('Content-Disposition', 'attachment; filename=Export-'.date('Ymd-His').'.csv')
			->withBody(new Stream($csv));
	}
}
