<?php
// Get the DB config info
require("../config.php");

if (!isset($_POST["password"])) {
    http_response_code(400);
    echo false;
    exit();
}
if (!isset($_POST["user"])) {
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

// get the hashed password
try {
    $pwd = password_hash($_POST["password"], PASSWORD_DEFAULT);
    $userID = (int)$_POST["user"];

    $sql = "UPDATE adscan_user SET Pwd = :password WHERE Id = :user";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':password', $pwd);
    $stmt->bindParam(':user', $userID);
    $stmt->execute();
    http_response_code(200);
    echo true;
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    echo "Update failed";
    exit;
}
