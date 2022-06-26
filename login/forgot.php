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
    <style>
        body {
            margin: 0;
            overflow-x: hidden;
        }

        :root {
            --bahama-blue: #005c98;
            --blue-button-colortop-area: #e6efff;
            --gravel: #464749;
            --text-color-black: #000000;
            --white: #ffffff;

            --font-size-25px: 25px;
            --font-size-l2: 15px;
            --font-size-xl2: 16px;
            --font-size-xxl: 30px;
            --font-size-xxl2: 18px;

            --font-family-roboto: "Roboto", Helvetica;
            --font-family-segoeui-regular: "SegoeUI-Regular", Helvetica;
        }

        .roboto-normal-bahama-blue-15px {
            color: var(--bahama-blue);
            font-family: var(--font-family-roboto);
            font-size: var(--font-size-l2);
        }

        .roboto-normal-bahama-blue-15px-2 {
            color: var(--bahama-blue);
            font-family: var(--font-family-roboto);
            font-size: var(--font-size-l2);
            font-style: normal;
            font-weight: 400;
        }

        .page-title {
            font-family: var(--font-family-roboto);
            font-size: var(--font-size-25px);
            font-style: normal;
            font-weight: 700;
            letter-spacing: 0;
        }

        .forgot-your-password {
            color: var(--gravel);
            font-weight: 700;
            left: 470px;
            line-height: 28px;
            text-align: center;
            white-space: nowrap;
        }


        .if-youve-lost-your {
            left: 470px;
            letter-spacing: 0;
            line-height: 27px;
            white-space: nowrap;
        }

        .enter-user-name-or-email {
            left: 487px;
            letter-spacing: 0;
            line-height: 47px;
            white-space: nowrap;
        }

        .roboto-normal-black-15px {
            color: var(--black);
            font-family: var(--font-family-roboto);
            font-size: var(--font-size-s);
            font-style: normal;
            font-weight: 400;
        }

        .roboto-normal-black-16px {
            color: var(--text-color-black);
            font-family: var(--font-family-roboto);
            font-size: var(--font-size-xl2);
            font-style: normal;
            font-weight: 400;
        }

        .back-to-login {
            color: var(--bahama-blue);
            font-family: var(--font-family-roboto);
            font-size: var(--font-size-xxl2);
            font-weight: 400;
            left: 470px;
            letter-spacing: 0;
            line-height: 47px;
            white-space: nowrap;
        }

        .span0-1 {
            color: var(--text-color-black);
        }

        .span1 {
            text-decoration: underline;
        }

        .top-menu {
            font-family: var(--font-family-roboto);
            font-size: var(--font-size-xxl2);
            font-style: normal;
            font-weight: 400;
            letter-spacing: 0;
        }
    </style>
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
    <main class="container" style="">
        <div class="row" style=" margin-top: -15%;background-color: #e6efff; padding: 20px; height: 75vh; margin-bottom: 150px">

            <div class="col-lg-6" id="new-login-form-container">
                <div style="display: flex; justify-content: space-between; align-items: center">
                    <div>
                        <img width="180px" style="margin-bottom:15%" src="https://anima-uploads.s3.amazonaws.com/projects/6284d6017c7b361162940774/releases/628b90e723270e8c56649893/img/group-999@1x.png" alt="AD scan logo" />
                    </div>
                    <!-- <div class="roboto-normal-bahama-blue-9px">
                        <span data-key="need" class="translate roboto-normal-black-9px-2">Need </span><span data-key="help" class="translate roboto-normal-bahama-blue-9px-2">Help?</span>
                    </div> -->
                </div>

                <div>
                    <span data-key="forgotPass" class="translate forgot-your-password page-title">Forgot your Password?</span>
                </div>
                <div style="margin-top:10px">
                    <p class="if-youve-lost-your roboto-normal-black-16px">
                        If you&#39;ve lost your password or wish to reset it, <br />enter your email and send your
                        request below.<br />A new password will be send to your email.
                    </p>
                </div>
                <div style="margin-top:10px">
                    <span id="selectedLanguage" class="roboto-normal-black-9px-2"></span>
                </div>

                <form method="post" onsubmit="return checkEmail()" action="../includes/forgotPassword.php">
                    <div class="form-group">
                        <label data-key="userName-email" for="forgot-email" class="translate enter-user-name-or-email roboto-normal-black-15px">Enter Email</label>
                        <input id="forgot-email" name="forgot-email" class="translate form-control border-1px-wild-blue-yonder" type="email">
                    </div>
                    <button data-key="forgotSend" name="passwordForgotSubmit" style="height:50px !important" type="submit" class="translate btn btn-block segoeui-regular-normal-white-15px" id="loginbutton">Send my password</button>
                </form>
                <br />
                <div class="back-to-login">
                    <span class="span0-1 top-menu"></span><span data-key="backToLogin" style="cursor:pointer" class="translate span1 top-menu" onclick="backToLogin()">Back to Login</span>
                </div>
                <br />
            </div>

            <div class="col-lg-6" style="display: flex; align-items: center">
                <img src="../images/login.png" style="height: 710px;margin-left: -3%;" alt="AD scan logo" />
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