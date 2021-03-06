<?php
session_start();
// check for user logged in
if (!isset($_SESSION["userFirst"]) || !isset($_SESSION["userCompanyId"]) || !isset($_SESSION["userCompany"]) || !isset($_SESSION["userLast"])
    || !isset($_SESSION["userID"]) || !isset($_SESSION["email"]) || !isset($_SESSION["isOwner"])) {
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
    <title>ADScan</title>
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico">

    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css" />
    <link type="text/css" rel="stylesheet" href="//unpkg.com/bootstrap-vue@latest/dist/bootstrap-vue.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat&family=Raleway&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dash.css" type="text/css" />

    <script src="js/jquery.min.js"></script>
    <script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

    <!-- Chert.js is for creating the charts in dashboard and patternomaly creates the pattern on the colours -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
    <!--<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/patternomaly@1.3.2/dist/patternomaly.min.js"></script>-->

    <!-- development version, includes helpful console warnings -->
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <!-- production version, optimized for size and speed -->
    <!--<script src="https://cdn.jsdelivr.net/npm/vue@2.6.12/dist/vue.min.js"></script>-->

    <script src="//unpkg.com/bootstrap-vue@latest/dist/bootstrap-vue.min.js"></script>
    <script src="//unpkg.com/bootstrap-vue@latest/dist/bootstrap-vue-icons.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script src="js/jszip/Stuk-jszip-7bbcb38/dist/jszip.min.js"></script>
    <script src="js/actionMenu.js"></script>
    <script src="js/lang.js"></script>
    <script src="https://cdn.syncfusion.com/ej2/19.2.46/ej2-vue-es5/dist/ej2-vue.min.js"></script>
    <link rel="stylesheet" href="https://cdn.syncfusion.com/ej2/material.css">

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
        let resChart = null;
        let historyChart = null;

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

        // check if abledocs loggin in as client
        if (sessionStorage.getItem('clientID') !== null && client.companyName === "AbleDocs") {
            client.clientId = sessionStorage.getItem('clientID');
        }

        // go to master page if login from abledocs
        if (client.clientId === 1 && client.companyName === "AbleDocs") {
            window.location.href = 'master.php';
        }

        // sessionStorage.removeItem("clientID");

        // get the scan and files from scans
        let scans = [];
        let scansReq = new XMLHttpRequest()
        scansReq.open("GET", "scanAPI.php?action=getCrawls&id=" + client.clientId, false);
        scansReq.onreadystatechange = function() {
            if (this.readyState === 4 && this.status === 200) {
                try {
                    scans = JSON.parse(this.responseText);
                } catch (err) {
                    console.error(err);
                    $(document).ready(function() {
                        $("#proscan").hide();
                        $("#litescan").hide();
                        Swal.fire("404", arrLang[currentLang]["scansBy"] + " " + client.companyName + " " + arrLang[currentLang]["notBeFound"], "error")
                           .then(() => {
                               window.location.href = "login/login.php";
                           });
                    });
                }
            }
            if (this.readyState === 4 && this.status !== 200) {
                scans = [];
                console.error('Something went wrong trying get data for crawls for. Http status: ' + this.status);
            }
        };
        scansReq.send();

        // get all the urls from the scans, exclude any duplicates
        // also make hashmap of scans
        let scanURLS = [];
        let scansHash = [];
        let allScanMostRecent = [];

        // go through every scan
        for (let i=0;i<scans.length;i++) {
            // convert the startdate into and actual date

            let date = new Date();
            date.setFullYear(parseInt(scans[i].crawl_time_end.split("-")[0]));
            date.setMonth(parseInt(scans[i].crawl_time_end.split("-")[1]) - 1);
            date.setDate(parseInt(scans[i].crawl_time_end.split(" ")[0].split("-")[2]));
            date.setHours(parseInt(scans[i].crawl_time_end.split(" ")[1].split(":")[0]));
            date.setMinutes(parseInt(scans[i].crawl_time_end.split(" ")[1].split(":")[0]));
            date.setSeconds(parseInt(scans[i].crawl_time_end.split(" ")[1].split(":")[0]));

            scans[i].crawl_time_end = date;

            // add scans to buckets for each url they were preformed on
            if (!scanURLS.includes(scans[i].starturl)) {
                scanURLS.push(scans[i].starturl);
                scansHash[scans[i].starturl] = [scans[i]];
            } else {
                scansHash[scans[i].starturl].push(scans[i]);
            }
        }

        // sort the scan by their date
        let allScanByDate = scans.sort((a,b) => (a.crawl_time_end < b.crawl_time_end) ? 1 :
            ((b.crawl_time_end < a.crawl_time_end) ? -1 : 0));

        // go through each scan
        for (let i=0;i<scanURLS.length;i++) {
            // sort the url the have multiple scans by crawl end time in DESC order
            // Note: they should already come in order, this is just in case
            if ( Object.keys(scansHash[scanURLS[i]]).length > 1 ) {
                scansHash[scanURLS[i]].sort((a,b) =>
                    (a.crawl_time_end < b.crawl_time_end) ? 1 : ((b.crawl_time_end < a.crawl_time_end) ? -1 : 0));
            }
            // add the most recent scan to allScanMostRecent
            allScanMostRecent.push(scansHash[scanURLS[i]][0]);
        }

        // get the files from scans
        let files = [];
        /*let filesReq = new XMLHttpRequest()
        filesReq.open("GET", "scanAPI.php?action=getFiles&crawl=" + scansHash[scanURLS[0]][0].ID, true);
        filesReq.onreadystatechange = function () {
            if (this.readyState === 4 && this.status === 200) {
                files = JSON.parse(this.responseText);
                for (let i = 0; i < files.length; i++) {
                    if (files[i].Tagged === "1") {
                        files[i].Tagged = "true";
                    }
                    if (files[i].Tagged === "0") {
                        files[i].Tagged = "false";
                    }
                }
            }
            if (this.readyState === 4 && this.status !== 200) {
                files = [];
                console.error('Error: Something went wrong getting files data for crawl ' + scansHash[scanURLS[0]][0].ID + '. HTTP status ' + this.status);
            }
        };
        filesReq.send();*/
        window.setInterval(function(){

            if ($("#litescan").css('display') === 'none') {
                if ($('#sideNav-ul').height() < $("#proscan").height()) {
                    $('#sideNav-ul').height($("#proscan").height());
                }
            } else {
                if ($('#sideNav-ul').height() < $("#litescan").height()) {
                    $('#sideNav-ul').height($("#litescan").height());
                }
            }
        }, 500);

    </script>
    <script id="charts">
        $(document).ready(function () {

            // listener for menu button when in mobile
            let timer = 350;
            $("#aside-tab").on('mouseover click focus', (e) => {
                if ($("#sideNav").css('display') === 'none') {
                    $("#sideNav").show("slide", { direction: "left" }, timer);
                } else {
                    if (e.type === "mouseover") {
                        $("#sideNav").hide("slide", {direction: "left"}, timer);
                    }
                }
            });

            $("main").on('mouseover click', () => {
                if ($("#sideNav").css('display') !== 'none') {
                    $("#sideNav").hide("slide", { direction: "left" }, timer);
                }
            });

            $("#litescan").on('mouseover click', () => {
                if ($("#sideNav").css('display') !== 'none') {
                    $("#sideNav").hide("slide", { direction: "left" }, timer);
                }
            });

            $("#hamburger-menu").on('click', () => {
                if ($("#sideNav").css('display') === 'none') {
                    $("#sideNav").show("slide", { direction: "left" }, timer);
                } else {
                    $("#sideNav").hide("slide", { direction: "left" }, timer);
                }
            });

            $("aside").css('height', $("main").height() + "px");

            let scanResultChart = document.querySelector("#scan-result-chart").getContext('2d');

            // colours for the charts w/ patter
            /*let untaggedColor = pattern.draw('line', "rgba(255, 0, 0, 0.7)");
            let taggedColor = pattern.draw('line-vertical', "rgba(255,153,0,0.7)");
            let compliantColor = pattern.draw('weave', "rgba(0, 255, 0, 0.7)");*/

            let untaggedColor = "#FA483A";
            let taggedColor = "#FFC64C";
            let compliantColor = "#3CC945";

            resChart = new Chart(scanResultChart, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [topData.compliant, topData.nonCompliant, topData.untagged],
                        backgroundColor: [compliantColor, taggedColor, untaggedColor],
                        borderColor: 'black'
                    }],
                    labels: ['Compliant', 'Tagged Non-Compliant', 'Untagged']
                },
                options: {
                    legend: {
                        display: false,
                    },
                    responsive: true,
                    maintainAspectRatio: true,
                }
            });

            let compHistoryChart = document.querySelector("#comp-history-chart").getContext('2d');
            let graphBorder = "rgb(130,130,130)";
            historyChart = new Chart(compHistoryChart, {
                type: 'bar',
                data: {
                    datasets: [
                        {
                            label: "Untagged",
                            data : [],
                            order: 1,
                            backgroundColor: untaggedColor,
                            borderColor: graphBorder
                        },
                        {
                            label: "Non-Compliant",
                            data : [],
                            order: 2,
                            backgroundColor: taggedColor,
                            borderColor: graphBorder
                        },
                        {
                            label: "Compliant",
                            data : [],
                            order: 3,
                            backgroundColor: compliantColor,
                            borderColor: graphBorder
                        },
                    ],
                    labels: []
                },
                options: {
                    scales: {
                        yAxes: [{
                            stacked: true,
                            display: true,
                            ticks: {
                                suggestedMin: 0,
                                suggestedMax: 100,
                                beginAtZero: true,
                                max: 100,
                                fontSize: 16,
                                fontColor: 'black'
                            }
                        }],
                        xAxes: [{
                            stacked: true,
                            ticks : {
                                fontColor: 'black'
                            }
                        }]
                    },
                    legend: {
                        labels: {
                            // This more specific font property overrides the global property
                            fontColor: 'black',
                            fontSize: 16
                        }
                    }
                }
            });

            // update the compliance history chart
            showOverallHistory();

            // hide all showfiles links
            $(".showFiles").hide();

        });
    </script>
