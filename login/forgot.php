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
    <title>ADScan - Forgot Password</title>

    <script defer>
        function validateEmail(email) {
            const re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(email);
        }

        function checkEmail() {
            let email = document.querySelector("#forgot-email").value
            if (email === "") { // check for not empty
                document.querySelector("#forgot-email").classList.add("is-invalid");
                return false;
            }
            // proper format in case html5 check for email type fails
            if (!validateEmail(email)) {
                document.querySelector("#forgot-email").classList.add("is-invalid");
                return false;
            }
            return true;
        }

        function backToLogin() {
            const params = new URLSearchParams(window.location.search)
            let currentLanguage = params.get('lang') ? params.get('lang') : "en";
            window.location.href = `login.php?lang=${currentLanguage}`
        }
    </script>
</head>

<body>
<main class="container">
    <div class="row inner-container">

        <div class="col-lg-6" id="new-login-form-container">
            <div style="display: flex; justify-content: space-between; align-items: center">
                <div>
                    <img width="180px" style="margin-bottom:15%" src="https://anima-uploads.s3.amazonaws.com/projects/6284d6017c7b361162940774/releases/628b90e723270e8c56649893/img/group-999@1x.png" alt="AD scan logo" />
                </div>
            </div>

            <div>
                <span data-key="forgotPass" class="translate page-title">Forgot your Password?</span>
            </div>
            <div style="margin-top:10px">
                <p class="text-message roboto-normal-black-16px">
                    If you&#39;ve lost your password or wish to reset it, <br />enter your email and send your
                    request below.<br />A new password will be send to your email.
                </p>
            </div>
            <div style="margin-top:10px">
                <span id="selectedLanguage" class="roboto-normal-black-9px-2"></span>
            </div>

            <form method="post" onsubmit="return checkEmail()" action="../includes/forgotPassword.php">
                <div class="form-group">
                    <label data-key="" for="forgot-email" class="translate enter-user-name-or-email heading-2">Enter your email</label>
                    <input id="forgot-email" name="forgot-email" class="translate form-control border-1px-wild-blue-yonder" style="border: 1px solid #7088b3 !important;" type="email">
                </div>
                <button data-key="forgotSend" name="passwordForgotSubmit" style="height:50px !important" type="submit" class="translate btn btn-block segoeui-regular-normal-white-15px" id="loginbutton">Send my password</button>
            </form>
            <br />
            <div class="back-to-login">
                <span class="span0-1 top-menu"></span><a href="javascript:void(0)" data-key="backToLogin" style="cursor:pointer" class="translate span1 top-menu" onclick="backToLogin()">Back to Login</a>
            </div>
            <br />
        </div>

        <div class="col-lg-6" style="display: flex; align-items: center">
            <img src="../images/login.png" class="login-page-img" alt="Accessibility clarified" />
        </div>

    </div>
</main>

<?php
// show invalid email if error is true
if (isset($_GET['error'])) {
    echo "<script defer>
            document.querySelector('#forgot-email').classList.add('is-invalid');
          </script>";
}
?>

<script defer>
    $("#forgot-email").on('input', () => {
        $("#forgot-email").removeClass('is-invalid');
    });
</script>

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