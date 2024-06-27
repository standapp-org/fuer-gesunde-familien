<?php
namespace LPC\LpcBase\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\RequestHandlerResolver;
use TYPO3\CMS\Extbase\Mvc\Web\RequestBuilder;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use LPC\LpcBase\Utility\ControllerActionHandler;

class AjaxHandlerMiddleware implements \Psr\Http\Server\MiddlewareInterface
{
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		if(preg_match('/(?:^|[?&])lpcajax=(\d+)(?:$|&)/', $request->getUri()->getQuery(), $match) === 1) {
			return GeneralUtility::makeInstance(ControllerActionHandler::class)->callAction((int)$match[1], $request);
		}
		return $handler->handle($request);
	}
}
