<?php
// Get the DB config info
require("../config.php");

// check that all required post params are set
if(!isset($_POST["email"], $_POST["firstName"], $_POST["lastName"], $_POST["clientId"], $_POST["company"], $_POST["lang"])){
    http_response_code(400);
    echo "missing parameters";
    exit();
}

// check for valid email
if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo "invalid email";
    exit();
}

// verify clientID is an int
if (!filter_var($_POST["clientId"], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    echo "Error: clientId must be an int";
    exit();
}

// all good! add the new user to adscan_user table

try {
    $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS);
    $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
}
catch(PDOException $e) {
    http_response_code(500);
    echo $e->getMessage();
    exit;
}

// check if email is already used
try {
    $sql = "SELECT Email FROM adscan_user WHERE Email = ?;";
    $stmt = $db->prepare($sql);
    $stmt->execute([$_POST["email"]]);
    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo "Email is already used";
        exit();
    }
}catch (PDOException $e) {
    http_response_code(500);
    echo $e->getMessage();
    exit();
}

// make a temp password for the new user
$pwd = uniqid('', false);
$hashedPwd = password_hash($pwd, PASSWORD_DEFAULT);

// get the created at time
$create = date("Y-m-d H:i:s");

// add the new user
try {
    $sql = "INSERT INTO adscan_user (Email, Pwd, CompanyID, CompanyName, Created_At, First_Name, Last_Name, Lang)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$_POST["email"], $hashedPwd, $_POST["clientId"],
        $_POST["company"], $create, $_POST["firstName"], $_POST["lastName"], $_POST["lang"]]);

    // Added user, return success
    http_response_code(201);
    echo $pwd;
    exit();
} catch (PDOException $e) {
    http_response_code(500);
    echo $e->getMessage();
    exit();
}
