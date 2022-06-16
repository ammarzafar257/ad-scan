<?php

/**
 * @api Handles a sign up request for ad scan, POST request
 * last modified: August 6th, 2020
 *
 * Must include following post params:
 * email - email address
 * pwd - password
 * repeatPwd - password repeated to ensure correct value
 * companyId - id fot the users company from ado, example: 167
 * companyName - name of organization the client is from
 * firstName - first name of user
 * lastName - last name of user
 * lang - preferred language code
 *
 * Optional Params - added in update statements if included
 * phoneNum - phone number to reach client
 * address - mailing address of the client
 * city - City the client is from
 * province - Province/State client is from
 * country - Country the client is from
 * ADO_Contact_ID - Contact ID in ADO
 */

// Get the DB config info
require("../config.php");

if (!isset($_POST["email"])) {  // check for if page was accessed via browser
    // send to login page when accessed incorrectly

    http_response_code(400);
    echo "Error: No email set";
    exit;

    /*header("Location: ../login/login.php");
    exit();*/
} else {
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

    // check for empty values in any required fields
    if (empty($_POST["email"]) || empty($_POST["pwd"]) || empty($_POST["companyId"]) || empty($_POST["companyName"])
    || empty($_POST["firstName"]) || empty($_POST["lastName"]) || empty($_POST["lang"])) {
        http_response_code(400);
        echo "Error: Missing parameters";
        exit();
    }

    // get all the required parameters for the insert
    $email = $_POST["email"];
    $pwd = password_hash($_POST["pwd"], PASSWORD_DEFAULT);  // give hashed password
    $companyId = $_POST["companyId"];
    $companyName = $_POST["companyName"];
    $createdAt = date("Y-m-d H:i:s");  // gives timestamp of current time
    $firstName = $_POST["firstName"];
    $lastName = $_POST["lastName"];
    $lang = $_POST["lang"];

    // Check that password matchs the repeated password
    if ($_POST["pwd"] !== $_POST["repeatPwd"]) {
        http_response_code(400);
        echo "Error: Passwords do not match";
        exit();
    }

    // check for valid email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo "Error: Invalid email";
        exit();
    }

    // check company id is an int
    if (!filter_var($companyId, FILTER_VALIDATE_INT)) {
        http_response_code(400);
        echo "Error: Company ID must be an int";
        exit();
    }

    // check if email is already used
    try {
        $sql = "SELECT Email FROM adscan_user WHERE Email = ?;";
        $stmt = $db->prepare($sql);
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            http_response_code(400);
            echo "Error: Email is already used";
            exit();
        }
    }catch (PDOException $e) {
        http_response_code(500);
        echo $e->getMessage();
        exit();
    }

    // if Phone number is included, check that it is valid
    $cleanPhoneNum = "";
    if (isset($_POST["phoneNum"])) {
        $cleanPhoneNum = preg_replace("/[^0-9]/", '', $_POST["phoneNum"]);  // remove anything other then numbers
        if (strlen($cleanPhoneNum) === 11) {                                 // if 11 numbers, remove the beginning 1
            $cleanPhoneNum = preg_replace("/^1/", '',$cleanPhoneNum);
        }
        if (strlen($cleanPhoneNum) !== 10) {                                // if not 10 digits, then its invalid
            http_response_code(400);
            echo "Error: Phone number invalid";
            exit();
        }
    }

    // Insert the new user into the DB
    try {
        $insertSql = "INSERT INTO adscan_user (Email, Pwd, CompanyID, CompanyName, Created_At, First_Name, Last_Name, Lang)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $db->prepare($insertSql);
        $insertStmt->execute([$email, $pwd, $companyId, $companyName, $createdAt, $firstName, $lastName, $lang]);

        /* Add optional fields if they are given */

        // if there is a phone number, add it
        if (isset($_POST["phoneNum"])) {
            $updatePhoneNumSQL = "UPDATE adscan_user SET Phone_Num = ? WHERE Email = ?";
            $phoneUpdateStmt = $db->prepare($updatePhoneNumSQL);
            $phoneUpdateStmt->execute([$cleanPhoneNum, $email]);
        }

        // if there is an address, add it
        if (isset($_POST["address"])) {
            $updateAddressSQL = "UPDATE adscan_user SET Company_Address = ? WHERE Email = ?";
            $updateAddressStmt = $db->prepare($updateAddressSQL);
            $updateAddressStmt->execute([$_POST["address"], $email]);
        }

        // if there is an postal code, add it
        if (isset($_POST["postal"])) {
            $updatePostalSQL = "UPDATE adscan_user SET Postal_Code = ? WHERE Email = ?";
            $updatePostalStmt = $db->prepare($updateAddressSQL);
            $updatePostalStmt->execute([$_POST["postal"], $email]);
        }

        // if there is an city, add it
        if (isset($_POST["city"])) {
            $updateCitySQL = "UPDATE adscan_user SET City = ? WHERE Email = ?";
            $updateCityStmt = $db->prepare($updateCitySQL);
            $updateCityStmt->execute([$_POST["city"], $email]);
        }

        // if there is an province, add it
        if (isset($_POST["province"])) {
            $updateProvinceSQL = "UPDATE adscan_user SET Province = ? WHERE Email = ?";
            $updateProvinceStmt = $db->prepare($updateProvinceSQL);
            $updateProvinceStmt->execute([$_POST["province"], $email]);
        }

        // if there is an province, add it
        if (isset($_POST["country"])) {
            $updateCountrySQL = "UPDATE adscan_user SET Country = ? WHERE Email = ?";
            $updateCountryStmt = $db->prepare($updateCountrySQL);
            $updateCountryStmt->execute([$_POST["country"], $email]);
        }

        // if there is an contactID, add it
        if (isset($_POST["contactId"])) {
            $updateContactSQL = "UPDATE adscan_user SET ADO_Contact_ID = ? WHERE Email = ?";
            $updateContactStmt = $db->prepare($updateContactSQL);
            $updateContactStmt->execute([$_POST["contactId"], $email]);
        }

        // return success message
        http_response_code(200);
        echo "User Added";

    } catch (PDOException $e) {
        http_response_code(500);
        echo $e->getMessage();
        exit();
    }
}