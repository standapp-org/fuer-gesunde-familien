<?php
namespace LPC\LpcBase\Routing;

trait SanitizePathSegmentTrait
{
	protected function sanitizePathSegment(string $pathSegment): string {
		return preg_replace(
			'#[^a-z0-9_]+#',
			'-',
			transliterator_transliterate(
				'Any-Latin;de-ASCII;Lower',
				$pathSegment
			)
		);
	}
}