</head>
<body>
<header id="main-app-header">
    <div class="row">
        <div id="logo-container" class="col-6">
            <img src="images/ADScan_RGB.png" alt="AD scan logo"/>
        </div>
        <div class="col-6" id="user-controls-container">
            <p style="display:inline-block">{{ username }}</p role>
            <button id="users-btn" type="button" onclick="usersDropdown()" aria-label="User Account" aria-expanded="false" class="btn btn-light">
                <i style="pointer-events: none;" class="fa fa-user"></i>&nbsp;<i class="fa fa-caret-down"></i>
            </button>
            <div class="dropdown-users dropdown-content">
                <a tabindex="1" class="translate" data-key="logout" href="login/login.php">Logout</a>
                <a tabindex="1" class="translate" data-key="changePass" onclick="changePassword()">Change Password</a>
                <a tabindex="1" class="translate" data-key="changeLang" onclick="getnewLang()">Change Language</a>
                <a tabindex="1" class="translate" data-key="inviteUser" v-if="client.isOwner" onclick="inviteNewUser()">Invite new user</a>
                <a tabindex="1" class="translate" data-key="backToAdmin" v-if="client.companyName === 'AbleDocs'" href="master.php">Back to Admin</a>
            </div>
        </div>
    </div>
    <div id="client-title" class="row">
        <h1 id="client-name">{{ companyName }}</h1>
    </div>
