<?php
namespace LPC\LpcPetition\Domain\Repository;

use LPC\LpcPetition\Domain\Model\Field;
use SJBR\StaticInfoTables\Domain\Repository\CountryRepository;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * @extends Repository<Field>
 */
class FieldRepository extends Repository
{
	public function __construct(
		private CountryRepository $countryRepository,
	) {
		parent::__construct();
	}

	/**
	 * @param array<string, mixed> $settings
	 * @return array<string, Field>
	 */
	public function getFormFieldsFromSettings(array $settings): array {
		$mandatory = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',',$settings['mandatoryFields'],true);
		$fields = [];
		foreach(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',',$settings['formFields'],true) as $id) {
			if(ctype_digit($id)) {
				$field = $this->findByUid((int)$id);
				$key = 'fieldData.'.$id;
			} else {
				$key = $id;
				$field = new \LPC\LpcPetition\Domain\Model\Field();
				$field->setName(LocalizationUtility::translate('formLabel.'.$id, 'LpcPetition') ?? '');
				switch($id) {
					case 'newsletter':
						$field->setType('checkbox');
						break;
					case 'private':
						$field->setType('radios');
						$field->setOptions([
							0 => LocalizationUtility::translate('formLabel.private.0', 'LpcPetition'),
							1 => LocalizationUtility::translate('formLabel.private.1', 'LpcPetition'),
						]);
						$mandatory[] = 'private';
						break;
					case 'allowReuse':
						$field->setName(LocalizationUtility::translate('formLabel.allowReuse', 'LpcPetition', [$settings['organizationName']]) ?? '');
						$field->setType('radios');
						$field->setTitle(LocalizationUtility::translate('form.explainAllowReuse', 'LpcPetition') ?? '');
						$field->setOptions([
							1 => LocalizationUtility::translate('formLabel.allowReuse.1', 'LpcPetition'),
							0 => LocalizationUtility::translate('formLabel.allowReuse.0', 'LpcPetition'),
						]);
						$mandatory[] = 'allowReuse';
						break;
					case 'country':
						$field->setType('select');
						$options = [];
						foreach(['CH','DE','AT','IT','FR','LI'] as $country) {
							$options[$country] = LocalizationUtility::translate('formLabel.country.'.$country, 'LpcPetition');
						}
						$options['-'] = new class {
							public bool $disabled = true;
							public function __toString() {
								return '──────────';
							}
						};
						foreach($this->countryRepository->findAllOrderedBy('shortNameLocal') as $country) {
							if(!isset($options[$country->getIsoCodeA2()])) {
								$options[$country->getIsoCodeA2()] = $country->getShortNameLocal();
							}
						}
						$field->setOptions($options);
						break;
					case 'canton':
						$field->setType('select');
						$field->setOptions(iterator_to_array(\LPC\LpcBase\Utility\SwissCantonUtility::getSortedByName()));
						break;
					case 'mail':
						$field->setType('email');
						break;
					case 'birthday':
						$field->setType('date');
						break;
					case 'comment':
						$field->setType('textarea');
						break;
					default:
						$field->setType('input');
				}
			}
			if ($field !== null) {
				$field->setMandatory(in_array($id, $mandatory));
				$fields[$key] = $field;
			}
		}
		return $fields;
	}
}
