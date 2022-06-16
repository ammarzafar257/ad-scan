<?php
// Get the DB config info
require("config.php");

// if no action the cancel any request
if (!$_GET['action']) {
    die();
}

// Determine what request is being made
switch($_GET['action']) {
    case 'getCrawls':  // get all crawls under a specified clientID
        echo getCrawls($db);
        break;
    case 'getFiles':
        echo getFiles($db);
        break;
    case 'getFilesForLite':
        echo getFilesForLite($db);
        break;
    case 'getSingleFiles':
        echo getSingleFile($db);
        break;
    case 'fileOwnership':
        echo fileOwnership($db);
        break;
    case 'getFileErrorCounts' :
        echo getFileErrors($db);
        break;
    case 'getAvgUA':
        echo getAvgUA($db);
        break;
    case 'getAllcrawls':
        echo getAllcrawls($db);
        break;
    case 'updateCounts':
        echo updateCounts();
        break;
    case 'csvDataCrawl':
        echo csvDataCrawl($db);
        break;
    default: // in case not action was found
        echo "Error: add action to request";
}

/**
 * Get all crawls under a clientID,
 * gets ID, starturl, crawl_time_end
 * @param $db
 * @return false|string JSON of clients
 */
function getCrawls($db) {
    // Get the clientId, if not found in request, kill the action
    $id = null;
    if( isset($_GET['id'])) {
        if (is_numeric($_GET['id'])) {
            $id = $_GET['id'];
        } else {
            sendStatus(400, 'Error: id number sent was not a number -> ' . $_GET['id']);
        }
    } else {
        sendStatus(400, 'Error: Missing id number');
    }

    try {
        $sql = "SELECT ID, starturl, crawl_time_end, compliant, nonCompliant, untagged, offsiteFiles, Files_Error,ScanType
        FROM adscan_crawls 
        WHERE clientID = ? AND status = 'Complete'";
        $crawls = $db->prepare($sql);
        $crawls->execute([$id]);

        // get the crawls
        $scans = array();
        while ($crawl = $crawls->fetch(PDO::FETCH_ASSOC)) {
            array_push($scans, $crawl);
        }

        // return JSON of crawls
        return json_encode($scans);
    } catch (PDOException $e) {
        sendStatus(500, 'Error: DB error fetching crawl data');
    }
}

/**
 * Get all files from a scan
 * @param $db
 * @return false|string JSON of files
 */
function getFiles($db) {
    // Get the clientId, if not found in request, kill the action
    $crawlID = null;
    if (isset($_GET['crawl'])) {
        if (is_numeric($_GET['crawl'])) {
            $crawlID = $_GET['crawl'];
        } else {
            sendStatus(400, 'Error: id number sent was not a number -> ' . $_GET['id']);
        }
    } else {
        sendStatus(400, 'Error: Missing id number');
    }

    try {
        // get the files
        $sql = 'SELECT crawl_id ,ID, filename, url, Title, Tagged, Producer, ROUND(UA_Index, 2) as "UA_Index", NumPages, FileSize, 
               subject, keywords, Author, Creator , ISO, OpenPassword, Lang, Fillable, CreationDate, ModDate, offsite, c.passed, c.`Warnings`, c.failed
            FROM adscan_files 
            LEFT JOIN (SELECT c.FileID , SUM(c.Passed) AS passed, SUM(c.`Warnings`) AS Warnings, SUM(c.`Errors`) AS failed
                         FROM checks c
                         WHERE FileID IN (SELECT ID FROM adscan_files WHERE crawl_id = ?)
                         GROUP BY c.FileID) AS c
            ON ID = c.FileID
            WHERE adscan_files.crawl_id  = ?';
        $crawlFiles = $db->prepare($sql);
        $crawlFiles->execute([$crawlID, $crawlID]);

        $files = array();
        while ($file = $crawlFiles->fetch(PDO::FETCH_ASSOC)) {
            array_push($files, $file);
        }

        // return json of the files
        return json_encode($files);
    } catch (PDOException $e) {
        sendStatus(500, 'Error: DB error fetching files');
    }
}

/**
 * Gets all the file data for showing a lite scan data
 * Also Requires GET param named 'crawl'
 * @param $db PDO ADScan database
 * @return false|string Json string for the queried data
 */
