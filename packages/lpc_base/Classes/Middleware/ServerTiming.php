<?php
namespace LPC\LpcBase\Middleware;

class ServerTiming implements \Psr\Http\Server\MiddlewareInterface
{
	public function process(
		\Psr\Http\Message\ServerRequestInterface $request,
		\Psr\Http\Server\RequestHandlerInterface $handler
	): \Psr\Http\Message\ResponseInterface {
		$response = $handler->handle($request);
		if(\LPC\LpcBase\Utility\TimeTracker::isActive()) {
			foreach(\LPC\LpcBase\Utility\TimeTracker::getMetrics() as $name => $metrics) {
				$header = $name.';dur='.($metrics['dur']*1000);
				if(!empty($metrics['desc'])) {
					$header .= ';desc="'.addcslashes($metrics['desc'],"\x0..\x1f\x28\x29\x5c\x7f..\xff").'"';
				}

				// this would be the way to do it, but it will easily exceed the max header length as long as
				// 'server-timing' is missing in \TYPO3\CMS\Core\Http\AbstractApplication::MULTI_LINE_HEADERS
				// so we output the headers directly for now
				//$response = $response->withAddedHeader('Server-Timing', $header);
				header('Server-Timing: '.$header, false);
			}
		}
		return $response;
	}
}
