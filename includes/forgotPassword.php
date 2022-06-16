<?php
// Get the DB config info
require("../config.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST["passwordForgotSubmit"])) {

    // get the language, english default
    $lang = $_POST["lang"] ?? 'en';

    /**
     * returns to the forgot password page with an error
     */
    function returnError($error) {
        $lang = $_POST["lang"] ?? 'en';
        http_response_code(400);
        header("Location: ../login/forgot.php?lang=".$lang."&error=".$error);
        die();
    }

    // check there is an email
    if (!isset($_POST["forgot-email"])) {
        returnError('true');
    }
    // check that the email isn't empty
    if ($_POST["forgot-email"] === "") {
        returnError('true');
    }
    // check that it is a valid email
    if (!filter_var($_POST["forgot-email"], FILTER_VALIDATE_EMAIL)) {
        returnError('true');
    }

    // create tokens for auth
    try {
        $selector = bin2hex(random_bytes(8));
        $token = random_bytes(32);
    } catch (Exception $e) {
        http_response_code(500); // throw error when cannot connect
        echo $e->getMessage();
        exit();
    }

    // url for email link
    $url = "https://".$_SERVER['SERVER_NAME']."/login/newPassword.php?lang=".$lang."&selector=" . $selector . "&validator=" . bin2hex($token);
    // expiry time for password reset, set to 1 hour later
    $expires = date("U") + 1800;

    // make the database connection
    $db = null;
    try {
        $db = makeDbConnection();
    } catch (Exception $e) {
        http_response_code(500); // throw error when cannot connect
        echo $e->getMessage();
        exit();
    }

    // check if email is a registered user
    try {
        $emailCheckSql = "SELECT * FROM adscan_user WHERE email = ?";
        $emailCheckStmt = $db->prepare($emailCheckSql);
        $emailCheckStmt->execute([$_POST["forgot-email"]]);
        $row = $emailCheckStmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            returnError('noAccount');
        }
    } catch (PDOException $e) {
        http_response_code(500); // throw error when cannot check
        echo $e->getMessage();
        exit();
    }

    try {
        // delete any existing tokens from that email
        if (!deleteTokensFromEmail($_POST["forgot-email"], $db)) {
            // send error when failed
            http_response_code(500);
            echo "An error sending request";
            exit();
        }

        // insert the token in the db
        if(!insertToken($_POST["forgot-email"], $selector, $token, $expires, $db)) {
            // send error when failed
            http_response_code(500);
            echo "An error sending request";
            exit();
        }
    } catch (PDOException $e) {
        http_response_code(500); // throw error when cannot make changes
        echo $e->getMessage();
        exit();
    }

    // Get everything needed to send an email
    $to = $_POST["forgot-email"];
    $subject = "";
    switch ($_POST["lang"]) {
        case 'fr':
            $subject = "ADscan - Réinitialisez votre mot de passe";
            break;
        case 'da':
            $subject = "ADscan - Nulstil din adgangskode";
            break;
        case 'de':
            $subject = "ADscan - Setze dein Passwort zurück";
            break;
        case 'nl':
            $subject = "ADscan - Stel je wachtwoord opnieuw in";
            break;
        case 'it':
            $subject = "ADscan - Reimposta la tua password";
            break;
        case 'en':
        default:
            $subject = "ADscan - Reset your password";
            break;
    }
    $message = "<a href='".$url."'>".$url."</a>";

    // load in PHPMailer
    require 'PHPMailer/src/Exception.php';
    require 'PHPMailer/src/PHPMailer.php';
    require 'PHPMailer/src/SMTP.php';

    // build the email
    $mail = new PHPMailer;
    $mail->IsSMTP(); // send via SMTP
    $mail->XMailer = "AbleDocs Online - http://ado.abledocs.com";
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_USER_PASSWORD;
    $mail->Host = SMTP_HOST; // SMTP servers
    $mail->Port = 587;          // set the SMTP port
    $mail->SMTPAuth = true;     // turn on SMTP authentication
    $mail->CharSet = "UTF-8";

    $mail->From = 'info@abledocs.com';
    $mail->FromName = 'ADscan';
    $mail->addAddress($to);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $message;

    if(!$mail->send()) {
        http_response_code(500);
        echo 'Message could not be sent.';
        echo 'Mailer Error: ' . $mail->ErrorInfo;
        exit();
    } else {  // all good and email has been sent
        header("location: ../login/conformation.php?lang=" . $_POST["lang"] . "&success=sent");
    }

} else {
    // if not from submit button, send back to login page
    header("location: ../login/login.php");
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

/**
 * Deletes all the tokens under an email
 * @param string $email - email address being checked against
 * @param PDO $db database connection
 * @return bool - true if successful
 * @throws PDOException
 */
function deleteTokensFromEmail(string $email, $db) {
    // delete any token this email already has
    $deleteTokenSQL = "DELETE FROM adscan_pwdreset WHERE email = ?;";
    $deleteTokenSTMT = $db->prepare($deleteTokenSQL);
    return $deleteTokenSTMT->execute([$email]);
}

/**
 * Adds a new token into the DB
 * @param string $email - email address for request
 * @param string $selector - selector token (8 bytes)
 * @param string $token - reset token (32 bytes)
 * @param $expiry - expiry time of reset token (date('U') + whatever)
 * @param PDO $db - PDO database connection
 * @return bool - true if successful
 * @throws PDOException
 */
function insertToken(string $email, string $selector, string $token, $expiry, PDO $db) {
    // make a hash for the token
    $hashedToken = password_hash($token, PASSWORD_DEFAULT);
    // insert into DB
    $sql = "INSERT INTO adscan_pwdreset (email, resetSelector, resetToken, expiry) VALUE (?, ?, ?, ?);";
    $stmt = $db->prepare($sql);
    return $stmt->execute([$email, $selector, $hashedToken, $expiry]);
}