function getFilesForLite($db) {

    ini_set('memory_limit', '-1');

    // Get the clientId, if not found in request, kill the action
    $crawlID = null;
    if (isset($_GET['crawl'])) {
        if (is_numeric($_GET['crawl'])) {
            $crawlID = $_GET['crawl'];
        } else {
            sendStatus(400, 'Error: id number sent was not a number -> ' . $_GET['id']);
        }
    } else {
        sendStatus(400, 'Error: Missing id number');
    }

    try {
        // get the files
        $sql = 'SELECT ID ,filename, ROUND(UA_Index, 2) as "UA_Index", Title, passed_check, warned_check, failed_check, Tagged, NumPages
            FROM adscan_files AS f
            WHERE f.crawl_id = ?';
        $crawlFiles = $db->prepare($sql);
        $crawlFiles->execute([$crawlID]);
        $files = array();
        while ($file = $crawlFiles->fetch(PDO::FETCH_ASSOC)) {
            array_push($files, $file);
        }
        // return json of the files
        return json_encode($files);
    } catch (PDOException $e) {
        sendStatus(500, 'Error: DB error fetching files or lite scan');
    }
}



/**
 * Get data for a single file, used in pdf viewer
 * @param $db
 * @return false|string Json of file data
 */
function getSingleFile($db) {
    // Get the file id
    $id = null;
    if( isset($_GET['id'])) {
        if (is_numeric($_GET['id'])) {
            $id = $_GET['id'];
        } else {
            sendStatus(400, 'Error: id number sent was not a number -> ' . $_GET['id']);
        }
    } else {
        sendStatus(400, 'Error: Missing id number');
    }

    try {
        // get the file
        $sql = 'SELECT * FROM adscan_files 
            WHERE adscan_files.ID  = ?';
        $crawlFile = $db->prepare($sql);
        $crawlFile->execute([$id]);

        $file = $crawlFile->fetch(PDO::FETCH_ASSOC);
        // return json of the files
        return json_encode(array($file));
    } catch (PDOException $e) {
        sendStatus(500, 'Error: DB error fetching file data');
    }
}

/**
 * Checks that file belongs to a certail owner
 * Rquires GET params "fileID" and "client" for client id number
 * @param $db ADScan DB
 * @return string either "true" or "false"
 */
function fileOwnership($db) {
    try {
        $sql = "SELECT c.clientID
                FROM adscan_crawls AS c
                INNER JOIN adscan_files AS f
                ON c.ID = f.crawl_id
                WHERE f.ID = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$_GET["fileID"]]);
        $belongsTo = $stmt->fetch();
    } catch (PDOException $e) {
        sendStatus(500, 'Error: DB error fetching crawl data');
    }

    if ((int)$belongsTo[0] === (int)$_GET["client"]) {
        return "true";
    } else {
        return "false";
    }
}

/**
 * Gets data for all crawls in th DB, used fo the AbleDocs master page
 * @param $db PDO ADScan database
 * @return false|string JSON for all scans
 */
function getAllcrawls($db) {
    try {
        $sql = 'SELECT ID, starturl, status, ScanType, clientID, crawl_time_start, crawl_time_end, Files_Found, Files_Scanned, Files_Error ,compliant, nonCompliant, untagged, offsiteFiles  FROM adscan_crawls ORDER BY ID DESC;';
        $stmt = $db->prepare($sql);
        $stmt->execute();

        $crawls = array();
        while ($crawl = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($crawls, $crawl);
        }

        return json_encode($crawls);
    } catch (PDOException $e) {
        sendStatus(500, 'Error: DB error fetching crawl data');
    }

}

/**
 * Returns all the files error, warning, and success counts by file id
 * @param $db
 * @return false|string
 */
function getFileErrors($db) {
    // get the file id
    $id = null;
    if( isset($_GET['id'])) {
        if (is_numeric($_GET['id'])) {
            $id = $_GET['id'];
        } else {
            sendStatus(400, 'Error: id number sent was not a number -> ' . $_GET['id']);
        }
    } else {
        sendStatus(400, 'Error: Missing id number');
    }

    try {
        $sql = "SELECT c.Passed AS PassedCount, c.Warnings AS WarnedCount, c.`Errors` AS FailedCount, c.CheckId,l.Name, l.Category
            FROM checks AS c
            INNER JOIN checks_labels AS l
            ON c.CheckID = l.`Key`
            WHERE c.FileID = ?";

        $checks = $db->prepare($sql);
        $checks->execute([$id]);
        $checksArray = array();
        while ($check = $checks->fetch(PDO::FETCH_ASSOC)) {
            array_push($checksArray, $check);
        }

        // return JSON of errors
        return json_encode($checksArray);
    } catch (PDOException $e) {
        sendStatus(500, 'Error: DB error fetching crawl data');
    }
}


