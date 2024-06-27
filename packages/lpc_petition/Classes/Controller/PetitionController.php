<?php
namespace LPC\LpcPetition\Controller;

use LPC\Captcha\Answer\CaptchaAnswer;
use LPC\LpcPetition\Domain\Model\Entry;
use LPC\LpcPetition\Domain\Repository\EntryRepository;
use LPC\LpcPetition\Domain\Repository\FieldRepository;
use LPC\LpcPetition\Domain\Validator\FieldsValidator;
use Psr\Http\Message\ResponseInterface;
use SJBR\StaticInfoTables\Domain\Repository\CountryRepository;
use Symfony\Component\Mime\Address;
use TYPO3Fluid\Fluid\View\AbstractTemplateView;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Validation\Validator\ConjunctionValidator;
use TYPO3\CMS\Fluid\View\TemplatePaths;

class PetitionController extends ActionController
{
	public function __construct(
		private EntryRepository $entryRepository,
		private PersistenceManager $persistenceManager,
		private CountryRepository $countryRepository,
		private FieldRepository $fieldRepository,
		private Mailer $mailer,
	) {}

	public function initializeListAction(): void {
		if (!empty($this->settings['storagePid'])) {
			$querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
			$querySettings->setStoragePageIds(GeneralUtility::intExplode(',',$this->settings['storagePid'], true));
			$this->entryRepository->setDefaultQuerySettings($querySettings);
		}
	}

	public function listAction(): ResponseInterface {
		$entries = [
			LocalizationUtility::translate('list.charRepo.tab.new','LpcPetition') => $this->entryRepository->findNewestPublic(5),
		];
		for ($i = 65; $i <= 90; $i++) {
			$entries[chr($i)] = [];
		}
		$entries['0-9'] = [];
		$entries['#'] = [];
		foreach($this->entryRepository->findPublic() as $entry) {
			$entries[$entry->getFirstLetter()][] = $entry;
		}
		$this->view->assign('entries',$entries);
		$this->view->assign('amount',$this->entryRepository->countAll());
		$this->view->assign('fields',$this->settings['listFields']
			? GeneralUtility::trimExplode(',',$this->settings['listFields'],true)
			: ['firstname','lastname','place','title']);
		return $this->htmlResponse();
	}

	public function formAction(): ResponseInterface {
		$this->view->assign('fields',$this->fieldRepository->getFormFieldsFromSettings($this->settings));
		$countries = [
			'top' => array_fill_keys(['CH','DE','AT','IT','FR','LI'],null),
			'all' => [],
		];
		foreach($this->countryRepository->findAllOrderedBy('shortNameLocal') as $country) {
			$key = array_key_exists($country->getIsoCodeA2(),$countries['top']) ? 'top' : 'all';
			$countries[$key][$country->getIsoCodeA2()] = $country->getShortNameLocal();
		}
		$this->view->assign('countries',$countries);
		$this->view->assign('captcha', $this->useCaptcha() ? 'math' : 'none');
		return $this->htmlResponse();
	}

	public function initializeSignAction(): void {
		$baseValidator = $this->arguments->getArgument('entry')->getValidator();
		if (!$baseValidator instanceof ConjunctionValidator) {
			$origValidator = $baseValidator;
			$baseValidator = new ConjunctionValidator();
			$baseValidator->addValidator($origValidator);
			$this->arguments->getArgument('entry')->setValidator($baseValidator);
		}
		$baseValidator->addValidator(new FieldsValidator($this->fieldRepository->getFormFieldsFromSettings($this->settings)));
	}

	protected function useCaptcha(): bool {
		return $this->settings['captcha']
			&& (stripos($_SERVER['HTTP_USER_AGENT'],'safari') === false
				|| stripos($_SERVER['HTTP_USER_AGENT'],'mac os x') === false);
	}

	public function signAction(Entry $entry, CaptchaAnswer $captcha = null): ResponseInterface {
		$otherAddresses = $this->entryRepository->findByMail($entry->getMail());
		if($otherAddresses->count() > 0) {
			$this->view->assign('otherAddresses',$otherAddresses);
			$this->entryRepository->setSessionObject($entry);
			return $this->htmlResponse();
		} else {
			return $this->sign($entry);
		}
	}

	public function forceAction(): ResponseInterface {
		$entry = $this->entryRepository->findSessionObject();
		if($entry) {
			return $this->sign($entry);
		} else {
			return $this->redirect('form');
		}
	}

	protected function sign(Entry $entry): ResponseInterface {
		$this->entryRepository->add($entry);
		if($this->settings['doubleOptIn']) {
			$entry->setHidden(true);
			$this->persistenceManager->persistAll();
			$mail = $this->createFluidEmail()
				->setTemplate('Email/DoubleOptIn')
				->format(FluidEmail::FORMAT_BOTH)
				->assign('entry', $entry)
				->assign('settings', $this->settings)
				->to(new Address($entry->getMail(), trim($entry->getFirstname().' '.$entry->getLastname())))
				->from(new Address($this->settings['fromEmail'], $this->settings['fromEmailName']))
				->subject(trim($this->settings['doubleOptInSubject']) ?: \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('mail_doubleoptin_subject', 'LpcPetition') ?? '');
			$this->mailer->send($mail);
			return $this->redirect('doubleOptIn');
		} else {
			$this->persistenceManager->persistAll();
			$this->notify($entry);
			$this->entryRepository->removeSessionObject();
			return $this->redirectToThanksPage();
		}
	}

	public function doubleOptInAction(?string $hash = null): ResponseInterface {
		if($hash) {
			if(($entry = $this->entryRepository->findByOptInHash($hash))) {
				if($entry->getHidden() == true) {
					$entry->setHidden(false);
					$this->entryRepository->update($entry);
					$this->notify($entry);
					$this->entryRepository->removeSessionObject();
				}
				// if already confirmed, we go to thanks page anyway
				return $this->redirectToThanksPage();
			} else {
				$this->view->assign('fail',true);
			}
		}
		return $this->htmlResponse();
	}

	protected function notify(Entry $entry): void {
		if(GeneralUtility::validEmail($this->settings['notifyEmail'])) {
			$mail = $this->createFluidEmail()
				->setTemplate('Email/Notify')
				->format(FluidEmail::FORMAT_HTML)
				->assign('entry', $entry)
				->assign('fields', $this->fieldRepository->getFormFieldsFromSettings($this->settings))
				->assign('settings', $this->settings)
				->to(new Address($this->settings['notifyEmail']))
				->from(new Address($this->settings['fromEmail'], $this->settings['fromEmailName']))
				->subject(LocalizationUtility::translate('notifyMail_subject','LpcPetition').' - '.GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
			$this->mailer->send($mail);
		}
	}

	protected function redirectToThanksPage(): ResponseInterface {
		if($this->settings['redirectPid']) {
			return $this->redirectToUri($this->uriBuilder->reset()->setTargetPageUid($this->settings['redirectPid'])->build());
		} else {
			return $this->redirect('thanks');
		}
	}

	public function thanksAction(): ResponseInterface {
		return $this->htmlResponse();
	}

	protected function createFluidEmail(): FluidEmail {
		if ($this->view instanceof AbstractTemplateView) {
			$templatePaths = $this->view->getRenderingContext()->getTemplatePaths();
		} else {
			$templatePaths = new TemplatePaths('LpcPetition');
		}
		$email = new FluidEmail($templatePaths);
		$email->setRequest($this->request);
		return $email;
	}
}
