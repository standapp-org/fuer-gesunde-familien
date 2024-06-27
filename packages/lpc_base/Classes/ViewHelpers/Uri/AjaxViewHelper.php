<?php
namespace LPC\LpcBase\ViewHelpers\Uri;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * @source TYPO3\CMS\Fluid\ViewHelpers\Uri\ActionViewHelper
 */
class AjaxViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public function initializeArguments(): void
    {
        $this->registerArgument('action', 'string', 'Target action');
        $this->registerArgument('arguments', 'array', 'Arguments', false, []);
        $this->registerArgument('controller', 'string', 'Target controller. If NULL current controllerName is used');
        $this->registerArgument('extensionName', 'string', 'Target Extension Name (without `tx_` prefix and no underscores). If NULL the current extension name is used');
        $this->registerArgument('pluginName', 'string', 'Target plugin. If empty, the current plugin name is used');
        $this->registerArgument('pageUid', 'int', 'Target page. See TypoLink destination');
        $this->registerArgument('pageType', 'int', 'Type of the target page. See typolink.parameter', false, 0);
        $this->registerArgument('noCache', 'bool', 'Set this to disable caching for the target page. You should not need this.', false);
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI', false, '');
        $this->registerArgument('format', 'string', 'The requested format, e.g. ".html', false, '');
        $this->registerArgument('linkAccessRestrictedPages', 'bool', 'If set, links pointing to access restricted pages will still link to the page even though the page cannot be accessed.', false, false);
        $this->registerArgument('additionalParams', 'array', 'additional query parameters that won\'t be prefixed like $arguments (overrule $arguments)', false, []);
        $this->registerArgument('absolute', 'bool', 'If set, an absolute URI is rendered', false, false);
        $this->registerArgument('addQueryString', 'string', 'If set, the current query parameters will be kept in the URL. If set to "untrusted", then ALL query parameters will be added. Be aware, that this might lead to problems when the generated link is cached.', false, false);
        $this->registerArgument('argumentsToBeExcludedFromQueryString', 'array', 'arguments to be removed from the URI. Only active if $addQueryString = TRUE', false, []);
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        /** @var RenderingContext $renderingContext */
        $request = $renderingContext->getRequest();
        if (!$request instanceof RequestInterface) {
            throw new \RuntimeException(
                'ViewHelper f:uri.action can be used only in extbase context and needs a request implementing extbase RequestInterface.',
                1639819692
            );
        }

        $pageUid = (int)($arguments['pageUid'] ?? 0);
        $pageType = (int)($arguments['pageType'] ?? 0);
        $noCache = (bool)($arguments['noCache'] ?? false);
        /** @var string|null $section */
        $section = $arguments['section'] ?? null;
        /** @var string|null $format */
        $format = $arguments['format'] ?? null;
        $linkAccessRestrictedPages = (bool)($arguments['linkAccessRestrictedPages'] ?? false);
        /** @var array<string,mixed>|null $additionalParams */
        $additionalParams = $arguments['additionalParams'] ?? null;
        $absolute = (bool)($arguments['absolute'] ?? false);
        /** @var bool|string $addQueryString */
        $addQueryString = $arguments['addQueryString'] ?? false;
        /** @var array<mixed>|null $argumentsToBeExcludedFromQueryString */
        $argumentsToBeExcludedFromQueryString = $arguments['argumentsToBeExcludedFromQueryString'] ?? null;
        /** @var string|null $action */
        $action = $arguments['action'] ?? null;
        /** @var string|null $controller */
        $controller = $arguments['controller'] ?? null;
        /** @var string|null $extensionName */
        $extensionName = $arguments['extensionName'] ?? null;
        /** @var string|null $pluginName */
        $pluginName = $arguments['pluginName'] ?? null;
        /** @var array<string,mixed>|null $arguments */
        $arguments = $arguments['arguments'] ?? [];

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $uriBuilder->reset()->setRequest($request);

        if ($pageUid > 0) {
            $uriBuilder->setTargetPageUid($pageUid);
        }
        if ($pageType > 0) {
            $uriBuilder->setTargetPageType($pageType);
        }
        if ($noCache === true) {
            $uriBuilder->setNoCache($noCache);
        }
        if (is_string($section)) {
            $uriBuilder->setSection($section);
        }
        if (is_string($format)) {
            $uriBuilder->setFormat($format);
        }
        if (is_array($additionalParams)) {
            $uriBuilder->setArguments($additionalParams);
        }
        if ($absolute === true) {
            $uriBuilder->setCreateAbsoluteUri($absolute);
        }
        if (!empty($addQueryString) && $addQueryString !== 'false') {
            $uriBuilder->setAddQueryString($addQueryString);
        }
        if (is_array($argumentsToBeExcludedFromQueryString)) {
            $uriBuilder->setArgumentsToBeExcludedFromQueryString($argumentsToBeExcludedFromQueryString);
        }
        if ($linkAccessRestrictedPages === true) {
            $uriBuilder->setLinkAccessRestrictedPages(true);
        }

		$cObjUid = GeneralUtility::makeInstance(ConfigurationManagerInterface::class)->getContentObject()?->data['uid'] ?? null;
		if ($cObjUid === null) {
			throw new \Exception('could not find current content elements uid for generating ajax uri');
		}
		$arguments['additionalParams']['lpcajax'] = $cObjUid;

        return $uriBuilder->uriFor($action, $arguments, $controller, $extensionName, $pluginName);
    }
}
