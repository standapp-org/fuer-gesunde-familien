#!/usr/bin/env php
<?php
/*
Script CLI (command line interface) for synchronizing two database
*/

$synchronizeTables = [
    'pages',
        'tt_content',
        'be_groups',
        'be_users',
    //    'sys_refindex', //todo it can be calculated
        'sys_file',
        'sys_file_reference',
];

$synchronizeTablesDefaultValues = [
    'pages' => [
        'media' => 0,
        //        'subtitle',
        //        'author',
        //        'nav_title',
    ],
        'tt_content' => [

//            'header_link'
        ],
    //    'be_groups' => [],
    //    'be_users' => [
    //        'password'
    //    ],
    'sys_file'=>[

    ],
    'sys_file_reference'=>[

    ],
    'be_groups'=>[

    ],
    'be_users'=>[

    ],

];

$importTables = [
    //'pages',
    //	'tt_content',
    //	'sys_file',
    //	'sys_file_reference',
    //	'be_users',
];
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

include 'Classes/Database.php';
include 'Classes/Logger.php';
include 'Credentials.php';

$logger = new Logger();

$dbSource = new Ecodev\Database(
    $sourceCredentials['host'],
    $sourceCredentials['username'],
    $sourceCredentials['password'],
    $sourceCredentials['port'],
);
$dbSource->connect($sourceCredentials['database']);

$dbTarget = new Ecodev\Database(
    $targetCredentials['host'],
    $targetCredentials['username'],
    $targetCredentials['password'],
    $targetCredentials['port'],
);
$dbTarget->connect($targetCredentials['database']);

// Synchronize tables
foreach ($synchronizeTables as $synchronizeTable) {
    $logger->log('Synchronizing table "' . $synchronizeTable . '" .....');
    $fieldStructures = $dbTarget->select('SHOW COLUMNS FROM ' . $synchronizeTable);
    $newFieldsNames = [];
    foreach ($fieldStructures as $fieldStructure) {
        $newFieldsNames[] = $fieldStructure['Field'];
    }

    // Build clause part of the request
    $clause = '1 = 1';
    $specialFields = [
        'deleted',
                'disable',
                'hidden'
    ];
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
    $exportCommand = sprintf(
        'mysqldump -u %s -p"%s" %s %s > /tmp/%s.sql',
        $sourceCredentials['username'],
        $sourceCredentials['password'],
        $sourceCredentials['database'],
        $importTable,
        $importTable,
    );

    exec($exportCommand);
    $importCommand = sprintf(
        'mysql -u %s -p"%s" %s < /tmp/%s.sql',
        $targetCredentials['username'],
        $targetCredentials['password'],
        $targetCredentials['database'],
        $importTable,
    );

    exec($importCommand);
    $logger->log('Done for: ' . $importTable . '!!!');
}

$logger->log('All tables imported successfully in the new database!!!');

$dbTarget->update(
    'pages',
    ['slug' => '/', 'backend_layout' => 'pagets__default', 'backend_layout_next_level' => 'pagets__default', 'TSconfig' => ''],
    ['uid' => '1'],
);
$dbTarget->update('pages', ['slug' => '/home'], ['uid' => '7']);
$dbTarget->update('pages', ['slug' => '/zugang-life-archivech'], ['uid' => '21']);
$dbTarget->update('pages', ['slug' => '/anmeldung-hli-tagung-2023'], ['uid' => '63']);
$dbTarget->update('pages', ['slug' => '/impressum-kontakt'], ['uid' => '3']);
