<?php
namespace LPC\LpcBase\Domain\Repository;

use LPC\LpcBase\Domain\Model\SocialLink;
use LPC\LpcBase\Event\AfterLookupSocialLinks;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<SocialLink>
 */
class SocialLinkRepository extends Repository
{
	protected $defaultOrderings = ['sorting' => 'ASC'];

	public function __construct(
		private ConnectionPool $connectionPool,
		private EventDispatcherInterface $eventDispatcher,
	) {
		parent::__construct();
	}

	/**
	 * @return SocialLink[]
	 */
	public function findForPage(int $pageUid, bool $stopAtSiteroot = true, bool $mergeRecursive = false, bool $uniquePlatforms = true): array {
		$db = $this->connectionPool->getConnectionForTable('pages');

		$event = new AfterLookupSocialLinks([], $pageUid, $stopAtSiteroot, $mergeRecursive, $uniquePlatforms);

		$query = $this->createQuery();
		while($pageUid) {
			$query->getQuerySettings()->setStoragePageIds([$pageUid]);
			$query->getQuerySettings()->setRespectStoragePage(true);
			foreach($query->execute() as $link) {
				$event->addSocialLink($link);
			}

			if (count($event->getSocialLinks()) > 0 && $mergeRecursive === false) {
				break;
			}

			$page = $db->select(['pid', 'is_siteroot'], 'pages', ['uid' => $pageUid])->fetchAssociative();
			if($page === false || ($page['is_siteroot'] != 0 && $stopAtSiteroot)) {
				break;
			}
			$pageUid = $page['pid'];
		}

		$this->eventDispatcher->dispatch($event);
		return $event->getSocialLinks();
	}
}
