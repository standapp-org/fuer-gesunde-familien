<?php
namespace LPC\LpcBase\Hook;

use TYPO3\CMS\Core\Configuration\Event\AfterTcaCompilationEvent;

class SingleFileFormManipulation
{
    public function __invoke(AfterTcaCompilationEvent $event): void {
		$tca = $event->getTca();
		foreach ($tca as $table => $tableConfig) {
			foreach ($tableConfig['columns'] as $column => $config) {
				if (($config['config']['type'] ?? '') === 'file' && ($config['config']['singleFile'] ?? false) == true) {
					unset($tca[$table]['columns'][$column]['config']['foreign_field']);
					unset($tca[$table]['columns'][$column]['config']['foreign_table_field']);
					$tca[$table]['columns'][$column]['config']['maxitems'] = 1;
				}
			}
		}
		$event->setTca($tca);
	}
}
