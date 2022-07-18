<?php
session_start();
?>

<!doctype html>

<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="shortcut icon" type="image/x-icon" href="../images/favicon.ico">
    <link rel="stylesheet" href="../css/login.css" type="text/css" />
    <link rel="stylesheet" href="../css/bootstrap.min.css" type="text/css" />
    <script src="../js/jquery.min.js"></script>
    <script src="../js/lang.js"></script>
    <title>ADScan - Forgot Password Confirmation </title>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(backToLogin, 60000);
            checkParams();
        });

        function backToLogin() {
            const params = new URLSearchParams(window.location.search)
            let currentLanguage = params.get('lang') ? params.get('lang') : "en";
            window.location.href = `login.php?lang=${currentLanguage}`
        }

        function checkParams() {
            const params = new URLSearchParams(window.location.search)
            let sent = params.get('success');
            let currentLanguage = params.get('lang') ? params.get('lang') : "en";
            if (!sent) {
                window.location.href = `login.php?lang=${currentLanguage}`
            }
        }
    </script>
</head>

<body>
<main class="container">
    <div class="row inner-container">

        <div class="col-lg-6" style="padding-left: 3% !important;padding-right: 3% !important;" id="new-login-form-container">
            <div style="display: flex; justify-content: space-between; align-items: center">
                <div>
                    <img width="180px" style="margin-bottom:15%" src="https://anima-uploads.s3.amazonaws.com/projects/6284d6017c7b361162940774/releases/628b90e723270e8c56649893/img/group-999@1x.png" alt="AD scan logo" />
                </div>
            </div>

            <div style="margin-top:10px">
                <p data-key="forgotPassConfirmation" class="confirmation-text translate roboto-normal-black-16px">
                    Thank you. If you have an ADScan account for this email address, you'll receive an email
                    shortly with instructions to reset your ADScan account password. Please be sure to check your spam folder in your email if you don't receive this soon. If you need to contact our support team for help,
                    please email us at support@abledocs.com or contact your account representative.
                </p>
            </div>
            <button onclick="backToLogin()" data-key="backToLogin" name="backToLogin" style="height:50px !important" type="submit" class="translate btn btn-block segoeui-regular-normal-white-15px" id="backToLogin">Back to Login</button>
        </div>

        <div class="col-lg-6" style="display: flex; align-items: center">
            <img src="../images/login.png" class="login-page-img" alt="Accessibility clarified" />
        </div>

    </div>
</main>

<?php
if (isset($_GET["lang"])) {
    echo '<script>';
    switch ($_GET["lang"]) {
        case "fr":
        case "da":
        case "it":
        case "es":
        case "de":
        case "nl":
            echo "changeLang('" . $_GET["lang"] . "');";
            break;
        case "en":
        default:
            echo "changeLang('en');";
            break;
    }
    echo '</script>';
} else if (isset($_COOKIE['lang'])) {
    echo '<script defer> changeLang("' . $_COOKIE['lang'] . '") </script>';
} else {
    echo '<script src="../js/loginLang.js" defer></script>';
}
?>

</body>

</html>