function getAvgUA($db) {
    // get the client id
    $id = null;
    if( isset($_GET['id'])) {
        if (is_numeric($_GET['id'])) {
            $id = $_GET['id'];
        } else {
            sendStatus(400, 'Error: id number sent was not a number -> ' . $_GET['id']);
        }
    } else {
        sendStatus(400, 'Error: Missing id number');
    }

    try {
        $sql = "SELECT crawl_id, ROUND(AVG(UA_Index),2) AS 'averageUA'
            FROM adscan_files
            WHERE crawl_id IN (SELECT ID FROM adscan_crawls WHERE clientID = ?)
            GROUP BY crawl_id";

        $avgUaStmt = $db->prepare($sql);
        $avgUaStmt->execute([$id]);
        $avgUaArray = array();
        while ($avgUA = $avgUaStmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($avgUaArray, $avgUA);
        }

        // return JSON of crawls
        return json_encode($avgUaArray);
    } catch (PDOException $e) {
        sendStatus(500, 'Error: DB error fetching crawl data');
    }
}

/**
 * Updates a crawl count given GET parameter named ID
 * @uses updateCountForCrawl()
 */
function updateCounts() {
    // get the database info
    try {
        $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS);
        $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }
    catch(PDOException $e) {
        exit;
    }

    // get the client id
    // get the client id and verify it is an integer
    $id = null;
    if( isset($_GET['id'])) {
        if (is_numeric($_GET['id'])) {
            $id = $_GET['id'];
        } else {
            sendStatus(400, 'Error: id number sent was not a number -> ' . $_GET['id']);
        }
    } else {
        sendStatus(400, 'Error: Missing id number');
    }

    // update the count and watch for errors
    try {
        if (updateCountForCrawl($_GET["id"])) {
            sendStatus(200, "Updated Crawl count");
        } else {
            sendStatus(400, "Failed to update crawl count");
        }
    } catch (TypeError $e) {
        sendStatus(400, "id was not an integer");
    } catch (PDOException $e) {
        sendStatus(500, "DB connection error");
    }
}

/**
 * Gives JSON of scanned PDFs, checks if crawl is pro and returns more info if so
 *
 * Post Params:
 *      crawl : (int) crawl_id from adscan_crawls table
 *      files : (int array) IDs from adscan_files table, if empty will fetch all files from crawl
 *
 * @param $db PDO ADScan db defined in config.php
 * @return false|string JSON of scan data for requested PDFs
 */
