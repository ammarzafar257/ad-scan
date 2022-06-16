<?php
// Get the DB config info
require("../config.php");

if (!isset($_GET["ScanType"])) {
    http_response_code(400);
    echo false;
    exit();
}
if (!isset($_GET["ID"])) {
    http_response_code(400);
    echo false;
    exit();
}

try {
    $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS);
    $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    http_response_code(500);
    echo "Cant connect to DB";
    exit;
}

try {
    $scantype = "";
    if ($_GET["ScanType"] === "Pro") {
        $scantype = "Lite";
    } else {
        $scantype = "Pro";
    }

    $sql = "UPDATE adscan_crawls SET ScanType = ? WHERE ID = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$scantype, $_GET["ID"]]);

    $count = $stmt->rowCount();
    if($count =='0'){
        http_response_code(500);
        echo "Update Failed, no data was changed. scantype = " . $scantype . " | ID = " .  $_GET["ID"];
        exit;
    } else {
        http_response_code(200);
        echo "Update successful";
        exit;
    }

} catch(PDOException $e) {
    http_response_code(500);
    echo "Error updating scan type";
    exit;
}