</header>
<section tabindex="0" aria-label="slide out tab for menu" id="aside-tab">
    <i style="pointer-events:none" class="fa fa-chevron-right"></i>
</section>
<aside id="sideNav">
    <nav>
        <template>
            <ul id="sideNav-ul">
                <li><a id="dashboardLink" class="translate" data-key="dashboard" onclick="SwitchOverallDash()" href="javascript:void(0);">Dashboard</a></li>
                <li id="overviewLink" class="nav-active"><a v-on:click="clickedDomain($event, 'Overview')" class="translate" data-key="overview" href="javascript:void(0);">Overview</a></li>
                <hr>
                <li v-for="(url, index) in urls">
                    <a v-on:click="clickedDomain($event, url)" v-bind:id="'sidenav-' + index" href="javascript:void(0);">{{ url.split("//")[1] }}</a>
                </li>
            </ul>
        </template>
    </nav>
</aside>
<main id="proscan">
    <div class="row">
        <div id="crawl-info-wrapper" class="col-md-3">
            <div id="crawl-info" class="dash-card">
                <p><span class="translate" data-key="crawlID"><Crawl ID>,</span>: {{ ID }}</p>
                <p><span class="translate" data-key="startURL">Starting URL</span>: {{ start }}</p>
                <p><span class="translate" data-key="ScanInit">Scan initiated</span>: {{ date }}</p>
                <p><span class="translate" data-key="pdfFilesFound">PDF files scanned</span>: {{ total }}</p>
                <button id="pro-exportCSV-btn" onclick="exportCsv(crawlInfo.ID)" data-key="Exportcsv" class="translate btn btn-outline-dark">Export CSV</button>
            </div>
        </div>
        <div id="overall-compliance-wrapper" class="col-md-9">
            <div class="dash-card">
                <div id="overall-header" class="content-header row">
                    <p><span class="translate" data-key="overallComp">Overall Compliance Statistics as of</span>&nbsp;<span>{{ date }}</span></p>
                </div>
                <div id="data-row" class="row">
                    <div id="pie-chart-container" class="col-md-4">
                        <canvas id="scan-result-chart"></canvas>
                        <p v-if="averageUA !== null" id="scan-ua"><span class="translate" data-key="index">UA Index</span>: {{ averageUA }}</p>
                        <p v-else id="scan-ua">UA <span class="translate" data-key="index">Index</span>: N/A</p>
                    </div>
                    <div id="document-counter" class="col-md-8">
                        <div class="row showFiles">
                            <a href="javascript:;" id="showallfiles" @click="showFiles('all')"><span class="translate" data-key="showFiles">Show all files</span> ({{ compliant + nonCompliant + untagged + offsite }})</a>
                        </div>
                        <div class="row legend-data">
                            <div class="col-4">
                                <div class="color-legend" style="background-color: #3CC945"></div>
                                <!--<div class="color-legend" style="background-color: #9ed0f6"></div>-->
                                <p class="translate" data-key="compliant">Compliant</p>
                            </div>
                            <div class="col-4">
                                <div class="color-legend" style="background-color: #FFC64C"></div>
                                <!--<div class="color-legend" style="background-color: #0a78c2"></div>-->
                                <p class="translate" data-key="nonComp">Non&#8209;Compliant</p>
                            </div>
                            <div class="col-4">
                                <div class="color-legend" style="background-color: #FA483A"></div>
                                <!--<div class="color-legend" style="background-color: #242056"></div>-->
                                <p class="translate" data-key="untagged">Untagged</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-4">
                                <p v-if="compliant !== null" class="pdf-counter">{{ compliant }}</p>
                                <p v-else class="pdf-counter">0</p>
                                <a href="javascript:;" aria-label="Show compliant files" class="translate showFiles" id="showcompfiles" data-key="showFiles" @click="showFiles('compliant')">Show files</a>
                            </div>
                            <div class="col-4">
                                <p v-if="nonCompliant !== null" class="pdf-counter">{{ nonCompliant }}</p>
                                <p v-else class="pdf-counter">0</p>
                                <a href="javascript:;" aria-label="Show non compliant files" class="translate showFiles" id="shownoncompfiles" data-key="showFiles" @click="showFiles('nonCompliant')">Show files</a>
                            </div>
                            <div class="col-4">
                                <p v-if="untagged !== null" class="pdf-counter">{{ untagged }}</p>
                                <p v-else class="pdf-counter">0</p>
                                <a href="javascript:;" aria-label="Show untagged files" class="translate showFiles" id="showuntaggedfiles" data-key="showFiles" @click="showFiles('untagged')">Show files</a>
                            </div>
                        </div>
                        <div class="row legend-data">
                            <div class="col-3">
                                <div class="color-legend" style="background-color: black"></div>
                                <p class="translate" data-key="offsite">Off Site</p>
                            </div>
                            <div class="col-3" style="padding-left: 15px !important;">
                                <p v-if="offsite !== null" class="pdf-counter">{{ offsite }}</p>
                                <p v-else class="pdf-counter">0</p>
                                <a href="javascript:;" @click="showFiles('offsite')" class="translate showFiles" id="showoffsitefiles" data-key="showFiles">Show files</a>
                            </div>
                            <div class="col-3">
                                <div class="color-legend" style="background-color: white"></div>
                                <p class="translate" data-key="fileErrors">File Errors</p>
                            </div>
                            <div class="col-3" style="padding-left: 15px !important;">
                                <p class="pdf-counter">{{ errors }}</p>
                                <a href="javascript:;" @click="showFiles('errors')" class="translate showFiles" id="showerrorfiles" data-key="showFiles">Show files</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="bottom-data-section" class="row">
        <div id="history-graph-container" class="col-md-7">  <!-- compliance history graph -->
            <div class="dash-card">
                <div class="row content-header">
                    <p class="translate" data-key="compHistory">Compliance History</p><span id="historyGraphURL"></span>
                </div>
                <canvas id="comp-history-chart"></canvas>
            </div>
        </div>
        <div id="bottom-data-right-side" class="col-md-5">
            <div class="row">  <!-- performance ranges -->
                <div id="preformance-ranges" style="width: 100%;" class="dash-card">
                    <div style="width:100%" class="content-header">
                        <p class="translate" data-key="prefRange">Performance Ranges</p>
                    </div>
                    <div class="row" id="performers-data">
                        <div class="col-6">
                            <p style="text-decoration: underline;font-weight: bold" class="translate" data-key="bestPref">Best Performer</p>
                            <p tabindex="0" v-on:click="showThisScan(true)" style="color: #6161FF;cursor: pointer;">{{ bestScanURL }}</p>
                            <!--<p class="pdf-counter">{{ bestScanPreformance }}%</p>-->
                            <br/>

                            <p v-if="bestScanUA !== null"  style="font-weight: bold"><span class="translate" data-key="index">Index</span>: {{ bestScanUA }}</p>
                            <p v-else style="font-weight: bold"><span class="translate" data-key="index">Index</span>: 0</p>

                            <p><span class="translate" data-key="compliant">Compliant</span>: {{ bestScanComp }}</p>
                            <p><span class="translate" data-key="nonComp">Non Compliant</span>: {{ bestScanNonComp }}</p>
                            <p><span class="translate" data-key="untagged">Untagged</span>: {{ bestScanUntagged }}</p>
                            <!--<p><span class="translate" data-key="offsite">Offsite</span>: {{ bestScanOffsite }}</p>-->
                        </div>
                        <div class="col-6">
                            <p style="text-decoration: underline;font-weight: bold" class="translate" data-key="worstPref">Worst Performer</p>
                            <p tabindex="0" v-on:click="showThisScan(false)" style="color: #6161FF; cursor: pointer;">{{ worstScanURL }}</p>
                            <br/>
                            <!--<p class="pdf-counter">{{ worstScanPreformance }}%</p>-->

                            <p v-if="worstScanUA !== null" style="font-weight: bold"><span class="translate" data-key="index">Index</span>: {{ worstScanUA }}</p>
                            <p v-else style="font-weight: bold"><span class="translate" data-key="index">Index</span>: 0</p>

                            <p><span class="translate" data-key="compliant">Compliant</span>: {{ worstScanComp }}</p>
                            <p><span class="translate" data-key="nonComp">Non Compliant</span>: {{ worstScanNonComp }}</p>
                            <p><span class="translate" data-key="untagged">Untagged</span>: {{ worstScanUntagged }}</p>
                            <!--  <p><span class="translate" data-key="offsite">Offsite</span>: {{ worstScanOffsite }}</p>-->
                        </div>
                    </div>
                </div>
            </div>
            <div id="scan-history-table-container" class="row">  <!-- Scan history -->
                <div style="width: 100%" class="dash-card">
                    <div class="content-header">
                        <p class="translate" data-key="scanHist" style="display: inline-block !important;">Scan History</p>
                        <!--<a id="new-scan-btn" class="btn btn-sm" href="#">New Scan</a>-->
                    </div>
                    <table id="scan-history-table" class="table table-hover">
                        <tr v-for="scan in scans">
                            <td>{{ getDate(scan) }}</td>
                            <td>{{ getURL(scan) }}</td>
                            <td><a style="color: #006ADB" href="#" @click="viewResults(scan)"><span class="translate" data-key="viewResults">{{ langLabel }}</span></a></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div id="file-viewer" class="row">
            <div class="dash-card">
                <div class="row" style="padding-top: 8px;">
                    <div class="col-md-4" style="padding: 8px;">
                        <div class="btn-group btn-block">
                            <b-button size="sm" id="selectall" data-key="selectAll" class="translate" variant="outline-secondary w-50" @click="selectAllRows">Select all</b-button>
                            <b-button size="sm" id="clearall" data-key="clearAll" class="translate" variant="outline-secondary w-50" @click="clearSelected">Clear selected</b-button>
                        </div>
                        <div class="btn-group btn-block">
                            <b-button size="sm" variant="outline-secondary w-50" @click="clearFilters">Clear Filters</b-button>
                            <b-button size="sm" data-key="showNon" class="translate w-50" variant="outline-secondary" @click="showNonCompAndUntagged">Show Non-compliant and Untagged</b-button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <span id="fileShowingStatus"><span class="translate" data-key="showing">Showing</span>: {{ status }}</span>
                        <br/>
                    </div>
                    <div class="col-md-4">
                        <!--<div class="dropdown">
                            <button id="action-menu-btn" onclick="openOptionMenu()" data-key="chooseAction" class="translate dropbtn">Choose an Action</button>
                            <div id="dropdown-content" class="dropdown-content" aria-expanded="false">
                                <a role="button" tabindex="1" class="translate" data-key="sendToAble" onclick="sendForRemediation(fileViewer.$refs.grid.getSelectedRecords(), client)" href="javascript:void(0);">Send to AbleDocs</a>
                                <a role="button" tabindex="1" data-key="downloadSelect" class="translate" onclick="downloadMultiplePDF(fileViewer.$refs.grid.getSelectedRecords())">Download Selected Files</a>
                                <a role="button" tabindex="1" data-key="Exportcsv" class="translate" onclick="exportCsv(topData.scanID)">Export to .csv</a>
                            </div>
                        </div>-->

                        <template>
                            <ejs-dropdownbutton ref="chooseActionDropdown" :items='dropdownItems' :select="actionSelect"><span class="translate" data-key="chooseAction">Choose an Action </span></ejs-dropdownbutton>
                        </template>

                    </div>
                </div>
                <div class="row">
                    <div id="top-scroll-wrapper-pro">
                        <div id="top-scroll-pro"></div>
                    </div>

                    <template>
                        <ejs-grid id="files-table-pro" :data-source="files" :allow-paging="true" :searchSettings='searchOptions' :page-settings='pageSettings' ref='grid'
                                  :allow-sorting="true" :allow-selection="true" :selection-settings='selectionOptions'
                                  :allow-resizing='true' :resize-stop="resizeColumn" :show-column-chooser='true' :toolbar='toolbarOptions'
                                  :allow-filtering="true" :filter-settings='filterOptions' :action-begin="dataStateChange" :header-cell-info="headerCellInfoEvent" :locale='lang' >
                            <e-columns>
                                <e-column type='checkbox' width='50'></e-column>
                                <e-column field='ID' header-txt='ID' :allow-searching=false :show-in-column-chooser=true :visible=false :is-primary-key='true'></e-column>
                                <e-column field='filename' header-text='Name' type="string" :template="nameTemplate" width="450"></e-column>
                                <e-column field='UA_Index' header-text='UA Index' :sort-comparer='sortNullAtBottom' text-align='Right' :template='uaTemplate' width="180"></e-column>
                                <e-column field='url' header-text='URL' :is-primary-key='true' :filter="urlFilter" width="350"></e-column>
                                <e-column field='NumPages' header-text='Page Count' text-align='Right' type="number" :sort-comparer='sortNullAtBottom' width="200"></e-column>
                                <e-column field='Tagged' header-text='Tagged' :template="taggedTemplate" :filter="taggedFilter" width="170"></e-column>
                                <e-column field='Title' header-text='Title' type="string" :sort-comparer='sortNullAtBottom' width="200"></e-column>
                                <e-column field='Creator' header-text='Application' width="200" :filter="filterCheckBox" :sort-comparer='sortNullAtBottom'></e-column>
                                <e-column field='Producer' header-text='Producer' width="200" :filter="filterCheckBox" :sort-comparer='sortNullAtBottom' ></e-column>
                                <e-column field='CreationDate' header-text='Creation Date' :format='dateFormat' :filter="createdDateFilter" width="150"></e-column>
                                <e-column field='ModDate' header-text='Last Modified' :format='dateFormat' :filter="modDateFilter" width="200"></e-column>
                                <e-column field='Lang' header-text='Language' width="190" :filter="filterCheckBox" :sort-comparer='sortNullAtBottom'></e-column>
                                <e-column field='FileSize' header-text='File Size' type="number" text-align='Right' :sort-comparer='sortNullAtBottom' width="150"></e-column>
                                <e-column field='offsite' header-text='Off Site' text-align='Right' :filter="offsiteFilter" :template="offsiteTemplate" width="150"></e-column>
                                <e-column field='passed' header-text='Passed' type="number" text-align='Right' :sort-comparer='sortNullAtBottom' width="150"></e-column>
                                <e-column field='Warnings' header-text='Warnings' type="number" text-align='Right' :sort-comparer='sortNullAtBottom' width="150"></e-column>
                                <e-column field='failed' header-text='Failed' type="number" text-align='Right' :sort-comparer='sortNullAtBottom' width="150"></e-column>
                            </e-columns>
                        </ejs-grid>
                    </template>
                </div>
            </div>
        </div>
    </div>
