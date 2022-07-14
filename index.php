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
    <title>ADScan</title>

    <!-- Recently Added Start -->
    <!-- Bootstrap CSS CDN -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Our Custom CSS -->

    <link rel="stylesheet" href="css/sidebar/style.css">
    <link rel="stylesheet" href="css/main-dashboard/style.css">

    <!-- Font Awesome JS -->
    <link media="all" type="text/css" rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.1.1/css/all.css">

    <!-- Recently Added End -->

    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico">

    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css" />
    <link type="text/css" rel="stylesheet" href="//unpkg.com/bootstrap-vue@latest/dist/bootstrap-vue.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat&family=Raleway&display=swap" rel="stylesheet">

    <script src="js/jquery.min.js"></script>
    <script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>

    <!-- Chert.js is for creating the charts in dashboard and patternomaly creates the pattern on the colours -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>

    <!-- development version, includes helpful console warnings -->
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>

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
        let scanHistory = [];

        // go through every scan
        for (let i = 0; i < scans.length; i++) {
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
        let allScanByDate = scans.sort((a, b) => (a.crawl_time_end < b.crawl_time_end) ? 1 :
            ((b.crawl_time_end < a.crawl_time_end) ? -1 : 0));

        // go through each scan
        for (let i = 0; i < scanURLS.length; i++) {
            // sort the url the have multiple scans by crawl end time in DESC order
            // Note: they should already come in order, this is just in case
            if (Object.keys(scansHash[scanURLS[i]]).length > 1) {
                scansHash[scanURLS[i]].sort((a, b) =>
                    (a.crawl_time_end < b.crawl_time_end) ? 1 : ((b.crawl_time_end < a.crawl_time_end) ? -1 : 0));
            }
            // add the most recent scan to allScanMostRecent
            allScanMostRecent.push(scansHash[scanURLS[i]][0]);

            if (sessionStorage.getItem('fromMaster') !== null) {
                scanHistory = scans.find(c => c.ID === parseInt(sessionStorage.getItem('fromMaster')));
            }
        }

        // get the files from scans
        let files = [];
    </script>
    <script id="charts">
        $(document).ready(function() {

            let scanResultChart = document.querySelector("#scan-result-chart").getContext('2d');

            // colours for the charts w/ patter

            let untaggedColor = "#55afc7";
            let taggedColor = "#FFC64C";
            let compliantColor = "#ba0068";

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
                    datasets: [{
                            label: "Untagged",
                            data: [],
                            order: 1,
                            backgroundColor: untaggedColor,
                            borderColor: graphBorder
                        },
                        {
                            label: "Non-Compliant",
                            data: [],
                            order: 2,
                            backgroundColor: taggedColor,
                            borderColor: graphBorder
                        },
                        {
                            label: "Compliant",
                            data: [],
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
                            ticks: {
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

                <!-- ProScan Start -->
                <div class="container" id="proscan">
                    <div class="row">
                        <!-- CRAWLS Details -->
                        <div class="col-lg-12">
                            <div class="main-dash-card">
                                <div class="card-top">
                                    <div class="row">
                                        <div class="col-lg-4" id="crawl-info-wrapper">
                                            <div class="details" id="crawl-info">
                                                <h5>
                                                    <span class="translate" data-key="crawlID">Crawl ID</span>: {{ ID }}
                                                </h5>
                                                <h6><span class="translate" data-key="startURL">Starting URL</span>: {{ start }}</h6>
                                                <h6>
                                                    <span class="translate" data-key="ScanInit">Scan initiated</span>: {{ date }}
                                                </h6>
                                                <h6>
                                                    <span class="translate" data-key="pdfFilesFound">PDF files scanned</span>: {{ total }}
                                                </h6>
                                            </div>
                                        </div>
                                        <div class="col-lg-8">
                                            <br>
                                            <h5>
                                                Search results for:
                                            </h5>
                                            <div class="show-urls">
                                                <select name="" id="" class="form-control">
                                                    <option value="">xn--brnepasning-ggb.esbjerg.dk</option>
                                                </select>
                                                <div class="dropdoen-btn">
                                                    Show all URL'S
                                                    <i class="fa fa-arrow-right"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="result-section" id="data-row">
                                    <div class="result-top" id="overall-header">
                                        <div>
                                            <h5>
                                                <b> Compliance results:</b> {{ date }} <a href=""> Show full scan history</a>
                                            </h5>
                                        </div>
                                        <div>
                                            <button class="translate btn btn-export" onclick="exportCsv(crawlInfo.ID)">
                                                <span data-key="Exportcsv">Export results</span> <i class="fa fa-download"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-3">
                                            <div class="graph">
                                                <canvas id="scan-result-chart"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-lg-9">
                                            <div class="row">
                                                <div class="col-lg-2">
                                                    <div class="total">
                                                        <div class="content">
                                                            <h5>
                                                                Files in total
                                                            </h5>
                                                            <h1>
                                                                ({{ compliant + nonCompliant + untagged + offsite }})
                                                            </h1>
                                                            <button class="translate btn" id="showallfiles" @click="showFiles('all')" data-key="showFiles">
                                                                Show Files
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="Compliant">
                                                        <div class="row">
                                                            <div class="col-lg-4">
                                                                <div class="content">
                                                                    <h5>
                                                                        <div class="dott" style="background-color:#00568E;">
                                                                        </div>
                                                                        <span class="translate" data-key="compliant">
                                                                            Compliant
                                                                        </span>
                                                                    </h5>
                                                                    <h1>
                                                                        {{ compliant ? compliant : 0 }}
                                                                    </h1>
                                                                    <button class="translate btn" id="showcompfiles" data-key="showFiles" @click="showFiles('compliant')">
                                                                        Show Files
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-4">
                                                                <div class="content">
                                                                    <h5>
                                                                        <div class="dott" style="background-color:#BA0068;">
                                                                        </div>
                                                                        <span class="translate" data-key="nonComp">
                                                                            Non-Compliant
                                                                        </span>
                                                                    </h5>
                                                                    <h1>
                                                                        {{ nonCompliant ? nonCompliant : 0 }}
                                                                    </h1>
                                                                    <button class="translate btn" id="shownoncompfiles" data-key="showFiles" @click="showFiles('nonCompliant')">
                                                                        Show Files
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-4">
                                                                <div class="content">
                                                                    <h5>
                                                                        <div class="dott" style="background-color:#55AFC7;">
                                                                        </div>
                                                                        <span class="translate" data-key="untagged">
                                                                            Untagged
                                                                        </span>
                                                                    </h5>
                                                                    <h1>
                                                                        {{ untagged ? untagged : 0 }}
                                                                    </h1>
                                                                    <button class="translate btn" id="showuntaggedfiles" data-key="showFiles" @click="showFiles('untagged')">
                                                                        Show Files
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="Errors">
                                                        <div class="row">
                                                            <div class="col-lg-6">
                                                                <div class="content">
                                                                    <h5 class="translate" data-key="offsite">
                                                                        Off Side Files
                                                                    </h5>
                                                                    <h1>
                                                                        {{ offsite ? offsite : 0 }}
                                                                    </h1>
                                                                    <button class="translate btn" id="showoffsitefiles" data-key="showFiles" @click="showFiles('offsite')">
                                                                        Show Files
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="content">
                                                                    <h5 class="translate" data-key="fileErrors">
                                                                        File Errors
                                                                    </h5>
                                                                    <h1>
                                                                        {{ errors }}
                                                                    </h1>
                                                                    <button class="translate btn" id="showerrorfiles" data-key="showFiles" @click="showFiles('errors')">
                                                                        Show Files
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="result-footer">
                                        <div class="row">
                                            <div class="col-lg-3">
                                                <div class="footer-link">
                                                    <a href="">
                                                        What is the UA index?
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="col-lg-9">
                                                <div class="footer-groth">
                                                    <div class="f-content">
                                                        <h5>
                                                            <div class="dott" style="background-color:#00568E;"></div>
                                                            <span class="translate" data-key="compliant">Compliant</span> <!-- <b>48%</b> -->
                                                        </h5>
                                                    </div>
                                                    <div class="f-content">
                                                        <h5>
                                                            <div class="dott" style="background-color:#BA0068;"></div>
                                                            <span class="translate" data-key="nonComp">Non-Compliant</span> <!-- <b>23%</b> -->
                                                        </h5>
                                                    </div>
                                                    <div class="f-content">
                                                        <h5>
                                                            <div class="dott" style="background-color:#55AFC7;"></div>
                                                            <span class="translate" data-key="untagged">Untagged</span> <!-- <b>39%</b> -->
                                                        </h5>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- CRAWLS Details -->

                        <!-- Table -->
                        <div class="col-lg-12" id="file-viewer">
                            <div class="latest-scan-card">
                                <div class="result-top">
                                    <div>
                                        <h5>
                                            <b class="translate" data-key="results">Results: </b>{{ status }}
                                        </h5>
                                    </div>
                                </div>
                                <div class="top-filter">
                                    <div class="filter-box">
                                        <!-- <button class="btn filter-btn">
                                            <i class="fa fa-plus"></i> Add filter
                                        </button>
                                        <button class="btn pills-btn">
                                            Date <i class="fa fa-close"></i>
                                        </button>
                                        <button class="btn pills-btn">
                                            Compliant <i class="fa fa-close"></i>
                                        </button> -->
                                    </div>
                                    <div class="right-btns">
                                        <div class="dropdown">
                                            <button data-key="chooseAction" class="translate btn btn-export dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                Choose an action
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <a role="button" tabindex="1" class="dropdown-item translate" data-key="sendToAble" onclick="sendForRemediation(fileViewer.$refs.grid.getSelectedRecords(), client)" href="javascript:void(0);">Send to AbleDocs</a>
                                                <a role="button" tabindex="1" data-key="downloadSelect" class="dropdown-item translate" onclick="downloadMultiplePDF(fileViewer.$refs.grid.getSelectedRecords())">Download Selected Files</a>
                                                <a role="button" tabindex="1" data-key="Exportcsv" class="dropdown-item translate" onclick="exportCsv(topData.scanID)">Export to .csv</a>
                                            </div>
                                        </div>

                                        <button class="btn btn-export" onclick="exportCsv(crawlInfo.ID)">
                                            <span class="translate" data-key="Exportcsv"> Export results </span> <i class="fa fa-download"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <div class="col-lg-3" style="display: flex; margin-bottom: 5px; padding: 0px !important">
                                        <b-button style="max-width: 115px !important; margin-right: 5px" size="sm" id="selectall" data-key="selectAll" class="translate" variant="outline-secondary w-50" @click="selectAllRows">Select all</b-button>
                                        <b-button style="max-width: 115px !important;" size="sm" id="clearall" data-key="clearAll" class="translate" variant="outline-secondary w-50" @click="clearSelected">Clear selected</b-button>
                                    </div>
                                    <template>
                                        <ejs-grid id="files-table-pro" :data-source="files" :allow-paging="true" :page-settings='pageSettings' ref='grid' :allow-sorting="true" :allow-selection="true" :selection-settings='selectionOptions' :allow-resizing='true' :resize-stop="resizeColumn" :show-column-chooser='true' :toolbar='toolbarOptions' :allow-filtering="true" :filter-settings='filterOptions' :action-begin="dataStateChange" :header-cell-info="headerCellInfoEvent" :locale='lang'>
                                            <e-columns>
                                                <e-column type='checkbox' width='50'></e-column>
                                                <e-column field='ID' header-text='ID' :show-in-column-chooser=false :visible=false :is-primary-key='true'></e-column>
                                                <e-column field='filename' header-text='Name' type="string" :template="nameTemplate" width="450"></e-column>
                                                <e-column field='UA_Index' header-text='UA Index' :sort-comparer='sortNullAtBottom' text-align='Right' type="number" :template='uaTemplate' width="180"></e-column>
                                                <e-column field='url' header-text='URL' :filter="urlFilter" width="350"></e-column>
                                                <e-column field='NumPages' header-text='Page Count' text-align='Right' type="number" :sort-comparer='sortNullAtBottom' width="200"></e-column>
                                                <e-column field='Tagged' header-text='Tagged' :template="taggedTemplate" :filter="taggedFilter" width="170"></e-column>
                                                <e-column field='Title' header-text='Title' type="string" :sort-comparer='sortNullAtBottom' width="200"></e-column>
                                                <e-column field='Creator' header-text='Application' width="200" :filter="filterCheckBox" :sort-comparer='sortNullAtBottom'></e-column>
                                                <e-column field='Producer' header-text='Producer' width="200" :filter="filterCheckBox" :sort-comparer='sortNullAtBottom'></e-column>
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
                        <!-- Table -->

                        <!-- History Graph -->
                        <div class="col-lg-5" id="history-graph-container">
                            <div class="Compliance-card">
                                <div class="result-top">
                                    <div>
                                        <h5>
                                            <b>Compliance History:</b><a href=""> Show full Compliance History</a>
                                        </h5>
                                    </div>
                                </div>
                                <div class="graph-box">
                                    <canvas id="comp-history-chart"></canvas>
                                </div>
                                <div class="result-footer">
                                    <div class="footer-groth">
                                        <div class="f-content">
                                            <h5>
                                                <div class="dott" style="background-color:#00568E;"></div>
                                                Compliant
                                            </h5>
                                        </div>
                                        <div class="f-content">
                                            <h5>
                                                <div class="dott" style="background-color:#BA0068;"></div>
                                                Non-Compliant
                                            </h5>
                                        </div>
                                        <div class="f-content">
                                            <h5>
                                                <div class="dott" style="background-color:#55AFC7;"></div>
                                                Untagged
                                            </h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Scan History Table -->
                        <div class="col-lg-7" id="scan-history-table-container">
                            <div class="latest-scan-card">
                                <div class="result-top">
                                    <div>
                                        <h5>
                                            <b>Latest Scan History:</b><a href=""> Show full Scan History</a>
                                        </h5>
                                    </div>
                                </div>
                                <div class="table-box">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th scope="col">Date</th>
                                                <th scope="col">Url's</th>
                                                <th scope="col">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="scan in scans">
                                                <td>{{ getDate(scan) }}</td>
                                                <td>{{ getURL(scan) }}</td>
                                                <td><a style="color: #006ADB" href="#" @click="viewResults(scan)"><span class="translate" data-key="viewResults">{{ langLabel }}</span></a></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <!-- ProScan End -->

                <!-- Lite Start -->
                <div class="container" id="litescan">
                    <div class="row" id="lite-header">
                        <!-- CRAWLS Details -->
                        <div class="col-lg-12">
                            <div class="main-dash-card">
                                <div class="card-top">
                                    <div class="row">
                                        <div class="col-lg-4">
                                            <div class="details">
                                                <h5>
                                                    <span class="translate" data-key="crawlID">Crawl ID</span>: {{ scan.ID }}
                                                </h5>
                                                <h6><span class="translate" data-key="startURL">Starting URL</span>: {{ scan.starturl }}</h6>
                                                <h6>
                                                    <span class="translate" data-key="ScanInit">Scan initiated</span>: {{ scan.crawl_time_end.toISOString().split('T')[0] }}
                                                </h6>
                                                <h6>
                                                    <span class="translate" data-key="pdfFilesFound">PDF files scanned</span>: {{ files.length }}
                                                </h6>
                                            </div>
                                        </div>
                                        <div class="col-lg-8">
                                            <br>
                                            <h5>
                                                Search results for:
                                            </h5>
                                            <div class="show-urls">
                                                <select name="" id="" class="form-control">
                                                    <option value="">xn--brnepasning-ggb.esbjerg.dk</option>
                                                </select>
                                                <div class="dropdoen-btn">
                                                    Show all URL'S
                                                    <i class="fa fa-arrow-right"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="result-section">
                                    <div class="result-top" id="overall-header">
                                        <div>
                                            <button class="translate btn btn-export" id="exportCSV-lite" onclick="exportCsv(liteScan.scan.ID)">
                                                <span class="translate" data-key="Exportcsv"> Export results </span> <i class="fa fa-download"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-12" id="data-row">
                                            <div class="row">
                                                <div class="col-lg-8">
                                                    <div class="Compliant">
                                                        <div class="row">
                                                            <div class="col-lg-4">
                                                                <div class="content">
                                                                    <h5>
                                                                        <div class="dott" style="background-color:#00568E;">
                                                                        </div>
                                                                        <span class="translate" data-key="compliant">
                                                                            Compliant
                                                                        </span>
                                                                    </h5>
                                                                    <h1>
                                                                        {{ scan.compliant ? scan.compliant : 0 }}
                                                                    </h1>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-4">
                                                                <div class="content">
                                                                    <h5>
                                                                        <div class="dott" style="background-color:#BA0068;">
                                                                        </div>
                                                                        <span class="translate" data-key="nonComp">
                                                                            Non-Compliant
                                                                        </span>
                                                                    </h5>
                                                                    <h1>
                                                                        {{ scan.nonCompliant ? scan.nonCompliant : 0 }}
                                                                    </h1>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-4">
                                                                <div class="content">
                                                                    <h5>
                                                                        <div class="dott" style="background-color:#55AFC7;">
                                                                        </div>
                                                                        <span class="translate" data-key="untagged">
                                                                            Untagged
                                                                        </span>
                                                                    </h5>
                                                                    <h1>
                                                                        {{ scan.untagged ? scan.untagged : 0 }}
                                                                    </h1>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-4">
                                                    <div class="Errors">
                                                        <div class="row">
                                                            <div class="col-lg-6">
                                                                <div class="content">
                                                                    <h5 class="translate" data-key="offsite">
                                                                        Off Side Files
                                                                    </h5>
                                                                    <h1>
                                                                        {{ scan.offsiteFiles ? scan.offsiteFiles : 0 }}
                                                                    </h1>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6">
                                                                <div class="content">
                                                                    <h5 class="translate" data-key="fileErrors">
                                                                        File Errors
                                                                    </h5>
                                                                    <h1>
                                                                        {{ scan.Files_Error }}
                                                                    </h1>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- CRAWLS Details -->

                        <!-- Table -->
                        <div class="col-lg-12" id="file-viewer">
                            <div class="latest-scan-card">
                                <div class="result-top">
                                    <div>
                                        <h5>
                                            <b class="translate" data-key="results">Results: </b> {{ status }}
                                        </h5>
                                    </div>
                                </div>
                                <div class="top-filter">
                                    <div class="filter-box">
                                        <!-- <button class="btn filter-btn">
                                            <i class="fa fa-plus"></i> Add filter
                                        </button>
                                        <button class="btn pills-btn">
                                            Date <i class="fa fa-close"></i>
                                        </button>
                                        <button class="btn pills-btn">
                                            Compliant <i class="fa fa-close"></i>
                                        </button> -->
                                    </div>
                                    <div class="right-btns">
                                        <div class="dropdown">
                                            <button class="btn btn-export dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                Choose an action
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <a class="dropdown-item" href="#">Action</a>
                                                <a class="dropdown-item" href="#">Another action</a>
                                                <a class="dropdown-item" href="#">Something else here</a>
                                            </div>
                                        </div>

                                        <button class="translate btn btn-export" id="exportCSV-lite" onclick="exportCsv(liteScan.scan.ID)">
                                            <span class="translate" data-key="Exportcsv"> Export results </span> <i class="fa fa-download"></i>
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <template>
                                        <ejs-grid :data-source="files" ref="liteGrid" :allow-paging="true" :page-settings='pageSettings' :allow-sorting='true' :action-begin="liteTableStateChange">
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
                                </div>

                            </div>
                        </div>
                        <!-- Table -->
                    </div>
                </div>
                <!-- Lite End -->

            </div>
        </div>
    </div>
    <!-- contains Vue instances -->
    <script src="js/dashboard.js" defer></script>
</body>

</html>