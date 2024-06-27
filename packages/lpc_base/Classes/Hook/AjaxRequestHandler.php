<?php
namespace LPC\LpcBase\Hook;

/**
 * only used for TYPO3 < 9 as replacement for LPC\LpcBase\Middleware\AjaxHandlerMiddleware
 */
class AjaxRequestHandler
{
	public function process($params,$pObj) {
		if(preg_match('/(?:^|[?&])lpcajax=(\d+)(?:$|&)/',$_SERVER['QUERY_STRING'],$match)) {
			$uid = $match[1];
			if(preg_match('/tx_([a-z]+_[a-z]+)%5B(action|controller)%5D=/',$_SERVER['QUERY_STRING'],$match)) {
				$namespace = $match[1];
				$pageConfig = [
					'10' => '< tt_content.list.20.'.$namespace,
				];
				$pObj->setup[$pObj->sPre.'.'] = $pageConfig;
				$pObj->pSetup = $pageConfig;
				$params['config']['disableAllHeaderCode'] = true;
				$params['config']['disableCharsetHeader'] = true;
				if(isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json') {
					$parsedBody = json_decode(file_get_contents('php://input'),true);
					$_POST = array_merge_recursive($_POST,$parsedBody);
				}
			}
		}
	}
}
