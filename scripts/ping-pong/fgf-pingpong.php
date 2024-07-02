#!/usr/bin/env php
<?php
/*
Script CLI (command line interface) for synchronizing two database
*/

$credentialsFile = 'credentials.php';
if (file_exists($credentialsFile)) {
    include $credentialsFile;
}

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
    'sys_file' => [],
    'sys_file_reference' => [],
    'be_groups' => [],
    'be_users' => [],
];

$importTables = [
    //'tx_lpcbase_domain_model_sociallink',
    //'tx_captcha_ip',
    'tx_lpcpetition_domain_model_entry',
    'tx_lpcpetition_domain_model_field',
    'tx_lpcpetition_domain_model_entry',
    'tx_lpcpetition_domain_model_field',
    'tx_powermail_domain_model_answer',
    'tx_powermail_domain_model_answers',
    'tx_powermail_domain_model_field',
    'tx_powermail_domain_model_fields',
    'tx_powermail_domain_model_form',
    'tx_powermail_domain_model_forms',
    'tx_powermail_domain_model_mail',
    'tx_powermail_domain_model_mails',
    'tx_powermail_domain_model_page',
    'tx_powermail_domain_model_pages',
];
############################################
# Database credentials
############################################

if (!isset($sourceCredentials)) {
    $sourceCredentials = [
        'host' => 'db',
        'username' => 'root',
        'password' => 'root',
        'database' => 'db2',
        'port' => '3306',
    ];
}

if (!isset($targetCredentials)) {
    $targetCredentials = [
        'host' => 'db',
        'username' => 'root',
        'password' => 'root',
        'database' => 'db',
        'port' => '3306',
    ];
}

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
    $specialFields = ['deleted', 'disable'];
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
    [
        'slug' => '/',
        'backend_layout' => 'pagets__default',
        'backend_layout_next_level' => 'pagets__default',
        'TSconfig' => '',
    ],
    ['uid' => '1'],
);
$dbTarget->update('pages', ['slug' => '/home'], ['uid' => '7']);
$dbTarget->update('pages', ['slug' => '/zugang-life-archivech'], ['uid' => '21']);
$dbTarget->update('pages', ['slug' => '/anmeldung-hli-tagung-2023'], ['uid' => '63']);
$dbTarget->update('pages', ['slug' => '/impressum-kontakt'], ['uid' => '3']);
$dbTarget->update('pages', ['slug' => '/keine-kassenpflicht-fuer-ivf'], ['uid' => '61']);
$dbTarget->update(
    'pages',
    [
        'slug' =>
            '/anmeldung-zugang-online-medienbeobachtung-abstimmungskampf-referendum-nein-zur-organentnahme-ohne-zustimmung',
    ],
    ['uid' => '59'],
);
$dbTarget->update('pages', ['slug' => '/ref-organspende-medienseminar'], ['uid' => '57']);
$dbTarget->update('pages', ['slug' => '/petition-freie-wahl-statt-maskenzwang'], ['uid' => '53']);
$dbTarget->update('pages', ['slug' => '/pinnwand-maskenerfahrungen'], ['uid' => '55']);
$dbTarget->update('pages', ['slug' => '/formular-uebermittlung-inhalte-pinnwand'], ['uid' => '56']);
$dbTarget->update('pages', ['slug' => '/hli-tagung-kultur-der-sorge'], ['uid' => '51']);
$dbTarget->update(
    'pages',
    ['slug' => '/anmeldung-zugang-online-medienbeobachtung-kampagne-nein-zur-ehe-fuer-alle'],
    ['uid' => '48'],
);
$dbTarget->update('pages', ['slug' => '/anmeldung-tagung'], ['uid' => '45']);
$dbTarget->update('pages', ['slug' => '/schluss-lockdown-gottesdienste'], ['uid' => '41']);
$dbTarget->update('pages', ['slug' => '/zurueck-zur-vernunft-schluss-mit-corona-lockdown'], ['uid' => '37']);
$dbTarget->update('pages', ['slug' => '/moratorium-organspenden-nach-herztod'], ['uid' => '35']);
$dbTarget->update('pages', ['slug' => '/befreiung-homo-netzwerke'], ['uid' => '28']);
$dbTarget->update('pages', ['slug' => '/inside-church-no-homo-network'], ['uid' => '31']);
$dbTarget->update(
    'pages',
    ['slug' => '/petition-aux-conseil-federal-sensiblisez-le-public-aux-consequences-de-lavortement'],
    ['uid' => '25'],
);
$dbTarget->update('pages', ['slug' => '/abtreibungsfolgen-oeffentlich-machen'], ['uid' => '24']);
$dbTarget->update('pages', ['slug' => '/petition-kreuz-bleibt'], ['uid' => '22']);
$dbTarget->update('pages', ['slug' => '/testseite'], ['uid' => '26']);
$dbTarget->update('pages', ['slug' => '/uni-fr-genderismus-nein'], ['uid' => '14']);
$dbTarget->update('pages', ['slug' => '/medienmitteilungen'], ['uid' => '18']);
$dbTarget->update('pages', ['slug' => '/links-gender-thema'], ['uid' => '17']);
$dbTarget->update('pages', ['slug' => '/pressemitteilungen'], ['uid' => '6']);
$dbTarget->update('pages', ['slug' => '/hier-unterzeichnen'], ['uid' => '4']);
$dbTarget->update('pages', ['slug' => '/rechtliche-hinweise'], ['uid' => '13']);
$dbTarget->update('pages', ['slug' => '/testseite-einbau-archivista-archiv-i-frame'], ['uid' => '34']);
$dbTarget->update('pages', ['slug' => '/test-seite-flyer-ueberall-kartenansicht'], ['uid' => '32']);
$dbTarget->update('pages', ['slug' => '/test-seite-flyer-ueberall-i-frame-suchen-und-warenkorb'], ['uid' => '33']);
