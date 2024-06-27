<?php
namespace LPC\LpcBase\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Fluid\View\StandaloneView;

class FluidController extends ActionController
{
	protected $defaultViewObjectName = StandaloneView::class;

	public function renderCachedAction(): ResponseInterface {
		return $this->htmlResponse();
	}

	public function renderUncachedAction(): ResponseInterface {
		return $this->htmlResponse();
	}

	protected function initializeView(StandaloneView $view): void {
		$view->setTemplateSource($this->settings['fluid']);
	}
}
