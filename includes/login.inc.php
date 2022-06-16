<?php

if (isset($_POST['login-submit'])) {
    // get the database info
    require '../config.php';

    // get the language, english default
    $lang = $_POST["lang"] ?? 'en';

    // get the email and password used to sign in
    $email = $_POST['email'];
    $password = $_POST['password'];

    // send to login page when either feild is empty
    if (empty($email) || empty($password)) {
        header("Location: ../login/login.php?lang=".$lang."&error=emptyfields");
        exit();
    }

    // get the hashed version of the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // get the user data from the db
        $sql = "SELECT * FROM adscan_user WHERE Email = ?;";
        $stmt = $db->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        // check for results coming in
        if (is_array($user)) {
            if (password_verify($password, $user['Pwd']) === false) {
                // incorrect password
                header("Location: ../login/login.php?lang=".$lang."&error=incorrect");
                exit();
            }
            if (password_verify($password, $user['Pwd']) === true) {
                // All good, Start the session and get the relvant info
                session_start();
                $_SESSION['userID'] = $user["Id"];
                $_SESSION["userCompany"] = $user["CompanyName"];
                $_SESSION["userCompanyId"] = $user["CompanyID"];
                $_SESSION["userFirst"] = $user["First_Name"];
                $_SESSION["userLast"] = $user["Last_Name"];
                $_SESSION["email"] = $user["Email"];
                $_SESSION["adoID"] = $user["ADO_Contact_ID"];
                $_SESSION["lang"] = $user["Lang"];
                $_SESSION["isOwner"] = $user["Role"] === "Owner";
                // make a check for new scans and calc there file counts
                checkForNewScans($db);

                // if Abledocs employee go to admin page
                if ($_SESSION["userCompany"] === "AbleDocs" && $_SESSION["userCompanyId"] === 1) {
                    header("Location: ../master.php");
                } else {
                    // redirect to the main dashboard otherwise
                    header("Location: ../index.php");
                }
            } else {
                // error in verify function
                header("Location: ../login/login.php?lang=".$lang."&error=incorrect");
                exit();
            }
        } else {
            // no email was found
            header("Location: ../login/login.php?lang=".$lang."&error=incorrect");
            exit();
        }
    } catch(PDOException $e) {
        // if error return to login page with error msg
        header("Location: ../login/login.php?lang=".$lang."&error=external");
        exit();
    }
} else {
    // send to login page when accessed incorrectly
    header("Location: ../login/login.php");
    exit();
}