<?php

define('DB_HOST','adscan-dev.mysql.database.azure.com');
define('DB_USER', 'docsableadscan');
define('DB_PASS', '!&ZYQ&2Jq5gaFftJB6Uu2Z6Rvn!pvwBqBsgcvhvq');
define('DB_NAME', 'adscan');
define('BLOB_STORAGE_URL', 'https://abledocs-cors-anywhere.herokuapp.com/https://adscandevstorage.blob.core.windows.net/');
define('SMTP_HOST', getenv('SMTP_HOST'));
define('SMTP_USER', getenv('SMTP_USER'));
define('SMTP_USER_PASSWORD', getenv('SMTP_USER_PASSWORD'));


//SET ADSCAN_DATABASE_HOST="adscan-dev.mysql.database.azure.com"
//SET ADSCAN_DATABASE_NAME=""
//SET ADSCAN_DATABASE_USER=""
//SET ADSCAN_BLOB_STORAGE_BASE_URL=""
//SET ADSCAN_DATABASE_USER_PASSWORD=""
//SET SMTP_HOST="mail.smtp2go.com"
//SET SMTP_USER="abledocs"
//SET SMTP_USER_PASSWORD="M2llOHFkaHFwaWMw"




try {
    $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS);
    $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
}
catch(PDOException $e) {
    echo $e->getMessage();
    exit;
}

/**
 * This function will look for new crawls and count their compliant, non-compliant, and untagged files
 * and update them in the DB.
 * Used in includes/login.inc.php
 */
function checkForNewScans($db) {
    // get crawls that are complete and have no info about files found
    $sql = "SELECT * FROM adscan_crawls WHERE status = 'Complete' AND compliant is NULL AND Files_Found > 0 AND (Files_Scanned + Files_Error) = Files_Found";
    $stmt = $db->prepare($sql);

    // if there are any, count them
    try {
        if ($stmt->execute()) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                updateCountForCrawl($row['ID']);
            }
        }
    } catch (Exception $e) {
        // do nothing, just continue otherwise this would mess up the login process
        echo $e->getMessage();
    }
}

/**
 * Updates the counts for compliant, non-compliant, and untagged for a crawl
 * @param  integer $id the ID for the crawl
 * @throws TypeError when $id is not an int
 * @throws PDOException when DB cannot run a query successfully
 * @return bool whether or not this function ran successfully
 */
function updateCountForCrawl(int $id): bool
{
    // get the database info
    try {
        $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS);
        $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }
    catch(PDOException $e) {
        return false;
    }

    // get crawls that are complete and have no info about files found
    $sql = "SELECT * FROM adscan_files WHERE crawl_id = ?";
    $stmt = $db->prepare($sql);
    if ($stmt->execute([$id])) {
        // stores the stats of the crawl for the update
        $stats = array(
            'compliant' => 0,
            'nonComp' => 0,
            'untagged' => 0,
            'offsite' => 0,
            'errors' => 0,
        );
        while ($file = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // count the compliant, non-compliant and untagged and offsite
            if ($file["offsite"] === 1 || $file["offsite"] === null) {
                $stats['offsite']++;
                continue;
            }
            // if file encounter error while scanning, add to error count and skip
            if ($file["adscan_success"] === 2) {
                $stats['errors']++;
                continue;
            }
            if ($file["Tagged"] === "false" || $file["Tagged"] === "0" || $file["Tagged"] === null) {
                $stats['untagged']++;
                continue;
            }
            if ($file["UA_Index"] === "100.0") {
                $stats['compliant']++;
            } else {
                $stats['nonComp']++;
            }
        }
    }

    // update the values for that crawl, errors should already be there under adscan_crawls.Files_Error
    $updateSQL = "UPDATE adscan_crawls SET compliant = :compliant, nonCompliant = :nonCompliant, untagged = :untagged, offsiteFiles = :offsite WHERE ID = :crawlID";
    $updateSTMT = $db->prepare($updateSQL);
    $updateSTMT->bindParam(':compliant', $stats['compliant']);
    $updateSTMT->bindParam(':nonCompliant', $stats['nonComp']);
    $updateSTMT->bindParam(':untagged', $stats['untagged']);
    $updateSTMT->bindParam(':offsite', $stats['offsite']);
    $updateSTMT->bindParam(':crawlID', $id);
    return $updateSTMT->execute();
}