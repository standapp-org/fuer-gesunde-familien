<?php
namespace LPC\LpcPetition\Domain\Repository;

use LPC\LpcPetition\Domain\Model\Entry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\Extbase\Security\Exception\InvalidHashException;

/**
 * @extends Repository<Entry>
 */
class EntryRepository extends Repository
{
	protected Entry|bool|null $sessionObject = null;

	protected $defaultOrderings = [
		'lastname' => 'ASC',
		'firstname' => 'ASC',
	];

	/**
	 * @return QueryResultInterface<Entry>
	 */
	public function findPublic(): QueryResultInterface {
		$query = $this->createQuery();
		$query->matching(
			$query->equals('private',0)
		);
		return $query->execute();
	}

	/**
	 * @return QueryResultInterface<Entry>
	 */
	public function findNewestPublic(int $num): QueryResultInterface {
		$query = $this->createQuery();
		$query->matching(
			$query->equals('private',0)
		);
		$query->setOrderings(['crdate' => 'DESC']);
		$query->setLimit($num);
		return $query->execute();
	}

	public function findByOptInHash(string $hash): ?Entry {
		try {
			$hash = GeneralUtility::makeInstance(HashService::class)->validateAndStripHmac($hash);
		} catch (InvalidHashException) {
			return null;
		}
		$uid = (int)base_convert($hash, 36, 10);
		$query = $this->createQuery();
		$query->matching($query->equals('uid',$uid));
		$query->getQuerySettings()->setIgnoreEnableFields(true);
		return $query->execute()->getFirst();
	}

	public function findSessionObject(): ?Entry {
		if($this->sessionObject === null) {
			if($entry = $GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_lpcpetition_petitiondata')) {
				$this->sessionObject = unserialize($entry);
			} else {
				$this->sessionObject = false;
			}
		}
		return $this->sessionObject ?: null;
	}

	public function getSessionObject(): Entry {
		$entry = $this->findSessionObject();
		if(!$entry) {
			$entry = new \LPC\LpcPetition\Domain\Model\Entry;
			$this->setSessionObject($entry);
		}
		return $entry;
	}

	public function setSessionObject(Entry $entry): void {
		$this->sessionObject = $entry;
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_lpcpetition_petitiondata', serialize($entry));
		$GLOBALS['TSFE']->fe_user->storeSessionData();
	}

	public function removeSessionObject(): void {
		$this->sessionObject = false;
		$GLOBALS['TSFE']->fe_user->setKey('ses', 'tx_lpcpetition_petitiondata', null);
		$GLOBALS['TSFE']->fe_user->storeSessionData();
	}
}
