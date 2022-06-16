<?php
// Get the DB config info
require("../config.php");

$lang = $_POST["lang"] ?? 'en';

if (isset($_POST["reset-pass-submit"])) {
    // get the needed information
    $selector = $_POST["selector"];
    $validator = $_POST["validator"];
    $currentDate = date("U");
    $db = makeDbConnection();

    // get the reset request token and validate
    $sql = "SELECT * FROM adscan_pwdreset WHERE resetSelector = ? AND expiry >= ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$selector, $currentDate]);

    if ($stmt->rowCount() > 0) {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $validatorToken = hex2bin($validator);
        $tokenCheck = password_verify($validatorToken, $result["resetToken"]);

        if (!$tokenCheck) {
            // return the login page if incorrect token
            header("location: ../login/forgot.php?lang=" . $lang . "&error=passReset");
        } else {
            // change the password
            $pwd = password_hash($_POST["pwd"], PASSWORD_DEFAULT);
            $sql = "UPDATE adscan_user SET Pwd = :password WHERE Email = :email";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':password', $pwd);
            $stmt->bindParam(':email', $result["email"]);
            $stmt->execute();
            // return to login
            header("location: ../login/login.php?lang=".$lang);
        }

    } else {
        // return th login page if no rows
        header("location: ../login/forgot.php?lang=" . $lang . "&error=passReset");
    }
} else {
    // if not from submit button, send back to login page
    header("location: ../login/forgot.php?lang=" . $lang . "&error=passReset");
}

/**
 * Creates a connection to AD scan db
 * @return PDO PDO connection
 * @throws PDOException when cannot connect to db
 */
function makeDbConnection() {
    try {
        $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8', DB_USER, DB_PASS);
        $db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $db;
    }
    catch(PDOException $e) {
        throw $e;
    }
}