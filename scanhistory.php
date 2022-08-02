<?php
session_start();
// check for user logged in
if (
    !isset($_SESSION["userFirst"]) || !isset($_SESSION["userCompanyId"]) || !isset($_SESSION["userCompany"]) || !isset($_SESSION["userLast"])
    || !isset($_SESSION["userID"]) || !isset($_SESSION["email"]) || !isset($_SESSION["isOwner"])
) {
    header("Location: ./login/login.php");
    exit();
}

?>



<!DOCTYPE html>

<html>

<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>ADScan - Scan history</title>

    <!-- Bootstrap CSS CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <link rel="stylesheet" href="css/styleguide.css">

    <!-- Font Awesome JS -->
    <link media="all" type="text/css" rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.1.1/css/all.css">

    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico">

    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css" />
    <link type="text/css" rel="stylesheet" href="//unpkg.com/bootstrap-vue@latest/dist/bootstrap-vue.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat&family=Raleway&display=swap" rel="stylesheet">


    <!-- development version, includes helpful console warnings -->
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>

    <script src="//unpkg.com/bootstrap-vue@latest/dist/bootstrap-vue.min.js"></script>
    <script src="//unpkg.com/bootstrap-vue@latest/dist/bootstrap-vue-icons.min.js"></script>

    <?php
    echo '<script>';
    // check if lang is set in a cookie
    if (isset($_COOKIE['lang'])) {
        echo "let currentLang = '" . $_COOKIE["lang"] . "';";
    } elseif (isset($_SESSION["lang"])) {  // if not use the lang set in the profile
        echo "let currentLang = '" . $_SESSION["lang"] . "';";
    } else {
        // if lang cant be found in a cookie or session use the system default, english if that fails
        echo "let currentLang = 'en' // default
            try {
                currentLang = window.navigator.userLanguage || window.navigator.language;
                currentLang = currentLang.split('-')[0];  // removes the country code
            } catch (err) {
                console.error('problem determining language, defaulting to english');
                let currentLang = 'en';
            }";
    }
    echo '</script>';
    ?>

    <script>
        const client = {
            userID: <?php echo $_SESSION['userID'] ?>,
            clientId: <?php echo $_SESSION["userCompanyId"] ?>,
            companyName: "<?php echo $_SESSION["userCompany"] ?>",
            firstName: "<?php echo $_SESSION["userFirst"] ?>",
            lastName: "<?php echo $_SESSION["userLast"] ?>",
            email: "<?php echo $_SESSION["email"] ?>",
            adoContactID: "<?php echo $_SESSION["adoID"] ?>",
            lang: "<?php echo $_SESSION["lang"] ?>",
            isOwner: "<?php echo $_SESSION["isOwner"] ?>" === "1"
        };

            let viewStatus = null;
            if (sessionStorage.getItem('viewStatus') !== null) {
                viewStatus = JSON.parse(sessionStorage.getItem('viewStatus'));
                viewStatus.isShowingFiles = false;
                viewStatus.fileCategory = "";
                sessionStorage.setItem('viewStatus', JSON.stringify(viewStatus));
            }
    </script>

</head>

<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-1" style="margin-left:-15px; padding-left: 0px !important; padding-right: 0px !important">
            <div class="sidenav">
                <div class=" wrapper">
                    <!-- Sidebar  -->
                    <?php
                    include 'menu/sidebar.php';
                    ?>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-11">
            <?php
            include 'menu/topnav.php';
            ?>

            <div class="container-fluid">
                <div class="container scan-search-outer" id="scan-history-page">
                    <div class="heading">
                        <h1>Latest Scan History: </h1>
                    </div>
                    <div class="collapse-table-body" id="scan-history-table">
                        <div class="coll-row" v-for="(scan, index) in getScanAgainstCrawl()" :key="index">
                            <div class="collapse-head" data-toggle="collapse" :href="`#collapse_${index}`" role="button" aria-expanded="false" aria-controls="collapse">
                                <div class="left-content">
                                    <i class="fa fa-caret-down"></i>
                                    <h6>{{ scanHistoryDate(scan) }}</h6>
                                    <h6>{{ getURL(scan) }}</h6>
                                </div>
                                <div class="right-content">
                                    <span class="translate"  data-key="viewResults" data-toggle="collapse" :href="`#collapse_${index}`" role="button" aria-expanded="false" aria-controls="collapse" style="color: var(--abledocs-blue)">View Results</span>
                                </div>
                            </div>
                            <div class="collapse coll-table" :id="`collapse_${index}`">
                                <table class="table">
                                    <thead>
                                    <tr>
                                        <th scope="col">Complaint</th>
                                        <th scope="col">Non-Complaint</th>
                                        <th scope="col">Untagged</th>
                                        <th scope="col">Off Side Files</th>
                                        <th scope="col">File Errors</th>


                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td>{{scan.compliant ? scan.compliant : 0}}</td>
                                        <td>{{scan.nonCompliant ? scan.nonCompliant : 0}}</td>
                                        <td>{{scan.untagged ? scan.untagged : 0}}</td>
                                        <td>{{scan.offsiteFiles ? scan.offsiteFiles : 0}}</td>
                                        <td>{{scan.Files_Error ? scan.Files_Error: 0}}</td>

                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- contains Vue instances -->
<script>
    /**
     * Scan history page instance
     * @type {Vue}
     */
    let scanHistoryPage = new Vue({
        el: "#scan-history-page",
        methods: {
            getScanAgainstCrawl: () => {
                return JSON.parse(sessionStorage.getItem('scanHistoryPage')) ? JSON.parse(sessionStorage.getItem('scanHistoryPage')) : [];
            },
            scanHistoryDate: (scan) => {
                return scan.crawl_time_end.split('T')[0];
            },
            getURL: (scan) => {
                return scan.starturl.split("/")[2]
            },
        }
    })
    </script>
    <!-- <script src="js/dashboard.js" defer></script> -->
</body>

</html>
