<?php
namespace LPC\LpcBase\Routing;

interface SluggableInterface
{
	public function getPathSegment(): string;
}
