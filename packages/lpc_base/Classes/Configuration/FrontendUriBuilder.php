<?php
namespace LPC\LpcBase\Configuration;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class FrontendUriBuilder extends UriBuilder
{
	public function getRequest(): ?RequestInterface {
		$request = $this->request ?? $GLOBALS['TYPO3_REQUEST'];
		if ($request instanceof ServerRequestInterface) {
			$applicationType = $request->getAttribute('applicationType') ?? 0;
			if (($applicationType & SystemEnvironmentBuilder::REQUESTTYPE_FE) === 0) {
				$applicationType |= SystemEnvironmentBuilder::REQUESTTYPE_FE;
				$request = $request->withAttribute('applicationType', $applicationType);
			}
			if (!$request instanceof RequestInterface) {
				$request = new Request($request->withAttribute('extbase', new ExtbaseRequestParameters()));
			}
			return $this->request = $request;
		}
		return null;
	}

	public function simulatePageId(int $simulatePageId): void {
		throw new \Exception("simulatePageId does not work anymore in TYPO3 12.");
	}
}
