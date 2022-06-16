<?php
    session_start();
?>

<!doctype html>

<html>

<?php
/**
 * Takes the user back to the forgot password screen
 */
function backToForgotPass() {
    if (isset($_GET["lang"])) {
        header("location: ../login/forgot.php?lang=" . $_POST["lang"]);
    } else {
        header("location: ../login/forgot.php?lang=en");
    }
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="shortcut icon" type="image/x-icon" href="../images/favicon.ico">
    <link rel="stylesheet" href="../css/login.css" type="text/css" />
    <link rel="stylesheet" href="../css/bootstrap.min.css" type="text/css" />
    <script src="../js/jquery.min.js"></script>
    <script src="../js/lang.js"></script>
    <title>ADScan - New Password</title>
    <style>
        body {
            background: rgb(10,120,194);
            background: radial-gradient(circle, rgba(10,120,194,1) 0%, rgba(255,255,255,1) 100%);
        }
    </style>
    <script>
        function validateNewPwd() {
            let pwd = $("#signup-password").val()
            let conPwd = $("#signup-confirm-password").val()
            if (pwd === "" || conPwd === "") {
                $("#signup-password").addClass("is-invalid");
                $("#signup-confirm-password").addClass("is-invalid");
                $("#newPwdForm").trigger('reset');
                return false;
            }
            if (pwd !== conPwd) {
                $("#signup-password").addClass("is-invalid");
                $("#signup-confirm-password").addClass("is-invalid");
                $("#newPwdForm").trigger('reset');
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <div id="form-container" class="container">
        <?php
            $selector = $_GET["selector"];
            $validator = $_GET["validator"];

            if (empty($selector) || empty($validator)) {  // return to forgot password if no tokens
                backToForgotPass();
            } else {
                // make sure tokens are valid
                if (ctype_xdigit($selector) && ctype_xdigit($validator)) {
                    ?>
                    <form id="newPwdForm" method="post" onsubmit="return validateNewPwd()" action="../includes/newPassword.php">
                        <h1 class="translate" data-key="resetYpass">Reset your password</h1>
                        <input type="hidden" name="selector" value="<?php echo $selector; ?>">
                        <input type="hidden" name="validator" value="<?php echo $validator; ?>">
                        <?php
                        if (isset($_GET["lang"])) {
                            echo "<input type='hidden' name='lang' value='" . $_GET["lang"] . "'>";
                        } else {
                            echo "<input type='hidden' name='lang' value='en'>";
                        }
                        ?>
                        <div class="form-group">
                            <label id="password-label" class="translate" data-key="password" for="signup-password">Password</label>
                            <input type="password" class="form-control" name="pwd" id="signup-password">
                            <small id="password-valid" style="display: none" class="text-danger"></small>
                        </div>
                        <div class="form-group">
                            <label id="conPassword-label" class="translate" data-key="conPass" for="signup-confirm-password">Confirm Password</label>
                            <input type="password" class="form-control" name="repeatPwd" id="signup-confirm-password">
                            <small id="conPassword-valid" style="display: none" class="text-danger"></small>
                        </div>
                        <button class="btn btn-primary translate" name="reset-pass-submit" data-key="resetMpass" type="submit">Reset my Password</button>
                    </form>
                    <?php
                } else {
                    backToForgotPass();
                }
            }
        ?>
    </div>


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
                    echo "changeLang('" . $_GET["lang"] ."');";
                    break;
                case "en":
                default:
                    echo "changeLang('en');";
                    break;
            }
            echo '</script>';
        } else if (isset($_COOKIE['lang'])) {
            echo '<script defer> changeLang("'. $_COOKIE['lang'] .'") </script>';
        } else {
            echo '<script src="../js/loginLang.js" defer></script>';
        }
    ?>
</body>
</html>