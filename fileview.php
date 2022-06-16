<?php
    session_start();
    require("config.php");

    // check for user logged in
    if (!isset($_SESSION["userFirst"])) {
        header("Location: ./login/login.php");
        exit();
    }
?>

<!DOCTYPE html>

<html>

<?php
echo '<script>';
// check if lang is set in a cookie
if (isset($_COOKIE['lang'])) {
    echo "let currentLang = '". $_COOKIE["lang"] ."';";
}
elseif (isset($_SESSION["lang"])) {  // if not use the lang set in the profile
    echo "let currentLang = '". $_SESSION["lang"] ."';";
}
else {
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

    let fileID = <?php echo $_GET['id'] ?>;

    // Get the base url for Blob Storage
    let blobStorage = '<?php echo BLOB_STORAGE_URL; ?>';

</script>

    <head>
        <meta http-equiv="content-type" content="text/html;charset=utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>ADScan</title>
        <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico">

        <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css" />
        <link href="https://fonts.googleapis.com/css2?family=Montserrat&family=Raleway&display=swap" rel="stylesheet">
        <link
            rel="stylesheet"
            href="https://use.fontawesome.com/releases/v5.7.2/css/all.css"
            integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr"
            crossorigin="anonymous"
        />
        <link rel="stylesheet" href="css/dash.css" type="text/css" />

        <script src="js/jquery.min.js"></script>
        <!-- development version, includes helpful console warnings -->
        <!-- <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script> -->
        <!-- production version, optimized for size and speed -->
        <script src="https://cdn.jsdelivr.net/npm/vue@2.6.12/dist/vue.min.js"></script>

        <!-- Sweet alerts -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10" defer></script>

        <script src="js/actionMenu.js" defer></script>
        <script src="js/lang.js"></script>

        <script src="js/pdfjs/build/pdf.js" async></script>
        <script src="https://rawgit.com/kangax/fabric.js/master/dist/fabric.js" async></script>

        <script src="js/preview.js" async></script>

        <script defer>
            function usersDropdown() {
                let userOptions = $(".dropdown-users");
                let userBtn = $("#users-btn")
                if (userOptions.css('display') === 'none') {
                    userOptions.show();
                    userBtn.attr('aria-expanded', true);
                } else {
                    userOptions.hide();
                    userBtn.attr('aria-expanded', false);
                }
            }
            // close the logout menu if open
            window.onclick = function(event) {
                if (!event.target.matches('#users-btn')) {
                    let userOptions = $(".dropdown-users");
                    let userBtn = $("#users-btn")
                    if (userOptions.css('display') !== 'none') {
                        userOptions.hide();
                        userBtn.attr('aria-expanded', false);
                    }
                }
                // same with the action menu, also defined in actionmenu.js
                if (!event.target.matches('.dropbtn')) {
                    var dropdowns = document.getElementsByClassName("dropdown-content");
                    var i;
                    for (i = 0; i < dropdowns.length; i++) {
                        var openDropdown = dropdowns[i];
                        if (openDropdown.classList.contains('show')) {
                            openDropdown.classList.remove('show');
                        }
                    }
                }
            }
        </script>

        <style>
            tbody td:nth-child(1), thead th:nth-child(1) {
                width: 460px !important;
                max-width: 460px !important;
                text-align: left;
                overflow-x: hidden;
            }
            tbody td:nth-child(2), thead th:nth-child(2) {
                text-align: center;
            }
            tbody td:nth-child(3), thead th:nth-child(3) {
                text-align: center;
            }
            tbody td:nth-child(4), thead th:nth-child(4) {
                text-align: center;
            }
            tbody td {
                padding: 6px 12px !important;
            }
        </style>

    </head>
<body>

<header id="main-app-header">
    <a class="skip-to-content-link" href="#file-preformance-container">Skip to Content</a>
    <div class="row">
        <div class="col-7">
            <img id="fileviewAdLogo" src="images/ADScan_RGB.png" alt="AD scan logo"/>
            <p id="client-name" style="display: inline-block">{{ companyName }}</p>
        </div>
        <div class="col-5" id="user-controls-container">
            <p style="display: inline-block"> {{ username }}</p>
            <button id="users-btn" type="button" onclick="usersDropdown()" aria-label="User Account" aria-expanded="false" class="btn btn-light">
                <i style="pointer-events: none;" class="fa fa-user"></i>&nbsp;<i class="fa fa-caret-down"></i>
            </button>
            <div class="dropdown-users dropdown-content">
                <a href="login/login.php" class="translate" data-key="logout">Logout</a>
            </div>
        </div>
    </div>
    <div id="client-title" class="row">
        &nbsp;
    </div>
</header>
<main id="pdf-viewer">
    <div class="row">
        <div id="file-title" class="col-10">
            <a class='translate btn' data-key='dashboard' href='index.php'>Dashboard</a>
            <!-- Show the file title, if not found show the filename -->
            <h1 v-if="file.Title === null || file.Title === ''">{{ file.filename }}</h1>
            <h1 v-else>{{ file.Title }}</h1>
        </div>
        <div style="margin-right: 0" class="col-2">
            <div id="fileview-chooseAction" class="dropdown">
                <button onclick="openOptionMenu()" class="dropbtn"><span data-key="chooseAction" class="translate">Choose an Action</span>&nbsp;&nbsp;<i style="color: white" class="fa fa-caret-down"></i></button>
                <div id="dropdown-content" class="dropdown-content">
                   <!--<a role="button" onclick="return false" >Send for remediation</a>
                    <a role="button" onclick="return false" >Send to AbleDocs</a>
                    <a role="button" onclick="return false" >Rescan File</a>-->
                    <a role="button" data-key="downloadThisFile" class="translate" onclick="downloadSinglePDF(pdfInfo.file.url, pdfInfo.file.filename)">Download File</a>
                    <a role="button" data-key="Exportcsv" class="translate" onclick="exportCsv(pdfInfo.file.crawl_id, [pdfInfo.file.ID])">Export to .csv</a>
                    <a role="button" class="translate" data-key="sendToAble" onclick="sendForRemediation(file, client)">Send to AbleDocs</a>
                </div>
            </div>
        </div>
    </div>
    <div class="row" id="url-indicator-row" style="border: 1px solid var(--border-grey);">
        <div class="col-1">
            <p style="font-weight: bold">URL:</p>
        </div>
        <div style="background-color: white" class="col-8">
            <p id="url-location" style="overflow:hidden; white-space: nowrap; text-overflow: ellipsis">{{ file.url }}</p>
        </div>
        <div style="background-color: white;text-align: center;border-left: 1px solid var(--border-grey)" class="col-3">
            <span>
                <button class="translate btn" data-key="copyURL" id="copyURLBtn" v-on:click="copyURL">Copy URL</button>&nbsp;
                <a class="translate btn" target="_blank" data-key="visitURL" v-bind:href="file.url">Visit URL</a>
            </span>
        </div>
    </div>
    <div class="row" style="border-top: 1px solid var(--border-grey)" id="pdf-view-content-wrapper">
        <div id="file-preformance-container" class="col-lg-7">

            <div class="section-header">
                <h2 data-key="filePref" class="translate">File Performance</h2>
            </div>

            <!-- Errors Table -->
            <table id="errorsTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th id="CheckName" scope="col">
                            <span class="pdf-count-checks"><span class="translate" data-key="index">Index</span>: {{  (Math.floor(file.UA_Index * 100) / 100).toFixed(2) }}</span><br />
                            <span class="translate" data-key="check">Check</span>
                        </th scope="col">
                        <th id="Passed"><i style="color: green" class="fas fa-check-circle"></i> <span class="translate" data-key="passed">Passed</span><br/>
                            <span class="pdf-count-checks">{{ passedCount }}</span>
                        </th>
                        <th id="Warned" scope="col"><i style="color: orange" class="fas fa-exclamation-circle"></i> <span class="translate" data-key="warned">Warned</span><br/>
                            <span class="pdf-count-checks">{{ warnedCount }}</span>
                        </th>
                        <th id="Failed" scope="col"><i style="color: red" class="fas fa-times-circle"></i> <span class="translate" data-key="failed">Failed</span><br/>
                            <span class="pdf-count-checks">{{ failedCount }}</span>
                        </th>
                    </tr>
                </thead>

                <template v-for="checkHead in checkLabels.map(item => item.category).filter((value, index, self) => self.indexOf(value) === index)">
                    <tr class="table-section-header">
                        <td :data-key='checkHead.replace(/ /g,"_").replace(/"/g, "").trim()' class="translate" colspan="4">{{ checkHead }}</td>
                    </tr>
                    <template v-for="section in checkLabels.filter(x => x.category === checkHead)">
                        <tbody>
                        <tr class="labelrow table-section-subhead">
                            <td headers="CheckName" tabindex="0" aria-expanded="false" :aria-label="section.label + ' section, press enter to toggle'" :data-key='section.label.replace(/ /g,"_").replace(/"/g, "").trim()' class="translate">
                                {{ section.label }}
                            </td>
                            <td headers="Passed">{{ section.passed }}</td>
                            <td headers="Warned">{{ section.warnings }}</td>
                            <td headers="Failed">{{ section.failed }}</td>
                        </tr>
                        <template v-for="check in checks.filter(x => x.Category === section.label)">
                            <tr v-if="check.FailedCount > 0" style="display: none" onclick="hideIssues(this)" :data-checkId="check.CheckId" aria-expanded='false' is-showing-issue='false' class="table-danger">
                                <td headers="CheckName" style="padding-left: 2em !important;" class="translate" :data-key='check.Name.replace(/ /g,"_").replace(/"/g, "").trim()'>{{ check.Name }}</td>
                                <td headers="Passed" v-if="check.PassedCount !== null">{{ check.PassedCount }}</td>
                                <td headers="Passed" v-else>0</td>
                                <td headers="Warned" v-if="check.WarnedCount !== null">{{ check.WarnedCount }}</td>
                                <td headers="Warned" v-else>0</td>
                                <td headers="Failed" v-if="check.FailedCount !== null">{{ check.FailedCount }}</td>
                                <td headers="Failed" v-else>0</td>
                            </tr>
                            <tr v-else-if="check.WarnedCount > 0" style="display: none" onclick="hideIssues(this)" :data-checkId="check.CheckId" aria-expanded='false' is-showing-issue='false' class="table-warning">
                                <td headers="CheckName" style="padding-left: 2em !important;" class="translate" :data-key='check.Name.replace(/ /g,"_").replace(/"/g, "").trim()'>{{ check.Name }}</td>
                                <td headers="Passed" v-if="check.PassedCount !== null">{{ check.PassedCount }}</td>
                                <td headers="Passed" v-else>0</td>
                                <td headers="Warned" v-if="check.WarnedCount !== null">{{ check.WarnedCount }}</td>
                                <td headers="Warned" v-else>0</td>
                                <td headers="Failed" v-if="check.FailedCount !== null">{{ check.FailedCount }}</td>
                                <td headers="Failed" v-else>0</td>
                            </tr>
                            <tr v-else-if="check.PassedCount > 0" style="display: none" :data-checkId="check.CheckId" class="table-success">
                                <td headers="CheckName" style="padding-left: 2em !important;" class="translate" :data-key='check.Name.replace(/ /g,"_").replace(/"/g, "").trim()'>{{ check.Name }}</td>
                                <td headers="Passed" v-if="check.PassedCount !== null">{{ check.PassedCount }}</td>
                                <td headers="Passed" v-else>0</td>
                                <td headers="Warned" v-if="check.WarnedCount !== null">{{ check.WarnedCount }}</td>
                                <td v-else>0</td>
                                <td headers="Failed" v-if="check.FailedCount !== null">{{ check.FailedCount }}</td>
                                <td headers="Failed" v-else>0</td>
                            </tr>
                            <tr v-else :data-checkId="check.CheckId" style="display: none">
                                <td headers="CheckName" style="padding-left: 2em !important;" class="translate" :data-key='check.Name.replace(/ /g,"_").replace(/"/g, "").trim()'>{{ check.Name }}</td>
                                <td headers="Passed" v-if="check.PassedCount !== null">{{ check.PassedCount }}</td>
                                <td headers="Passed" v-else>0</td>
                                <td headers="Warned" v-if="check.WarnedCount !== null">{{ check.WarnedCount }}</td>
                                <td headers="Warned" v-else>0</td>
                                <td headers="Failed" v-if="check.FailedCount !== null">{{ check.FailedCount }}</td>
                                <td headers="Failed" v-else>0</td>
                            </tr>
                        </template>
                        </tbody>
                    </template>
                </template>
            </table>
        </div>
        <div id="pdf-section" style="border-left: 1px solid var(--border-grey)" class="col-lg-5">

            <div class="section-header">
                <h2 class="translate" data-key="preview">Preview</h2>
            </div>

            <div class="top-bar">
                <button class="btn" id="prev-page">
                    <i class="fas fa-arrow-circle-left"></i> <span class="translate" data-key="PreviousPage">Prev Page</span>
                </button>
                <button class="btn" id="next-page">
                    <span class="translate" data-key="nextPage">Next Page</span> <i class="fas fa-arrow-circle-right"></i>
                </button>
                <!--<button class="btn" style="float: right" id="expand-page">
                    Expand <i class="fas fa-plus-circle"></i>
                </button>-->
                <br />
                <span class="page-info">
                    <span class="translate" data-key="page">Page</span> <span id="page-num"></span> <span class="translate" data-key="of">of</span> <span id="page-count"></span>
                </span>
            </div>
            <canvas id="pdf-render"></canvas>
            <div id="loading-pdf-indicator">
                <div class="spinner-border spinner-border-lg text-primary"></div>
                <p class="translate" data-key="pdf-preview-coming">PDF preview is coming</p>
            </div>
        </div>
    </div>
</main>
<script>
    $( document ).ready(function() {
        changeLang(currentLang, false);
    });
</script>
</body>
</html>