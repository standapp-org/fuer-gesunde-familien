<?php

/**
 * Extension Manager/Repository config file for ext "site_package".
 */
$EM_CONF[$_EXTKEY] = [
    'title' => 'Site Package',
    'description' => '',
    'category' => 'templates',
    'constraints' => [
        'depends' => [
            'bootstrap_package' => '13.0.0-14.9.99',
        ],
        'conflicts' => [
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'TC\\SitePackage\\' => 'Classes',
        ],
    ],
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'techniConcept',
    'author_email' => 'info@techniconcept.ch',
    'author_company' => 'TeC',
    'version' => '1.0.0',
];
