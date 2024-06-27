<?php
return array_merge(
	\LPC\LpcBase\Domain\Model\SocialLink::getPlatformIcons(),
	\LPC\LpcBase\Configuration\PluginRegistry::getIcons(),
);
