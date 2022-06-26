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
    <title>ADScan - Forgot Password Success </title>


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


        .conformation-text {
            left: 470px;
            letter-spacing: 0;
            line-height: 27px;
            white-space: nowrap;
        }

        .roboto-normal-black-16px {
            color: var(--text-color-black);
            font-family: var(--font-family-roboto);
            font-size: var(--font-size-xl2);
            font-style: normal;
            font-weight: 400;
        }
    </style>
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
    <main class="container" style="">
        <div class="row" style=" margin-top: -15%;background-color: #e6efff; padding: 20px; height: 75vh; margin-bottom: 150px">

            <div class="col-lg-6" id="new-login-form-container">
                <div style="display: flex; justify-content: space-between; align-items: center">
                    <div>
                        <img width="180px" style="margin-bottom:15%" src="https://anima-uploads.s3.amazonaws.com/projects/6284d6017c7b361162940774/releases/628b90e723270e8c56649893/img/group-999@1x.png" alt="AD scan logo" />
                    </div>
                </div>

                <div>
                    <span data-key="request-received" class="translate forgot-your-password page-title">Request Received</span>
                </div>
                <div style="margin-top:10px">
                    <p class="conformation-text roboto-normal-black-16px">
                        Thank you. If you have an ADScan account for this <br /> email address, you&#39;ll receive an email
                        shortly with instructions <br /> to reset your ADScan account password.<br /> Please be sure to check your spam folder in your email <br />if you don't receive this soon. <br /> If you need to contact our support team for help,
                        <br /> please email us at support@abledocs.com or <br /> contact your account representative.
                    </p>
                </div>
                <button onclick="backToLogin()" data-key="backToLogin" name="backToLogin" style="height:50px !important" type="submit" class="translate btn btn-block segoeui-regular-normal-white-15px" id="backToLogin">Back to Login</button>
            </div>

            <div class="col-lg-6" style="display: flex; align-items: center">
                <img src="../images/login.png" style="height: 710px;margin-left: -3%;" alt="AD scan logo" />
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