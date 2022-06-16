<?php
ini_set('memory_limit', '-1');

// Get the DB config info
require("config.php");

// get the files id from the body
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE); //convert JSON into array
$idArray = $input["ids"];

// get the checks from the files
$sqlChecks = "SELECT l.Name, c.Passed AS PassedCount, c.`Warnings` AS WarnedCount, c.`Errors` AS FailedCount, c.FileID AS DocumentId 
                FROM checks AS c 
                LEFT JOIN checks_labels AS l
                ON l.`Key` = c.CheckId
                WHERE c.FileID in (SELECT ID FROM adscan_files WHERE ID in  (". implode(", ",$idArray) ."))";
$checksReq = $db->prepare($sqlChecks);
$checksReq->execute([]);
$checks = array();
while ($check = $checksReq->fetch(PDO::FETCH_ASSOC)) {
    array_push($checks, $check);
}
// echo json_encode($checks);

$fileStats = new stdClass();
$fileStats->checks = $checks;

echo json_encode($fileStats);