function csvDataCrawl($db) {
    ini_set('memory_limit', '-1');

    // decode the sent json
    $reqData = json_decode(file_get_contents("php://input"), true);

    // Get the clientId, if not found in request, kill the action
    $crawlID = null;
    if (isset($reqData['crawl'])) {
        if (is_numeric($reqData['crawl'])) {
            $crawlID = $reqData['crawl'];
        } else {
            sendStatus(400, 'Error: id number sent was not a number -> ' . $_GET['id']);
        }
    } else {
        sendStatus(400, 'Error: Missing id number');
    }

    // find the type of scan to determine the level of detail for the csv
    $scanType = "";
    try {
        $sql = "SELECT ScanType FROM adscan_crawls WHERE ID = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$crawlID]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $scanType = $result[0]['ScanType'];

    } catch (PDOException $e) {
        sendStatus(500, 'Error: DB error fetching crawl data');
    }

    try {
        $sql = "";
        if ($scanType === "Pro") {
            $sql = "SELECT ID, url, found_on, filename, UA_Index, Title, passed_check, warned_check, failed_check, Tagged, Producer, NumPages,
            FileSize, Subject, KeyWords, Author, Creator, ISO, OpenPassword, Lang, Fillable, CreationDate, 
            ModDate, offsite ,

(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'CaptionTag') AS 'Caption structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'CaptionTag') AS 'Caption structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'CaptionTag') AS 'Caption structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'AccessibleEncyptionSettings') AS 'Security settings and document access by assistive - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'AccessibleEncyptionSettings') AS 'Security settings and document access by assistive - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'AccessibleEncyptionSettings') AS 'Security settings and document access by assistive - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'ActualTextHasLanguage') AS 'Natural language of actual text - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'ActualTextHasLanguage') AS 'Natural language of actual text - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'ActualTextHasLanguage') AS 'Natural language of actual text - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'AltTextHasLanguage') AS 'Natural language of alternative text - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'AltTextHasLanguage') AS 'Natural language of alternative text - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'AltTextHasLanguage') AS 'Natural language of alternative text - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'AnnotationHasAltText') AS 'Alternative description for annotations - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'AnnotationHasAltText') AS 'Alternative description for annotations - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'AnnotationHasAltText') AS 'Alternative description for annotations - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'AnnotationsInAnnotTag') AS 'Nesting of annotations in Annot structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'AnnotationsInAnnotTag') AS 'Nesting of annotations in Annot structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'AnnotationsInAnnotTag') AS 'Nesting of annotations in Annot structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'AnnotTag') AS 'Annot structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'AnnotTag') AS 'Annot structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'AnnotTag') AS 'Annot structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'ArtTag') AS 'Art structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'ArtTag') AS 'Art structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'ArtTag') AS 'Art structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'BibEntryTag') AS 'BibEntry structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'BibEntryTag') AS 'BibEntry structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'BibEntryTag') AS 'BibEntry structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'BlockQuoteTag') AS 'BlockQuote structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'BlockQuoteTag') AS 'BlockQuote structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'BlockQuoteTag') AS 'BlockQuote structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'CharactersUnicodeMappable') AS 'Mapping of characters to Unicode - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'CharactersUnicodeMappable') AS 'Mapping of characters to Unicode - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'CharactersUnicodeMappable') AS 'Mapping of characters to Unicode - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'CMapOnlyReferencesToPredefinedCMaps') AS 'References inside CMaps to other CMaps - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'CMapOnlyReferencesToPredefinedCMaps') AS 'References inside CMaps to other CMaps - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'CMapOnlyReferencesToPredefinedCMaps') AS 'References inside CMaps to other CMaps - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'CMapPredefinedOrEmbedded') AS 'Predefined or embedded CMaps - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'CMapPredefinedOrEmbedded') AS 'Predefined or embedded CMaps - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'CMapPredefinedOrEmbedded') AS 'Predefined or embedded CMaps - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'CodeTag') AS 'Code structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'CodeTag') AS 'Code structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'CodeTag') AS 'Code structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'ContentIsTaggedOrArtifacted') AS 'Tagged content and artifacts - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'ContentIsTaggedOrArtifacted') AS 'Tagged content and artifacts - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'ContentIsTaggedOrArtifacted') AS 'Tagged content and artifacts - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'ContentsEntryHasLanguage') AS 'Natural language of Contents entries in annot - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'ContentsEntryHasLanguage') AS 'Natural language of Contents entries in annot - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'ContentsEntryHasLanguage') AS 'Natural language of Contents entries in annot - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DisplayDocTitleIsTrue') AS 'Display of document title in window title - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DisplayDocTitleIsTrue') AS 'Display of document title in window title - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DisplayDocTitleIsTrue') AS 'Display of document title in window title - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DivTag') AS 'Div structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DivTag') AS 'Div structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DivTag') AS 'Div structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DocuementContainsNoReferenceXObjects') AS 'Referenced external objects - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DocuementContainsNoReferenceXObjects') AS 'Referenced external objects - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DocuementContainsNoReferenceXObjects') AS 'Referenced external objects - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DocumentContainsPDFUAIdentification') AS 'PDF/UA identifier - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DocumentContainsPDFUAIdentification') AS 'PDF/UA identifier - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DocumentContainsPDFUAIdentification') AS 'PDF/UA identifier - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DocumentContainsTitle') AS 'Title in XMP metadata - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DocumentContainsTitle') AS 'Title in XMP metadata - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DocumentContainsTitle') AS 'Title in XMP metadata - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DocumentHasNoTrapNetAnnots') AS 'TrapNet annotations - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DocumentHasNoTrapNetAnnots') AS 'TrapNet annotations - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DocumentHasNoTrapNetAnnots') AS 'TrapNet annotations - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DocumentTag') AS 'Document structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DocumentTag') AS 'Document structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'DocumentTag') AS 'Document structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'ExpansionTexHasLanguage') AS 'Natural language of expansion text - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'ExpansionTexHasLanguage') AS 'Natural language of expansion text - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'ExpansionTexHasLanguage') AS 'Natural language of expansion text - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FigureHasAltText') AS 'Alternative text for Figure structure element - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FigureHasAltText') AS 'Alternative text for Figure structure element - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FigureHasAltText') AS 'Alternative text for Figure structure element - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FigureHasBBox') AS 'Bounding boxes - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FigureHasBBox') AS 'Bounding boxes - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FigureHasBBox') AS 'Bounding boxes - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FigureTag') AS 'Figure structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FigureTag') AS 'Figure structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FigureTag') AS 'Figure structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FontsAreEmbedded') AS 'Font embedding - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FontsAreEmbedded') AS 'Font embedding - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FontsAreEmbedded') AS 'Font embedding - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FontType0RegistryIsIdentical') AS 'Registry entries in Type 0 fonts - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FontType0RegistryIsIdentical') AS 'Registry entries in Type 0 fonts - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FontType0RegistryIsIdentical') AS 'Registry entries in Type 0 fonts - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FontType0RegistryIsOrdering') AS 'Ordering entries in Type 0 fonts - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FontType0RegistryIsOrdering') AS 'Ordering entries in Type 0 fonts - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FontType0RegistryIsOrdering') AS 'Ordering entries in Type 0 fonts - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FontType0SupplementIsNotLess') AS 'Supplement entries in Type 0 fonts - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FontType0SupplementIsNotLess') AS 'Supplement entries in Type 0 fonts - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FontType0SupplementIsNotLess') AS 'Supplement entries in Type 0 fonts - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FontType2HasCIDToGIDMap') AS 'CID to GID mapping of Type 2 CID fonts - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FontType2HasCIDToGIDMap') AS 'CID to GID mapping of Type 2 CID fonts - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FontType2HasCIDToGIDMap') AS 'CID to GID mapping of Type 2 CID fonts - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FormFieldHasTUKey') AS 'Alternate names for form fields - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FormFieldHasTUKey') AS 'Alternate names for form fields - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FormFieldHasTUKey') AS 'Alternate names for form fields - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FormTag') AS 'Form structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FormTag') AS 'Form structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FormTag') AS 'Form structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FormulaHasAltText') AS 'Alternative text for Formula structure element - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FormulaHasAltText') AS 'Alternative text for Formula structure element - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FormulaHasAltText') AS 'Alternative text for Formula structure element - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FormulaTag') AS 'Formula structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FormulaTag') AS 'Formula structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FormulaTag') AS 'Formula structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FSDictsContainsFAndUF') AS 'F and UF entries in file specifications - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FSDictsContainsFAndUF') AS 'F and UF entries in file specifications - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FSDictsContainsFAndUF') AS 'F and UF entries in file specifications - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'H1Tag') AS 'H1 structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'H1Tag') AS 'H1 structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'H1Tag') AS 'H1 structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'H2Tag') AS 'H2 structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'H2Tag') AS 'H2 structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'H2Tag') AS 'H2 structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'H3Tag') AS 'H3 structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'H3Tag') AS 'H3 structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'H3Tag') AS 'H3 structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'H4Tag') AS 'H4 structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'H4Tag') AS 'H4 structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'H4Tag') AS 'H4 structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'H5Tag') AS 'H5 structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'H5Tag') AS 'H5 structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'H5Tag') AS 'H5 structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'H6Tag') AS 'H6 structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'H6Tag') AS 'H6 structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'H6Tag') AS 'H6 structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'HTag') AS 'H structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'HTag') AS 'H structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'HTag') AS 'H structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'IndexTag') AS 'Index structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'IndexTag') AS 'Index structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'IndexTag') AS 'Index structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'LblTag') AS 'Lbl structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'LblTag') AS 'Lbl structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'LblTag') AS 'Lbl structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'LBodyTag') AS 'LBody structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'LBodyTag') AS 'LBody structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'LBodyTag') AS 'LBody structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'LinkAnnotationsInLinkTag') AS 'Nesting of Link annotations inside Link - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'LinkAnnotationsInLinkTag') AS 'Nesting of Link annotations inside Link - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'LinkAnnotationsInLinkTag') AS 'Nesting of Link annotations inside Link - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'LinkTag') AS 'Link structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'LinkTag') AS 'Link structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'LinkTag') AS 'Link structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'LITag') AS 'LI structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'LITag') AS 'LI structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'LITag') AS 'LI structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'LTag') AS 'L structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'LTag') AS 'L structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'LTag') AS 'L structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'MarkedIsTrue') AS 'Mark for tagged documents - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'MarkedIsTrue') AS 'Mark for tagged documents - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'MarkedIsTrue') AS 'Mark for tagged documents - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'MetadataInCatalogExists') AS 'XMP Metadata - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'MetadataInCatalogExists') AS 'XMP Metadata - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'MetadataInCatalogExists') AS 'XMP Metadata - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoArtifactInTaggedContent') AS 'Artifacts inside tagged content - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoArtifactInTaggedContent') AS 'Artifacts inside tagged content - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoArtifactInTaggedContent') AS 'Artifacts inside tagged content - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoCircularMappings') AS 'Circular role mapping - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoCircularMappings') AS 'Circular role mapping - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoCircularMappings') AS 'Circular role mapping - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NonStandardStructureTypeIsRemapped') AS 'Role mapping of non-standard structure types - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NonStandardStructureTypeIsRemapped') AS 'Role mapping of non-standard structure types - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NonStandardStructureTypeIsRemapped') AS 'Role mapping of non-standard structure types - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NonSymbolicTTFontContainsEncoding') AS 'Encoding entry in non-symbolic TrueType font - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NonSymbolicTTFontContainsEncoding') AS 'Encoding entry in non-symbolic TrueType font - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NonSymbolicTTFontContainsEncoding') AS 'Encoding entry in non-symbolic TrueType font - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NonSymbolicTTFontContainsOnlyAdobeGlyphNames') AS 'Glyph names in non-symbolic TrueType font - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NonSymbolicTTFontContainsOnlyAdobeGlyphNames') AS 'Glyph names in non-symbolic TrueType font - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NonSymbolicTTFontContainsOnlyAdobeGlyphNames') AS 'Glyph names in non-symbolic TrueType font - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoteTag') AS 'Note structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoteTag') AS 'Note structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoteTag') AS 'Note structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoteTagHasID') AS 'IDs of Note structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoteTagHasID') AS 'IDs of Note structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoteTagHasID') AS 'IDs of Note structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoteTagHasUniqueID') AS 'Unique ID entries in Note structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoteTagHasUniqueID') AS 'Unique ID entries in Note structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoteTagHasUniqueID') AS 'Unique ID entries in Note structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'OutlineItemHasLanguage') AS 'Natural language of outlines can be determined - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'OutlineItemHasLanguage') AS 'Natural language of outlines can be determined - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'OutlineItemHasLanguage') AS 'Natural language of outlines can be determined - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PagesWithAnnotsHaveTabOrderS') AS 'Tab order for pages with annotations - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PagesWithAnnotsHaveTabOrderS') AS 'Tab order for pages with annotations - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PagesWithAnnotsHaveTabOrderS') AS 'Tab order for pages with annotations - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PartTag') AS 'Part structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PartTag') AS 'Part structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PartTag') AS 'Part structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PDFParsable') AS 'PDF syntax - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PDFParsable') AS 'PDF syntax - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PDFParsable') AS 'PDF syntax - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PrinterMarkAnnotationaNotTagged') AS 'PrinterMark annotations - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PrinterMarkAnnotationaNotTagged') AS 'PrinterMark annotations - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PrinterMarkAnnotationaNotTagged') AS 'PrinterMark annotations - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PTag') AS 'P structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PTag') AS 'P structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PTag') AS 'P structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'QuoteTag') AS 'Quote structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'QuoteTag') AS 'Quote structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'QuoteTag') AS 'Quote structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'RBTag') AS 'RB structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'RBTag') AS 'RB structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'RBTag') AS 'RB structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'ReferenceTag') AS 'Reference structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'ReferenceTag') AS 'Reference structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'ReferenceTag') AS 'Reference structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'RPTag') AS 'RP structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'RPTag') AS 'RP structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'RPTag') AS 'RP structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'RTTag') AS 'RT structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'RTTag') AS 'RT structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'RTTag') AS 'RT structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'RubyTag') AS 'Ruby structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'RubyTag') AS 'Ruby structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'RubyTag') AS 'Ruby structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'SectTag') AS 'Sect structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'SectTag') AS 'Sect structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'SectTag') AS 'Sect structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'SpanTag') AS 'Span structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'SpanTag') AS 'Span structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'SpanTag') AS 'Span structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'StandardStructureTypeIsNotRemapped') AS 'Role mapping for standard structure types - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'StandardStructureTypeIsNotRemapped') AS 'Role mapping for standard structure types - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'StandardStructureTypeIsNotRemapped') AS 'Role mapping for standard structure types - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'StructuralParentTree') AS 'Structural parent tree - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'StructuralParentTree') AS 'Structural parent tree - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'StructuralParentTree') AS 'Structural parent tree - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'StructureHasCorruptElements') AS 'Logical structure syntax - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'StructureHasCorruptElements') AS 'Logical structure syntax - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'StructureHasCorruptElements') AS 'Logical structure syntax - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'SuspectsIsFalse') AS 'Tag suspects - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'SuspectsIsFalse') AS 'Tag suspects - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'SuspectsIsFalse') AS 'Tag suspects - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TablesAreRegular') AS 'Table regularity - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TablesAreRegular') AS 'Table regularity - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TablesAreRegular') AS 'Table regularity - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TableTag') AS 'Table structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TableTag') AS 'Table structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TableTag') AS 'Table structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TBodyTag') AS 'TBody structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TBodyTag') AS 'TBody structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TBodyTag') AS 'TBody structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TDTag') AS 'TD structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TDTag') AS 'TD structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TDTag') AS 'TD structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TextContentHasLanguage') AS 'Natural language of text objects - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TextContentHasLanguage') AS 'Natural language of text objects - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TextContentHasLanguage') AS 'Natural language of text objects - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TFootTag') AS 'TFoot structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TFootTag') AS 'TFoot structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TFootTag') AS 'TFoot structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'THeadTag') AS 'THead structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'THeadTag') AS 'THead structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'THeadTag') AS 'THead structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'THTag') AS 'TH structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'THTag') AS 'TH structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'THTag') AS 'TH structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TOCITag') AS 'TOCI structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TOCITag') AS 'TOCI structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TOCITag') AS 'TOCI structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TOCTag') AS 'TOC structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TOCTag') AS 'TOC structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TOCTag') AS 'TOC structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TRTag') AS 'TR structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TRTag') AS 'TR structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TRTag') AS 'TR structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TUEntryHasLanguage') AS 'Natural language of alternate names of form fields - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TUEntryHasLanguage') AS 'Natural language of alternate names of form fields - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'TUEntryHasLanguage') AS 'Natural language of alternate names of form fields - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'WarichuTag') AS 'Warichu structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'WarichuTag') AS 'Warichu structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'WarichuTag') AS 'Warichu structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'WidgetAnnotationsInFormTag') AS 'Nesting of Widget annotations inside a Form - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'WidgetAnnotationsInFormTag') AS 'Nesting of Widget annotations inside a Form - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'WidgetAnnotationsInFormTag') AS 'Nesting of Widget annotations inside a Form - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'WModeInDictAndStreamIdentical') AS 'WMode entry in CMap definition and CMap data - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'WModeInDictAndStreamIdentical') AS 'WMode entry in CMap definition and CMap data - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'WModeInDictAndStreamIdentical') AS 'WMode entry in CMap definition and CMap data - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'WPTag') AS 'WP structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'WPTag') AS 'WP structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'WPTag') AS 'WP structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'WTTag') AS 'WT structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'WTTag') AS 'WT structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'WTTag') AS 'WT structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'XFAIsNotDynamic') AS 'Dynamic XFA form - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'XFAIsNotDynamic') AS 'Dynamic XFA form - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'XFAIsNotDynamic') AS 'Dynamic XFA form - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'AltTextHasLanguage') AS 'Natural language of alternative text cannot be detected - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'AltTextHasLanguage') AS 'Natural language of alternative text cannot be detected - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'AltTextHasLanguage') AS 'Natural language of alternative text cannot be detected - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FirstHeadingIsH1') AS 'First heading level - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FirstHeadingIsH1') AS 'First heading level - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'FirstHeadingIsH1') AS 'First heading level - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'HeadingStructureTypesNotMixed') AS 'Use of either H or Hn structure elements - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'HeadingStructureTypesNotMixed') AS 'Use of either H or Hn structure elements - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'HeadingStructureTypesNotMixed') AS 'Use of either H or Hn structure elements - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoHeadingsSkipped') AS 'Nesting of heading levels - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoHeadingsSkipped') AS 'Nesting of heading levels - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoHeadingsSkipped') AS 'Nesting of heading levels - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'OnlyOneHPerNode') AS 'H structure elements within a structure node - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'OnlyOneHPerNode') AS 'H structure elements within a structure node - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'OnlyOneHPerNode') AS 'H structure elements within a structure node - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PDFUAHeading') AS 'Headings - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PDFUAHeading') AS 'Headings - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PDFUAHeading') AS 'Headings - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PDFUANotes') AS 'Notes - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PDFUANotes') AS 'Notes - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PDFUANotes') AS 'Notes - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PDFUAOptionalContent') AS 'Optional Content - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PDFUAOptionalContent') AS 'Optional Content - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'PDFUAOptionalContent') AS 'Optional Content - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'THHasAssociatedCells') AS 'Table header cell assignments - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'THHasAssociatedCells') AS 'Table header cell assignments - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'THHasAssociatedCells') AS 'Table header cell assignments - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'SymbolicTTFontContainsNoEncoding') AS 'Encoding of symbolic TrueType fonts - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'SymbolicTTFontContainsNoEncoding') AS 'Encoding of symbolic TrueType fonts - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'SymbolicTTFontContainsNoEncoding') AS 'Encoding of symbolic TrueType fonts - Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'OCConfDictHasNoAS') AS 'entry in OCCDs (optional content configuration dictionaries)- Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'OCConfDictHasNoAS') AS 'entry in OCCDs (optional content configuration dictionaries)- Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'OCConfDictHasNoAS') AS 'entry in OCCDs (optional content configuration dictionaries)- Failed',
(SELECT passed FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoTaggedContentInArtifact') AS 'Tagged content inside artifacts - Passed',
(SELECT c.`Warnings` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoTaggedContentInArtifact') AS 'Tagged content inside artifacts - Warned',
(SELECT c.`Errors` FROM checks AS c WHERE c.FileID = f.ID AND c.CheckID = 'NoTaggedContentInArtifact') AS 'Tagged content inside artifacts - Failed'

FROM adscan_files AS f
WHERE f.crawl_id = ?";
        } else {
            $sql = "SELECT ID, url, filename, UA_Index, Title, passed_check, warned_check, failed_check, Tagged, Producer, NumPages,
            FileSize, Subject, KeyWords, Author, Creator, ISO, OpenPassword, Lang, Fillable, CreationDate, 
            ModDate, offsite
            FROM adscan_files AS f
            WHERE f.crawl_id = ?";
        }

        // check to see if specific files are being request or none
        $selectedFiles = count($reqData['files']) > 0;
        if ($selectedFiles) {
            $placeholders = str_repeat('?, ', count($reqData['files']) - 1) . '?';
            $sql .= " AND f.ID IN ($placeholders)";
        }

        $stmt = $db->prepare($sql);

        if ($selectedFiles) {  //  add in the selected file IDs to the prepared statement
            $params = [$crawlID];
            foreach ($reqData['files'] as $ID) {
                $params[] = $ID;
            }
            $stmt->execute($params);
        } else {
            $stmt->execute([$crawlID]);
        }

        // return the results as JSON
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $return = json_encode($results);
        header('Content-type: text/json');
        header('Content-Length: '.strlen($return)."\r\n");
        return $return;

    } catch (PDOException $e) {
        sendStatus(500, 'Error: DB error fetching crawl data');
    }
}

/**
 * Returns a request with an error
 * @param $statusCode int status code for error
 * @param $msg string error message
 */
function sendStatus(int $statusCode, string $msg) {
    http_response_code($statusCode);
    echo $msg;
    exit();
}