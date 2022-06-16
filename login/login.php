<?php
session_start();
?>

<!doctype html>

<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>ADScan - Login</title>
    <link rel="shortcut icon" type="image/x-icon" href="../images/favicon.ico">
    <link rel="stylesheet" href="../css/bootstrap.min.css" type="text/css" />
    <link rel="stylesheet" href="../css/login.css" type="text/css" />
    <link rel="stylesheet" href="../css/styleguide.css" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/0.8.2/css/flag-icon.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/4.1.0/mdb.min.css" rel="stylesheet" />

    <!-- MDB -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/4.1.0/mdb.min.js"></script>
    <script src="../js/jquery.min.js"></script>
    <script src="../js/lang.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let main = document.querySelector("body > main");
            main.style.minHeight = main.offsetHeight + "px";
            getCurrentLang();
        });

        function getCurrentLang() {
            const params = new URLSearchParams(window.location.search)
            let currentLanguage = params.get('lang') ? params.get('lang') : 'en';
            document.getElementById('languages').value = currentLanguage;
            let language;
            switch (currentLanguage) {
                case 'en':
                    language = "English";
                    break;
                case 'da':
                    language = "Dansk";
                    break;
                case 'de':
                    language = "Deutsch";
                    break;
                case 'es':
                    language = "Espanol";
                    break;
                case 'fr':
                    language = "Francais";
                    break;
                case 'it':
                    language = "Italino";
                    break;
                case 'nl':
                    language = "Nederlands";
                    break;
            }
            document.getElementById('selectedLanguage').innerHTML = `: ${language}`;
        }

        function setLanguage() {
            const selected_lang = document.getElementById("languages").value;
            window.location.href = `login.php?lang=${selected_lang}`
        }

        function forgotPassword() {
            const params = new URLSearchParams(window.location.search)
            let currentLanguage = params.get('lang') ? params.get('lang') : "en";
            window.location.href = `forgot.php?lang=${currentLanguage}`;
        }
    </script>
</head>

<body>

    <?php
    // remove lingering session variables
    session_unset();
    ?>

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
                    <span data-key="login" class="translate roboto-medium-black-14px">Log in</span>
                </div>
                <div style="margin-top:10px">
                    <span data-key="enterYourEmailAddressAndPassword" class="translate roboto-normal-black-9px-2">Enter your Email address and Password</span>
                </div>
                <div style="margin-top:10px">
                    <span data-key="language" class="translate roboto-normal-black-9px-2">Language</span><span id="selectedLanguage" class="roboto-normal-black-9px-2"></span>
                </div>
                <div class="form-group" style="border: 1px solid var(--wild-blue-yonder);border-radius: 0.25rem;margin-top: 5%;">
                    <select name="languages" id="languages" class="translate form-control border-1px-wild-blue-yonder" onchange="setLanguage()">
                        <option disabled selected>Select Language</option>
                        <option value="da">Dansk</option>
                        <option value="de">Deutsch</option>
                        <option value="en">󠁧󠁢󠁥󠁮󠁧󠁿English</option>
                        <option value="es">󠁧󠁢󠁳󠁣Espanol</option>
                        <option value="fr">Francais</option>
                        <option value="nl">Nederlands</option>
                        <option value="it">Italino</option>
                    </select>
                </div>

                <form method="post" action="../includes/login.inc.php">
                    <div class="form-group">
                        <label class="translate roboto-normal-black-9px-2" data-key="email" for="login-email">Email Address</label>
                        <input id="login-email" name="email" class="form-control border-1px-wild-blue-yonder" type="text">
                    </div>

                    <div class="form-group">
                        <label class="translate roboto-normal-black-9px-2" data-key="password" for="login-password">Password</label>
                        <input id="login-password" name="password" class="form-control border-1px-wild-blue-yonder" type="password">
                    </div>

                    <div style="display: flex; justify-content: space-between; font-size: 10px">
                        <div class="form-group" style="margin-top: 3%;">
                            <input type="checkbox" id="login-remberInformation" name="remberInformation">
                            <label class="translate roboto-normal-black-9px-2" data-key="rememberInformation" for="login-remeber-information">Remeber Information</label>
                        </div>

                        <div class="form-group">
                            <span onclick="forgotPassword()" class="translate roboto-normal-bahama-blue-9px-2" data-key="forgotPass"> Forgot Password? </span>
                        </div>
                    </div>
                    <button data-key="login" style="height:50px !important" type="submit" name="login-submit" class="translate btn btn-block segoeui-regular-normal-white-15px" id="loginbutton">Log in</button>

                    <!--check for success msg-->
                    <?php
                    $successMsg = "";
                    if (isset($_GET['success'])) {
                        if ($_GET['success'] === "sent") {
                            $successMsg = "Email Sent";
                            echo "<p style='color: #008000;font-weight: bold'>" . $successMsg . "</p>";
                        }
                    }
                    ?>

                    <!--check for an error message-->
                    <?php
                    $errorMsg = "";
                    if (isset($_GET["error"])) {
                        switch ($_GET["error"]) {
                            case 'emptyfields':
                                $errorMsg = "You must enter a username and password";
                                echo '<p style="color: #a10000" class="translate" data-key="emptyfields" role="alert" aria-atomic="true">' . $errorMsg . '</p>';
                                break;
                            case 'incorrect':
                                $errorMsg = "Either your email or password was incorrect";
                                echo '<p style="color: #a10000" class="translate" data-key="incorrect" role="alert" aria-atomic="true">' . $errorMsg . '</p>';
                                break;
                            case 'external':
                                $errorMsg = "Something went wrong trying to retrieve your account";
                                echo '<p style="color: #a10000" class="translate" data-key="external" role="alert" aria-atomic="true">' . $errorMsg . '</p>';
                                break;
                            case 'passReset':
                                $errorMsg = "Error resetting password";
                                echo '<p style="color: #a10000" class="translate" data-key="resetError" role="alert" aria-atomic="true">' . $errorMsg . '</p>';
                                break;
                        }
                    }
                    ?>

                </form>
                <br />
                <!-- <div class="roboto-normal-bahama-blue-9px">
                    <span class="translate roboto-normal-black-9px-2">No Account? </span><span class="translate roboto-normal-bahama-blue-9px-2">Contact us</span>
                </div> -->
                <br />

            </div>

            <div class="col-lg-6" style="display: flex; align-items: center">
                <img src="../images/login.png" style="    height: 710px;margin-left: -3%;" alt="AD scan logo" />
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