<?php
namespace LPC\LpcBase\Utility;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Dispatcher;
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class ControllerActionHandler {
	private ConnectionPool $connectionPool;
	private ConfigurationManagerInterface $configurationManager;
	private RequestBuilder $requestBuilder;
	private Dispatcher $requestDispatcher;
	private PersistenceManagerInterface $persistenceManager;
	private ResponseFactoryInterface $responseFactory;

	public function __construct(
		ConnectionPool $connectionPool,
		ConfigurationManagerInterface $configurationManager,
		RequestBuilder $requestBuilder,
		Dispatcher $requestDispatcher,
		PersistenceManagerInterface $persistenceManager,
		ResponseFactoryInterface $responseFactory
	) {
		$this->connectionPool = $connectionPool;
		$this->configurationManager = $configurationManager;
		$this->requestBuilder = $requestBuilder;
		$this->requestDispatcher = $requestDispatcher;
		$this->persistenceManager = $persistenceManager;
		$this->responseFactory = $responseFactory;
	}

	public function callAction(int $uid, ServerRequestInterface $request): ResponseInterface {
		$row = $this->connectionPool->getConnectionForTable('tt_content')->select(['*'], 'tt_content', ['uid' => $uid])->fetchAssociative();
		if ($row === false) {
			return $this->responseFactory->createResponse(404);
		}

		/** @var TypoScriptFrontendController */
		$tsfe = $request->getAttribute('frontend.controller');
		$tsfe->no_cache = true;
		$tsfe->preparePageContentGeneration($request);
		$tsfe->cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class, $GLOBALS['TSFE']);
		$tsfe->cObj->start($row, 'tt_content', $request);

		$this->configurationManager->setContentObject($tsfe->cObj);
		$setup = $row['CType'] === 'list'
			? $tsfe->tmpl->setup['tt_content.']['list.']['20.'][$row['list_type'].'.']
			: $tsfe->tmpl->setup['tt_content.'][$row['CType'].'.']['20.'];
		$this->configurationManager->setConfiguration($setup);

		$extbaseRequest = $this->requestBuilder->build($request);
		$response = $this->requestDispatcher->dispatch($extbaseRequest);

		$this->persistenceManager->persistAll();
		return $response;
	}
}
