<?php
// Get the DB config info
require("../config.php");

/**
 * Sends a http response
 * @param $code 'http status code'
 * @param $msg 'string message to to be sent'
 */
function sendResponse($code, $msg) {
    http_response_code($code);
    echo $msg;
    exit;
}

try {
    $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS);
    $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
}
catch(PDOException $e) {
    sendResponse(500, $e->getMessage());
}

// make sure that accounts have been sent
if (!isset($_POST["accounts"])) {
    sendResponse(400, "Missing accounts parameter");
}

// make sure the json sent is valid
if (json_decode($_POST["accounts"])) {
    sendResponse(400, "invalid JSON was sent");
}

// get the new accounts and put into an array
$accounts = json_decode($_POST["accounts"], true);

// loop through the accounts and add them to the DB
foreach ($accounts as $account) {
    // gather all the data for the new account
    $newAccount = [
      'email' => $account->email,
      'Pwd' => password_hash("AbleDocs1", PASSWORD_DEFAULT),
      'CompanyID' => 1,
      'CompanyName' => "AbleDocs",
      'Created_At' => date("Y-m-d H:i:s"),
      'First_Name' => $account->fname,
      'Last_Name' => $accounts->lname,
      'Lang' => "en",
      'Role' => "train",  // short for training because max size is 8 characters
      'Expiry_Date' => $account->expiryDate
    ];

    try {
        // add the new account to the database
        $insertSQL = "INSERT INTO adscan_users INSERT INTO adscan_user (Email, Pwd, CompanyID, CompanyName, Created_At, First_Name, Last_Name, Lang, Role, Expiry_Date)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $db->prepare($insertSQL);
        $insertStmt->execute($newAccount);
    } catch (PDOException $e) {
        sendResponse(500, $e->getMessage());
    }
}

// all good, send status 200
sendResponse(200, "OK");