</main>
<section id="litescan">

    <div id="lite-header">

        <div class="row">
            <div class="col-lg-4 lite-dash-card">

                <p><span class="translate" data-key="crawlID">Crawl ID,</span>: {{ scan.ID }}</p>
                <p><span class="translate" data-key="startURL">Starting URL</span>: {{ scan.starturl }}</p>
                <p><span class="translate" data-key="ScanInit">Scan initiated</span>: {{ scan.crawl_time_end.toISOString().split('T')[0] }}</p>
                <p><span class="translate" data-key="pdfFilesFound">PDF files scanned</span>: {{ files.length }}</p>
                <button id="exportCSV-lite" onclick="exportCsv(liteScan.scan.ID)" data-key="Exportcsv" class="translate btn btn-outline-dark">Export CSV</button>
            </div>
            <div id="lite-fileCounts" class="col-lg-7 lite-dash-card">
                <div class="row legend-data">
                    <div class="col-4">
                        <div class="color-legend" style="background-color: #3CC945"></div>
                        <!--<div class="color-legend" style="background-color: #9ed0f6"></div>-->
                        <p class="translate" data-key="compliant">Compliant</p>
                    </div>
                    <div class="col-4">
                        <div class="color-legend" style="background-color: #FFC64C"></div>
                        <!--<div class="color-legend" style="background-color: #0a78c2"></div>-->
                        <p class="translate" data-key="nonComp">Non&#8209;Compliant</p>
                    </div>
                    <div class="col-4">
                        <div class="color-legend" style="background-color: #FA483A"></div>
                        <!--<div class="color-legend" style="background-color: #242056"></div>-->
                        <p class="translate" data-key="untagged">Untagged</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-4">
                        <p v-if="scan.compliant !== null" class="pdf-counter">{{ scan.compliant }}</p>
                        <p v-else class="pdf-counter">0</p>
                    </div>
                    <div class="col-4">
                        <p v-if="scan.nonCompliant !== null" class="pdf-counter">{{ scan.nonCompliant }}</p>
                        <p v-else class="pdf-counter">0</p>
                    </div>
                    <div class="col-4">
                        <p v-if="scan.untagged !== null" class="pdf-counter">{{ scan.untagged }}</p>
                        <p v-else class="pdf-counter">0</p>
                    </div>
                </div>
                <div class="row legend-data">
                    <div class="col-3">
                        <div class="color-legend" style="background-color: black"></div>
                        <p class="translate" data-key="offsite">Off Site</p>
                    </div>
                    <div class="col-3" style="padding-left: 15px !important;">
                        <p v-if="scan.offsiteFiles !== null" class="pdf-counter">{{ scan.offsiteFiles }}</p>
                        <p v-else class="pdf-counter">0</p>
                    </div>
                    <div class="col-3">
                        <div class="color-legend" style="background-color: white"></div>
                        <p class="translate" data-key="fileErrors">File Errors</p>
                    </div>
                    <div class="col-3" style="padding-left: 15px !important;">
                        <p class="pdf-counter">{{ scan.Files_Error }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="top-scroll-wrapper">
        <div id="top-scroll"></div>
    </div>
    <template>
        <ejs-grid :data-source="files" ref="liteGrid" :allow-paging="true"
                  :page-settings='pageSettings' :allow-sorting='true' :action-begin="liteTableStateChange">
            <e-columns>
                <e-column field='ID' header-txt='ID' :visible=false :is-primary-key='true'></e-column>
                <e-column field="filename" header-text="Name" width=275 text-align="left" :template="filenameTemplate"></e-column>
                <e-column field="UA_Index" header-text="UA Index" width=150 text-align="right" :template='uaTemplate'></e-column>
                <e-column field="NumPages" header-text="Page Count" width=180 text-align="right"></e-column>
                <e-column field="Tagged" header-text="Tagged" width=125 text-align="left" :template="taggedTemplate"></e-column>
                <e-column field="Title" header-text="Tile" width=230 text-align="left"></e-column>
                <e-column field="passed_check" header-text="Passed" width=110 text-align="right"></e-column>
                <e-column field="warned_check" header-text="Warned" width=110 text-align="right"></e-column>
                <e-column field="failed_check" header-text="Failed" width=110 text-align="right"></e-column>
            </e-columns>
        </ejs-grid>
    </template>

</section>

<!-- contains Vue instances -->
<script src="js/dashboard.js" defer></script>
<!-- Ensures the page loads in the correct language -->
<script>
    $( document ).ready(function() {
        // ensure theres no gap at the bottom
        $("aside").css('height', 'auto');
        changeLang(currentLang);
    });
</script>

</body>
</html>