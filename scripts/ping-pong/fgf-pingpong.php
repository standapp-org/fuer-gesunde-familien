#!/usr/bin/env php
<?php

/*
Script CLI (command line interface) for synchronizing two database
*/

$synchronizeTables = [
    'pages',
//    'tt_content',
//    'be_groups',
//    'be_users',
//    'fe_groups',
//    'fe_users',
//    'sys_refindex',
//    'sys_template',
//    'tx_datafilter_filters',
//    'tx_dataquery_queries',
//    'tx_displaycontroller_components_mm',
//    'tx_phpdisplay_displays',
];

$synchronizeTablesDefaultValues = [
    'pages' => [

        'media' => 0,
//        'subtitle',
//        'author',
//        'nav_title',
    ],
//    'tt_content' => [
//
//        'subheader',
//        'header_link'
//    ],
//    'be_groups' => [],
//    'be_users' => [
//        'password'
//    ],
//    'fe_groups' => [],
//    'fe_users' => [],
//    'sys_domain' => [],
//    'sys_refindex' => [],
//    'sys_template' => [],
//    'tx_datafilter_filters' => [],
//    'tx_dataquery_queries' => [],
//    'tx_displaycontroller_components_mm' => [],
//    'tx_phpdisplay_displays' => [],
];


$importTables = array(
    //'pages',
//	'tt_content',
//	'sys_file',
//	'sys_file_reference',
//	'be_users',
);
############################################
# Database credentials
############################################

$sourceCredentials = [
    'host' => 'db',
    'username' => 'root',
    'password' => 'root',
    'database' => 'db2',
    'port' => '3306',
];

$targetCredentials = [
    'host' => 'db',
    'username' => 'root',
    'password' => 'root',
    'database' => 'db',
    'port' => '3306',
];


############################################
# Beginning of the script
############################################

include('Classes/Database.php');
include('Classes/Logger.php');
include('Credentials.php');

$logger = new Logger();

$dbSource = new Ecodev\Database($sourceCredentials['host'], $sourceCredentials['username'], $sourceCredentials['password'], $sourceCredentials['port']);
$dbSource->connect($sourceCredentials['database']);

$dbTarget = new Ecodev\Database($targetCredentials['host'], $targetCredentials['username'], $targetCredentials['password'], $targetCredentials['port']);
$dbTarget->connect($targetCredentials['database']);

// Synchronize tables
foreach ($synchronizeTables as $synchronizeTable) {
    $logger->log('Synchronizing table "' . $synchronizeTable . '" .....');
    $fieldStructures = $dbTarget->select('SHOW COLUMNS FROM ' . $synchronizeTable);
    $newFieldsNames = array();
    foreach ($fieldStructures as $fieldStructure) {
        $newFieldsNames[] = $fieldStructure['Field'];
    }

    // Build clause part of the request
    $clause = '1 = 1';
    $specialFields = array(
        'deleted',
//        'disable',
//        'hidden'
    );
    foreach ($specialFields as $specialField) {
        if (in_array($specialField, $newFieldsNames)) {
            $clause .= ' AND ' . $specialField . ' = 0';
        }
    }
    //$toImportValues = $dbOld->select('SELECT * FROM ' . $synchronizeTable . ' WHERE ' . $clause);
    $toImportValues = $dbSource->select('SELECT * FROM ' . $synchronizeTable . ' WHERE ' . $clause);

    $dbTarget->delete($synchronizeTable); //truncating table

    /*
    $dbNew->query('TRUNCATE TABLE '. $table); => other way to do it
    */
    foreach ($toImportValues as $index => $toImportValue) {

        // We sanitize the data by removing the unwanted fields
        foreach ($toImportValue as $fieldName => $value) {
            if (!in_array($fieldName, $newFieldsNames)) {
                unset($toImportValue[$fieldName]);
            }
        }

        foreach ($synchronizeTablesDefaultValues[$synchronizeTable] as $fieldName => $value) {
            if (!$toImportValue[$fieldName]) {
                $toImportValue[$fieldName] = $synchronizeTablesDefaultValues[$synchronizeTable][$fieldName];
            }
        }
        $dbTarget->insert($synchronizeTable, $toImportValue);
    }
    $logger->log('Synchronized!!!');
}
$logger->log('All tables synchronized successfully!!!');

// Import tables
foreach ($importTables as $importTable) {
    $logger->log('Import whole content of table: "' . $importTable . '"');
    $exportCommand = sprintf('mysqldump -u %s -p"%s" %s %s > /tmp/%s.sql',
        $sourceCredentials['username'],
        $sourceCredentials['password'],
        $sourceCredentials['database'],
        $importTable,
        $importTable
    );

    exec($exportCommand);
    $importCommand = sprintf('mysql -u %s -p"%s" %s < /tmp/%s.sql',
        $targetCredentials['username'],
        $targetCredentials['password'],
        $targetCredentials['database'],
        $importTable
    );

    exec($importCommand);
    $logger->log('Done for: ' . $importTable . '!!!');
}
$logger->log('All tables imported successfully in the new database!!!');
