/**
 * @summary This file controls the main dashboard for ad scan
 * @author Colin Taylor
 */

/**
 * Handels the the dropdown for the users menu
 * @global
 */
let avgUaList = null;

let listenersSet = false;

let viewStatus = null;
if (sessionStorage.getItem('viewStatus') !== null) {
    viewStatus = JSON.parse(sessionStorage.getItem('viewStatus'));
}

$(document).ready(function () {
    if (viewStatus === null) {
        // if coming in from crawls list use that scan instead
        let startingScan = null;
        if (sessionStorage.getItem('fromMaster') !== null) {
            startingScan = scans.find(c => c.ID === parseInt(sessionStorage.getItem('fromMaster')));

            // in case starting scan cannot be found, use most recent one
            if (startingScan === undefined) {
                startingScan = allScanMostRecent[0];
            }

        } else {
            startingScan = allScanMostRecent[0];
        }

        // init the viewStatus object
        viewStatus = {
            isPro: startingScan.ScanType === "Pro",
            url: "",
            isShowingFiles: $("#file-viewer").css('display') !== 'none',
            fileCategory: ''
        };

        // show the dashboard if Pro scan is first
        if (viewStatus.isPro) {
            $("#proscan").show();
            $("#file-viewer").hide();
        }

        // Hide the main dashboard if scan is lite
        let proScans = 0;
        let liteScans = 0;
        for (let i = 0; i < scans.length; i++) {
            // get the number for scantypes
            (scans[i].ScanType === "Lite") ? liteScans++ : proScans++;
        }
        // if all lite
        if (liteScans > 0 && proScans === 0) {
            $("#litescan").show();
        } else if (proScans > 0 && liteScans === 0) {  // if all pro scans
            $("#litescan").hide();
        } else {    // if both
            if (startingScan.ScanType === "Pro") {
                $("#litescan").hide();
            } else {
                $("#litescan").show();
            }
        }
        if (liteScans === 0 && scans.length === 1) {
            function waitForUAList() {
                if (avgUaList !== null) {
                    showScanData(startingScan.starturl, 0);
                }
                else {
                    setTimeout(waitForUAList, 100);
                }
            }
            waitForUAList();
        }

        // get the selected url
        if ($("#overviewLink").css('display') !== 'none') {
            viewStatus.url = 'overview';
        } else {
            viewStatus.url = allScanMostRecent[0].starturl;
        }

        // add to session storage
        sessionStorage.setItem('viewStatus', JSON.stringify(viewStatus));
    } else {
        if (viewStatus.url === "overview") {
            SwitchOverallDash();
            $("#litescan").hide();
        } else {
            function waitForUAList() {
                if (avgUaList !== null) {
                    showScanData(viewStatus.url, 0);
                    // show files if needed
                    if (viewStatus.isShowingFiles) {
                        if (viewStatus.fileCategory === "Non-Compliant and untagged") {
                            fileViewer.showNonCompAndUntagged();
                            // hide the other sections and show the file table
                            $("#history-graph-container").css('display', 'none');
                            $("#file-viewer").css('display', 'flex');
                        } else {
                            topData.showFiles(viewStatus.fileCategory);
                        }
                    }
                }
                else {
                    setTimeout(waitForUAList, 100);
                }
            }
            waitForUAList();
        }
    }
    loadFromMaster();
});

/**
* Loads a crawl if one has been selected from crawls list
*/
function loadFromMaster() {
    // function to wait for avgUaList to not be null, showScanData() fails if it is null, waits on get req to scanAPI.php?action=getAvgUA
    function waitForUAScores() {
        if (avgUaList !== null) {
            // check to see if came from master.php and if so select that scan
            if (sessionStorage.getItem('fromMaster') !== null) {
                try {
                    // find the url of the crawl
                    let clickedCrawl = scans.find(c => c.ID === parseInt(sessionStorage.getItem('fromMaster')));

                    // if the clicked cannot be found, load the most recent scan
                    if (clickedCrawl === undefined) {
                        clickedCrawl = allScanMostRecent[0]
                    }

                    // get the index of that crawl in scansHash
                    let index = 0;
                    for (let i = 0; i < scansHash[clickedCrawl.starturl].length; i++) {
                        if (scansHash[clickedCrawl.starturl][i].ID === clickedCrawl.ID) {
                            index = i;
                            break;
                        }
                    }

                    // open that scan, 0 means most recent for that url
                    showScanData(clickedCrawl.starturl, index);
                } catch (err) {
                    // write error to console if that fails
                    console.error(err);
                }
                // session item is no longer needed so remove it
                sessionStorage.removeItem('fromMaster');
            }
        } else {
            setTimeout(waitForUAScores, 100);
        }
    }
    waitForUAScores();
}


/**
* Updates the 'viewStatus' object in sessionStorage
* @param key of the object to change
* @param value of the key
* @static
*/
function changeViewStatus(key, value) {
    if (typeof key !== 'string') {
        console.error('key for function changeViewStatus was not string');
        return;
    }
    if (sessionStorage.getItem('viewStatus') === null) {
        return;
    }
    viewStatus = JSON.parse(sessionStorage.getItem('viewStatus'));
    if (viewStatus === null) {
        return;
    }
    switch (key) {
        case 'url':
            if (typeof value !== 'string') {
                console.error('new url entered into function changeViewStatus not a string');
                return;
            }
            viewStatus.url = value;
            break;
        case 'isPro':
            if (typeof value !== 'boolean') {
                console.error('new value entered into function changeViewStatus had incorrect type, key = isPro');
                return;
            }
            viewStatus.isPro = value;
            break;
        case 'isShowingFiles':
            if (typeof value !== 'boolean') {
                console.error('new value entered into function changeViewStatus had incorrect type, key = isShowingFiles');
                return;
            }
            viewStatus.isShowingFiles = value;
            break;
        case 'fileCategory':
            if (typeof value !== 'string') {
                console.error('new value entered into function changeViewStatus had incorrect type, key = fileCategory');
                return;
            }
            viewStatus.fileCategory = value;
            break;
        default:
            break;
    }
    sessionStorage.setItem('viewStatus', JSON.stringify(viewStatus));
}


/**
* Stores the totals for all most recent scans by the client
* @type {{offsite: number, averageUA: number, untagged: number, compliant: number, nonCompliant: number}}
*/
let overviewStats = {
    compliant: 0,
    nonCompliant: 0,
    untagged: 0,
    offsite: 0,
    errors: 0,
    averageUA: 0
};

getOverviewStats();

let bestScan = allScanMostRecent[0];
let worstScan = allScanMostRecent[0];


/**
* store all the most recent files in recentScanIDs
*/
let recentScanIDs = [];
allScanMostRecent.forEach((scan) => {
    recentScanIDs.push(scan.ID);
});
let filesNewestScan = files.filter(file => {
    return recentScanIDs.includes(file.crawl_id);
});

/**
* Defines the vue comp for the aside section
* @type {Vue}
*/
let sideNav = new Vue({
    el: "#sidebar",
    data: {
        client: client,
        companyName: client.companyName,
        username: client.firstName + " " + client.lastName,
        urls: scanURLS,
    },
    methods: {
        clickedDomain(event, url) {
            // show the data for said crawl
            if (url === "Overview") {
                SwitchOverallDash();

                changeViewStatus('url', 'overview');

            } else {
                showScanData(url, 0);
                fileViewer.status = arrLang[currentLang]["allFiles"];
                changeViewStatus('url', url);
            }
        },
    }
});

let searchResultFor = new Vue({
    el: "#search-result-for",
    data: {
        urls: scanURLS,
    },
    methods: {
        searchResultClickedDomain() {
            const url = document.getElementById("searchResult").value;
            // show the data for said crawl
            if (url === "Overview") {
                SwitchOverallDash();
                changeViewStatus('url', 'overview');
            } else {
                showScanData(url, 0);
                fileViewer.status = arrLang[currentLang]["allFiles"];
                changeViewStatus('url', url);
            }
        }
    }
});

/**
* The header section of the dashboard
*/
let dashHeader = new Vue({
    el: "#main-app-header",
    data: {
        client: client,
        companyName: client.companyName,
        username: client.firstName + " " + client.lastName
    }
});

/**
* Vue instance containing the date for the overall compliant as of line
* @type {Vue}
*/
let overallStatHeader = new Vue({
    el: "#overall-header",
    data: {
        date: allScanByDate[0] ? allScanByDate[0].crawl_time_end.toISOString().split('T')[0] : null,
    }
});

let crawlInfo = new Vue({
    el: '#crawl-info',
    data: {
        ID: null,
        start: "",
        date: null,
        total: 0,
        allUrls: scanURLS,
    },
    methods: {
        crawlInfoUpdate: (crawl) => {
            crawlInfo.ID = crawl.ID;
            crawlInfo.start = crawl.starturl;
            crawlInfo.date = crawl.crawl_time_end.toISOString().split('T')[0];
            crawlInfo.total = crawl.compliant + crawl.nonCompliant + crawl.untagged + crawl.offsiteFiles
        }
    }
});

/**
* The the top section that shows file counters and doughnut chart
* @type {Vue}
*/
let topData = new Vue({
    el: "#data-row",
    data: {
        compliant: overviewStats.compliant,
        nonCompliant: overviewStats.nonCompliant,
        untagged: overviewStats.untagged,
        offsite: overviewStats.offsite,
        errors: overviewStats.errors,
        averageUA: overviewStats.averageUA,
        scanID: null
    },
    methods: {
        /**
         * Changes the files displayed in the file viewer table
         * @param {string} type the type of files to see ('all', 'compliant', 'nonCompliant', 'untagged', 'offsite')
         */
        showFiles: (type) => {
            // set the scans in file viewer the desired ones
            switch (type) {
                case 'compliant':
                    fileViewer.status = arrLang[currentLang]["compliant"];
                    fileViewer.files = files.filter(file => {
                        return file.UA_Index === 100 && file.Tagged === 'Yes' && !file.offsite;
                    });
                    break;
                case 'nonCompliant':
                    fileViewer.status = arrLang[currentLang]["nonComp"]
                    fileViewer.files = files.filter(file => {
                        return file.UA_Index !== 100 && file.Tagged === 'Yes' && file.UA_Index !== null && !file.offsite;
                    });
                    break;
                case 'untagged':
                    fileViewer.status = arrLang[currentLang]["untagged"]
                    fileViewer.files = files.filter(file => {
                        return !file.offsite && (file.Tagged === 'No') && file.UA_Index !== null;
                    });
                    break;
                case 'offsite':
                    fileViewer.status = arrLang[currentLang]["offsite"]
                    fileViewer.files = files.filter(file => {
                        return file.offsite;
                    });
                    break;
                case 'errors':
                    fileViewer.status = arrLang[currentLang]["fileErrors"];
                    fileViewer.files = files.filter(file => {
                        return file.UA_Index === null && !file.offsite;
                    });
                    break;
                case 'all':
                default:
                    fileViewer.files = files;
                    fileViewer.status = arrLang[currentLang]["allFiles"];
                    break;
            }
            // hide the other sections and show the file table
            $("#scan-history-table-container").hide();
            $("#history-graph-container").css('display', 'none');
            $("#file-viewer").css('display', 'flex');
            // update the viewStatus
            changeViewStatus('isShowingFiles', true);
            changeViewStatus('fileCategory', type);
            // resize scroll bar
            resizeScrollPro()
        }
    }
});


/**
* Scan history instance
* @type {Vue}
*/
let scanHistoryTable = new Vue({
    el: "#scan-history-table-container",
    data: {
        scans: allScanByDate,
        langLabel: "View Results",
    },
    methods: {
        getDate: (scan) => {
            return scan.crawl_time_end.toISOString().split('T')[0];
        },
        getURL: (scan) => {
            return scan.starturl.split("/")[2]
        },
        viewResults: (scan) => {
            // TODO: needs updated
            // $("#url-select").val(scan.starturl);
            let index = 0;
            for (let i = 0; i < scansHash[scan.starturl].length; i++) {
                if (scansHash[scan.starturl][i].ID === scan.ID) { index = i; }
            }
            showScanData(scan.starturl, index);
        },
    }
});

let action;
let dropInstance = null;
let offsitedropInstance = null;
let taggedDropInstance = null;
let createDateInstance = null;
let modDateInstance = null;
let urlFilterSelections = null;

/**
* Used for fileViewer.sortNullAtBottom() to determine if a value from the table is empty
* @param reference either the reference or comparer for sorting
* @returns {boolean} True if empty value
*/
function isValueEmpty(reference) {
    return reference === " " || reference === "" || reference === '\n  ' || reference === null;
}

// Translations for the grid
ejs.base.L10n.load(tableTranslations);


/**
* an array of json data needed for the data range picker translations.
* Note: These files were taken from syncfusion vue node module
* @type {string[]}
*/
const jsonFiles = [
    "js/dateRangeLangJson/numberingSystems.json",
    "js/dateRangeLangJson/weekData.json",

    "js/dateRangeLangJson/da/ca-gregorian.json",
    "js/dateRangeLangJson/da/numbers.json",
    "js/dateRangeLangJson/da/timeZoneNames.json",

    "js/dateRangeLangJson/de/ca-gregorian.json",
    "js/dateRangeLangJson/de/numbers.json",
    "js/dateRangeLangJson/de/timeZoneNames.json",

    "js/dateRangeLangJson/en/ca-gregorian.json",
    "js/dateRangeLangJson/en/numbers.json",
    "js/dateRangeLangJson/en/timeZoneNames.json",

    "js/dateRangeLangJson/es/ca-gregorian.json",
    "js/dateRangeLangJson/es/numbers.json",
    "js/dateRangeLangJson/es/timeZoneNames.json",

    "js/dateRangeLangJson/fr/ca-gregorian.json",
    "js/dateRangeLangJson/fr/numbers.json",
    "js/dateRangeLangJson/fr/timeZoneNames.json",

    "js/dateRangeLangJson/it/ca-gregorian.json",
    "js/dateRangeLangJson/it/numbers.json",
    "js/dateRangeLangJson/it/timeZoneNames.json",

    "js/dateRangeLangJson/nl/ca-gregorian.json",
    "js/dateRangeLangJson/nl/numbers.json",
    "js/dateRangeLangJson/nl/timeZoneNames.json"
];

// load the json files into the translator
function loadJsonforDateRange(location) {
    fetch(location)
        .then(r => r.json())
        .then(json => {
            ejs.base.loadCldr(json);
        });
}

jsonFiles.forEach(file => {
    loadJsonforDateRange(file);
});


/**
* Controls the table for files,
* Selected files are accessed through fileViewer.selected
* @type {Vue}
*/
let fileViewer = new Vue({
    el: "#file-viewer",
    data: {
        files: filesNewestScan,
        status: '',
        lang: currentLang,
        pageSettings: { pageSize: 15 },
        selectionOptions: { enableToggle: true, persistSelection: true },
        arrLang: arrLang,
        dateValue: null,
        moddateValue: null,
        endDate: null,
        modEndDate: null,
        toolbarOptions: ['Search', 'ColumnChooser'],
        searchOptions: { fields: ['url', 'filename', 'UA_Index', 'NumPages', 'Tagged', 'Title', 'Creator', 'Producer', 'CreationDate', 'ModDate', 'Lang', 'FileSize', 'offsite'] },
        filterOptions: {
            type: 'Menu'
        },
        filterCheckBox: {
            type: 'CheckBox'
        },
        urlFilter: {
            ui: {
                create: function (args) {
                    args.getOptrInstance.dropOptr.element.parentElement.parentElement.style.display =
                        "none";
                    let flValInput = document.createElement("input", { className: "flm-input" });
                    args.target.appendChild(flValInput);
                    dropInstance = new ejs.dropdowns.MultiSelect({
                        fields: { text: "url", value: "url" },
                        dataSource: new ejs.data.DataManager(getDomainsForCrawl(false)),
                        popupHeight: "200px",
                        popupWidth: "400px",
                        mode: "CheckBox",
                        allowFiltering: false,
                        showDropDownIcon: true,
                    });
                    dropInstance.appendTo(flValInput);
                },
                write: function (args) {
                    dropInstance.value = (urlFilterSelections) ? urlFilterSelections : getDomainsForCrawl(false);
                    // dropInstance.value = [...new Set(fileViewer.$refs.grid.getCurrentViewRecords().map(item => item.url.split('/')[0] + "//" +  item.url.split('/')[2]))];
                    setTimeout(() => { dropInstance.showPopup(); }, 40);
                },
                read: function (args) {
                    urlFilterSelections = dropInstance.value;
                    fileViewer.$refs.grid.clearFiltering(['url']);
                    fileViewer.$refs.grid.filterByColumn(args.column.field, "startsWith", dropInstance.value);
                },
                update: function () {
                    try {
                        dropInstance.dataSource = getDomainsForCrawl(false);
                    } catch (e) {
                        if (e instanceof ReferenceError || e instanceof TypeError) {
                            console.warn('dropInstance not created yet');
                        } else {
                            console.error(e)
                        }
                    }
                }
            }
        },
        offsiteFilter: {
            ui: {
                create: function (args) {
                    args.getOptrInstance.dropOptr.element.parentElement.parentElement.style.display =
                        "none";
                    let flValInput = document.createElement("input", { className: "flm-input" });
                    args.target.appendChild(flValInput);
                    offsitedropInstance = new ejs.dropdowns.DropDownList({
                        dataSource: [
                            { text: arrLang[currentLang]['yes'], value: 'Yes' },
                            { text: arrLang[currentLang]['no'], value: 'No' },
                        ],
                        fields: { text: "text", value: "value" },
                        popupHeight: '200px',
                        popupWidth: "300px",
                        mode: "CheckBox"
                    });
                    offsitedropInstance.appendTo(flValInput);
                    setTimeout(() => { offsitedropInstance.showPopup(); }, 10);
                },
                write: function (args) {
                    offsitedropInstance.value = args.filteredValue;
                },
                read: function (args) {
                    args.fltrObj.filterByColumn(
                        args.column.field,
                        "contains",
                        offsitedropInstance.value === 'Yes'
                    );
                },
            }
        },
        taggedFilter: {
            ui: {
                create: function (args) {
                    args.getOptrInstance.dropOptr.element.parentElement.parentElement.style.display =
                        "none";
                    let flValInput = document.createElement("input", { className: "flm-input" });
                    args.target.appendChild(flValInput);
                    taggedDropInstance = new ejs.dropdowns.DropDownList({
                        dataSource: [
                            { text: arrLang[currentLang]['yes'], value: 'Yes' },
                            { text: arrLang[currentLang]['no'], value: 'No' },
                            { text: arrLang[currentLang]['unknown'], value: 'Unknown' }
                        ],
                        fields: { text: "text", value: "value" },
                        popupHeight: '200px',
                        popupWidth: "300px",
                        mode: "CheckBox"
                    });
                    taggedDropInstance.appendTo(flValInput);
                    setTimeout(() => { offsitedropInstance.showPopup(); }, 10);
                },
                write: function (args) {
                    taggedDropInstance.value = args.filteredValue;
                },
                read: function (args) {
                    args.fltrObj.filterByColumn(
                        args.column.field,
                        "equal",
                        taggedDropInstance.value
                    );
                },
            }
        },
        createdDateFilter: {
            ui: {
                create: function (args) {
                    args.getOptrInstance.dropOptr.element.parentElement.parentElement.style.display =
                        "none";
                    let isFiltered = event.target.classList.contains("e-filtered");

                    let flValInput = document.createElement("input", { className: "flm-input" });
                    flValInput.id = 'createDateInput';
                    createDateInstance = new ejs.calendars.DateRangePicker({
                        value: isFiltered ? fileViewer.dateValue : null,
                        openOnFocus: true,
                        locale: fileViewer.lang,
                        change: function (e) {
                            fileViewer.dateValue = e.value;
                            let grid = fileViewer.$refs.grid;
                            if (e.value) {
                                let startDate = e.value[0];
                                fileViewer.endDate = e.value[1];
                                grid.filterByColumn('CreationDate', 'greaterThan', startDate);
                            } else {
                                grid.filterSettings.columns = [];
                                grid.removeFilteredColsByField(e.target.id);
                            }
                        }
                    });
                    args.target.appendChild(flValInput);
                    createDateInstance.appendTo(flValInput);
                }
            }
        },
        modDateFilter: {
            ui: {
                create: function (args) {
                    args.getOptrInstance.dropOptr.element.parentElement.parentElement.style.display =
                        "none";
                    let isFiltered = event.target.classList.contains("e-filtered");

                    let flValInput = document.createElement("input", { className: "flm-input" });
                    flValInput.id = 'modDateInput';
                    modDateInstance = new ejs.calendars.DateRangePicker({
                        value: isFiltered ? fileViewer.dateValue : null,
                        openOnFocus: true,
                        locale: fileViewer.lang,
                        change: function (e) {
                            fileViewer.moddateValue = e.value;
                            let grid = fileViewer.$refs.grid;
                            if (e.value) {
                                let startDate = e.value[0];
                                fileViewer.modEndDate = e.value[1];
                                grid.filterByColumn('ModDate', 'greaterThan', startDate);
                            } else {
                                grid.filterSettings.columns = [];
                                grid.removeFilteredColsByField(e.target.id);
                            }
                        }
                    });
                    args.target.appendChild(flValInput);
                    modDateInstance.appendTo(flValInput);
                }
            }
        },
        dateFormat: {
            type: 'date',
            format: 'yyyy/MM/dd'
        },
        uaTemplate: function () {
            return {
                template: Vue.component("columntemplate", {
                    template: '<div v-if="data.UA_Index === null">---</div>\n' +
                        '<div v-else>{{ (Math.floor(parseFloat(data.UA_Index) * 100) / 100).toFixed(2) }}</div>'
                })
            }
        },
        nameTemplate: function () {
            return {
                template: Vue.component("columntemplate", {
                    template: '<div><a v-bind:href="\'fileview.php?id=\' + data.ID" >{{ data.filename }}</a></div>'
                })
            }
        },
        taggedTemplate: function () {
            return {
                template: Vue.component("columntemplate", {
                    template: '<div v-if="data.Tagged === \'Unknown\'" class="translate" data-key="unknown"></div>\n' +
                        '      <div v-else-if="data.Tagged === \'Yes\'" class="translate" data-key="yes"></div>\n' +
                        '      <div v-else class="translate" data-key="no"></div>'
                })
            }
        },
        offsiteTemplate: function () {
            return {
                template: Vue.component("columntemplate", {
                    template: '<div v-if="data.offsite" class="translate" data-key="yes">yes</div>\n' +
                        '<div v-else class="translate" data-key="no">no</div>'
                })
            }
        },
        dropdownItems: [
            { text: arrLang[currentLang]['sendToAble'], id: 'sendToAD' },
            { text: arrLang[currentLang]['downloadSelect'], id: 'downloadFiles' },
            { text: arrLang[currentLang]['Exportcsv'], id: 'exportCSV' },
        ],
    },
    methods: {
        clearFilters() {
            $("#files-table-pro select").val($("select option:first").val());
            $("#files-table-pro :input[type=number]").val('');
            $("#files-table-pro :input[type=search]").val('');
            $("#createDateInput").val('')
            $("#modDateInput").val('')
            fileViewer.$refs.grid.clearFiltering();
        },
        dataStateChange(args) {
            // disables the autocomplete feature for the text input, needs a small delay to work
            if (args.requestType === 'filterbeforeopen' && args.columnName === 'filename') {
                setTimeout(() => {
                    try {
                        let input = document.querySelector("#strui-grid-column2")
                        input.ej2_instances[0].autofill = false
                        input.ej2_instances[1].autofill = false
                    } catch (e) {
                        console.error(e);
                    }
                }, 50);
            }

            // for creation date filtering
            if (args.currentFilteringColumn === 'CreationDate' && args.action === 'filter') {
                args.columns.push({
                    actualFilterValue: {},
                    actualOperator: {},
                    field: "CreationDate",
                    ignoreAccent: false,
                    isForeignKey: false,
                    matchCase: false,
                    operator: "lessThan",
                    predicate: "and",
                    uid: fileViewer.$refs.grid.getColumnByField(args.currentFilteringColumn).uid,
                    value: fileViewer.endDate
                });
            }

            // for last modified date filtering
            if (args.currentFilteringColumn === 'ModDate' && args.action === 'filter') {
                args.columns.push({
                    actualFilterValue: {},
                    actualOperator: {},
                    field: "ModDate",
                    ignoreAccent: false,
                    isForeignKey: false,
                    matchCase: false,
                    operator: "lessThan",
                    predicate: "and",
                    uid: fileViewer.$refs.grid.getColumnByField(args.currentFilteringColumn).uid,
                    value: fileViewer.modEndDate
                });
            }

            // for getting the sorting direction
            if (args.requestType == "sorting") {
                action = args.direction;
            }

            // wait a bit for that table to repopulate then fix translations and the top scroll bar
            setTimeout(() => {
                $(".translate[data-key='yes']").each((index) => {
                    let elem = $($(".translate[data-key='yes']")[index]);
                    elem.text(arrLang[currentLang][elem.attr('data-key')]);
                });
                $(".translate[data-key='no']").each((index) => {
                    let elem = $($(".translate[data-key='no']")[index]);
                    elem.text(arrLang[currentLang][elem.attr('data-key')]);
                });
                $(".translate[data-key='unknown']").each((index) => {
                    let elem = $($(".translate[data-key='unknown']")[index]);
                    elem.text(arrLang[currentLang][elem.attr('data-key')]);
                });
                // when the columns change, resize the top scroll bar
                if (args.requestType === "columnstate" && args.name === "actionBegin") {
                    resizeScrollPro();
                }
            }, 50)
        },
        /**
         * This function adds a tooltip over any header that overflows its bounds
         */
        headerCellInfoEvent: function (args) {
            try {
                // wait 200 milliseconds so table has time to populate, needed to function
                setTimeout(() => {
                    // if the size of the box is smaller than its content...
                    if ((args.node.children[2].offsetWidth + args.node.children[0].children[0].offsetWidth + 18) > args.node.children[0].clientWidth &&
                        args.cell.column.headerText !== '') {
                        // add a tooltip with the headers text
                        new ejs.popups.Tooltip(
                            {
                                content: args.cell.column.headerText
                            },
                            args.node
                        );
                    } else {
                        // delete the tooltip if there is one
                        try {
                            if (args.node.ej2_instances.length > 0) {
                                args.node.ej2_instances[0].destroy();
                            }
                        } catch (e) {
                            if (e instanceof TypeError) {
                                // these should be ignored because the function runs multiple times and
                                // will encounter this when moving the resizing element
                            } else {
                                console.error(e);
                            }
                        }
                    }
                }, 200)
            } catch (err) {
                console.error('Error creating the tooltips for the headers in the files table', err);
            }
        },
        /**
         * Adds a tooltip to a column that when resized overflows its bounds
         * @param args
         * @see headerCellInfoEvent
         */
        resizeColumn: function (args) {
            // get the column being resized
            let columnName = args.column.headerText;
            let columnIndex = args.column.index;
            let columnHeader = fileViewer.$refs.grid.getColumnHeaderByIndex(columnIndex);
            // Run headerCellInfoEvent() to add the tooltip
            this.headerCellInfoEvent({ node: columnHeader, cell: { column: { headerText: columnName } } });
            // resize the top scroll bar if needed
            resizeScrollPro();
        },
        showNonCompAndUntagged() {
            // update status
            fileViewer.status = arrLang[currentLang]["nonCompUntagged"];
            // show non-comp files
            this.files = files.filter(file => {
                return parseFloat(file.UA_Index) < 100 && file.Tagged === "Yes" && !file.offsite;
            });
            // add on the untagged files
            let untaggedFiles = files.filter(file => {
                return !file.offsite && file.Tagged === "No";
            });
            this.files = this.files.concat(untaggedFiles);
            changeViewStatus('fileCategory', fileViewer.status);
        },
        actionSelect(args) {
            switch (args.item.id) {
                case 'sendToAD':
                    sendForRemediation(fileViewer.$refs.grid.getSelectedRecords(), client);
                    break;
                case 'downloadFiles':
                    downloadMultiplePDF(fileViewer.$refs.grid.getSelectedRecords());
                    break;
                case 'exportCSV':
                    exportCsv(topData.scanID);
                    break;
                default:
                    break;
            }
        },
        selectAllRows() {
            document.querySelector("div.e-headercelldiv.e-headerchkcelldiv > div > input").click();
        },
        clearSelected() {
            fileViewer.$refs.grid.clearRowSelection();
        },
        sortNullAtBottom(reference, comparer) {
            let sortAsc = action === "Ascending";

            // If the data is string make sure were treating them as all lowercase so they sort correctly
            if (typeof reference === 'string') {
                reference = reference.trim().toLowerCase();
            }
            if (typeof comparer === 'string') {
                comparer = comparer.trim().toLowerCase();
            }

            if (sortAsc && isValueEmpty(reference) && !isValueEmpty(comparer)) {
                return 1;
            }
            else if (sortAsc && isValueEmpty(comparer)) {
                return -1;
            }
            else if (!sortAsc && isValueEmpty(reference) && !isValueEmpty(comparer)) {
                return -1;
            }
            else if (!sortAsc && isValueEmpty(comparer)) {
                return 1;
            } else {
                if (reference < comparer) {
                    return -1;
                }
                if (reference >= comparer) {
                    return 1;
                }
            }
            return 0;
        }
    }
});

/**
* Swaps the data in the dashboard to that of a new crawl
* @param url {string} the URL of the crawl in the DB
* @param index {int} that crawls position in scansHash (0 is  most recent crawl on that url)
* @return undefined
* @static
*/
function showScanData(url, index) {
    let thisScan = scansHash[url][index];

    if (thisScan.ScanType !== "Pro") {
        showLiteScan(url, index);
        return;
    }
    changeViewStatus('isPro', true);
    changeViewStatus('url', url);
    
    // ensure the scans are shown again
    $("#proscan").show();
    $("#file-viewer").hide();
    $("#scan-history-table-container").show();
    $("#history-graph-container").show();
    $("#litescan").hide();

    // hide all showfiles links
    $(".showFiles").show();

    // show the crawl info card
    $("#crawl-info-wrapper").show();

    // update the files browser
    let newfiles = [];
    let filesReq = new XMLHttpRequest()
    filesReq.open("GET", "scanAPI.php?action=getFiles&crawl=" + thisScan.ID, true);
    filesReq.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
            newfiles = JSON.parse(this.responseText);
            for (let i = 0; i < newfiles.length; i++) {
                // update the UA index to be a float
                if (newfiles[i].UA_Index !== null) {
                    newfiles[i].UA_Index = parseFloat(newfiles[i].UA_Index);
                }
                // update creation date and last modified dates to be actaull dates instead of strings
                newfiles[i].CreationDate = new Date(newfiles[i].CreationDate);
                newfiles[i].ModDate = new Date(newfiles[i].ModDate);
                // update offsite to a proper boolean
                newfiles[i].offsite = newfiles[i].offsite === 1;
                // update the tagged value to have its string value
                if (newfiles[i].offsite || newfiles[i].UA_Index === null) {
                    newfiles[i].Tagged = "Unknown";
                } else if (newfiles[i].Tagged === "1") {
                    newfiles[i].Tagged = "Yes";
                } else {
                    newfiles[i].Tagged = "No";
                }
            }
            files = newfiles;

            switch (viewStatus.fileCategory) {
                case 'compliant':
                case 'nonCompliant':
                case 'untagged':
                case 'offsite':
                    topData.showFiles(viewStatus.fileCategory);
                    break;
                case 'File Errors':
                    fileViewer.files = files.filter(file => {
                        return file.UA_Index === null && !file.offsite;
                    });
                    break;
                case 'Non-compliant and untagged':
                    fileViewer.files = files.filter(file => {
                        return file.UA_Index !== 100 && file.UA_Index !== null;
                    });
                    break;
                default:   // show all files
                    fileViewer.files = newfiles;
            }
            // update & correct the files table
            fileViewer.urlFilter.ui.update()
            /*fileViewer.applicationTemplateOptions.update()
            fileViewer.producerTemplateOptions.update()
            fileViewer.languageTemplateOptions.update()
            document.querySelector("#offsiteFilter > option:nth-child(2)").innerHTML = "Yes";
            document.querySelector("#offsiteFilter > option:nth-child(3)").innerHTML = "No";*/
        }
        if (this.readyState === 4 && this.status !== 200) {
            console.error('Error: something went wrong getting files for crawl ' + thisScan.ID + '. HTTP Status: ' + this.status);
            newfiles = [];
            fileViewer.files = newfiles;
        }
        // resize the scroll bar
        resizeScrollPro()
    };
    filesReq.send();
    // change the date in the overall stats header
    overallStatHeader.date = thisScan.crawl_time_end.toISOString().split('T')[0];
    // change the top data section to have stats for that scan
    topData.compliant = thisScan.compliant;
    topData.nonCompliant = thisScan.nonCompliant;
    topData.untagged = thisScan.untagged;
    topData.offsite = thisScan.offsiteFiles;
    topData.errors = thisScan.Files_Error;
    topData.averageUA = getAvgUaForCrawl(thisScan.ID);
    topData.scanID = thisScan.ID;

    // update the crawl info
    crawlInfo.crawlInfoUpdate(thisScan);

    // update the doughnut chart
    resChart.data.datasets[0].data = [topData.compliant, topData.nonCompliant, topData.untagged];
    resChart.update();
    // have the scan history change to  so show scans under that url
    scanHistoryTable.scans = scansHash[url];
    sessionStorage.setItem('scanHistoryPage', JSON.stringify(scansHash[url]));
    // update the compliance history chart
    let scansCount = scansHash[url].length
    let compliantBar = [];
    let non_compliantBar = [];
    let untaggedBar = [];
    let labels = [];
    for (let i = 0; i < scansCount; i++) {
        if (i === 4) {  // stop and 4 most recent scans
            break;
        }
        let total = scansHash[url][i].compliant + scansHash[url][i].nonCompliant + scansHash[url][i].untagged;
        compliantBar.unshift(parseFloat((scansHash[url][i].compliant / total) * 100).toFixed(2));
        non_compliantBar.unshift(parseFloat((scansHash[url][i].nonCompliant / total) * 100).toFixed(2))
        untaggedBar.unshift(parseFloat((scansHash[url][i].untagged / total) * 100).toFixed(2))
        labels.unshift(scansHash[url][i].crawl_time_end.toISOString().split('T')[0]);
    }
    historyChart.data.datasets[0].data = untaggedBar;
    historyChart.data.datasets[1].data = non_compliantBar;
    historyChart.data.datasets[2].data = compliantBar;
    historyChart.data.labels = labels;
    historyChart.update();
    $("#historyGraphURL").text(" - " + thisScan.starturl);
}

// check if coming from crawls list, if so start with that scan instead
let startScan = null;
if (sessionStorage.getItem('fromMaster') !== null) {
    startScan = scans.find(c => c.ID === parseInt(sessionStorage.getItem('fromMaster')))
} else {
    startScan = allScanMostRecent[0];
}

let liteScan = new Vue({
    el: "#litescan",
    data: {
        scan: startScan,
        files: [],
        pageSettings: {
            pageSize: 50
        },
        urls: scanURLS,
        uaTemplate: function () {
            return {
                template: Vue.component("columntemplate", {
                    template: '<div v-if="data.UA_Index === null">---</div>\n' +
                        '<div v-else>{{ (Math.floor(parseFloat(data.UA_Index) * 100) / 100).toFixed(2) }}</div>'
                })
            }
        },
        filenameTemplate: function () {
            return {
                template: Vue.component("columntemplate", {
                    template: '<div v-if="data.UA_Index === null || data.UA_Index === \'NaN\'"><span class="error">&#9888;</span> {{ data.filename }}</div>\n' +
                        '<div v-else>{{ data.filename }}</div>'
                })
            }
        },
        taggedTemplate: function () {
            return {
                template: Vue.component("columntemplate", {
                    template: '<div v-if="data.Tagged === \'Unknown\'" class="translate" data-key="unknown"></div>\n' +
                        '      <div v-else-if="data.Tagged === \'Yes\'" class="translate" data-key="yes"></div>\n' +
                        '      <div v-else class="translate" data-key="no"></div>'
                })
            }
        },
    },
    methods: {
        searchResultClickedDomain() {
            const url = document.getElementById("liteScanSearchResult").value;
            console.log(url, "URL");
            // show the data for said crawl
            if (url === "Overview") {
                SwitchOverallDash();
                changeViewStatus('url', 'overview');
            } else {
                showScanData(url, 0);
                fileViewer.status = arrLang[currentLang]["allFiles"];
                changeViewStatus('url', url);
            }
        },
        liteTableStateChange() {
            setTimeout(() => {
                $(".translate[data-key='yes']").each((index) => {
                    let elem = $($(".translate[data-key='yes']")[index]);
                    elem.text(arrLang[currentLang][elem.attr('data-key')]);
                });
                $(".translate[data-key='no']").each((index) => {
                    let elem = $($(".translate[data-key='no']")[index]);
                    elem.text(arrLang[currentLang][elem.attr('data-key')]);
                });
                $(".translate[data-key='unknown']").each((index) => {
                    let elem = $($(".translate[data-key='unknown']")[index]);
                    elem.text(arrLang[currentLang][elem.attr('data-key')]);
                });
            }, 50)
        }
    }
});

/**
* Shows the scan as a lite version scan
* @param url {string} the url of the requested crawl
* @param index {int} that crawls position in scansHash (0 is  most recent crawl on that url)
* @return undefined
* @static
*/
function showLiteScan(url, index) {
    // hide the dashboard
    $("#proscan").hide();
    $("#litescan").show();

    // get the current showScan
    let thisScan = undefined
    thisScan = scansHash[url][index];
    liteScan.scan = thisScan;
    sessionStorage.removeItem('scanHistoryPage');

    // update the files browser
    let filesLite = [];
    let filesReqLite = new XMLHttpRequest()
    filesReqLite.open("GET", "scanAPI.php?action=getFilesForLite&crawl=" + thisScan.ID, true);
    filesReqLite.onreadystatechange = function () {
        if (this.readyState === 4 && this.status === 200) {
            filesLite = JSON.parse(this.responseText);
            for (let i = 0; i < filesLite.length; i++) {
                // update the tagged value to have its string value
                if (filesLite[i].offsite || filesLite[i].UA_Index === null) {
                    filesLite[i].Tagged = "Unknown";
                } else if (filesLite[i].Tagged === "1") {
                    filesLite[i].Tagged = "Yes";
                } else {
                    filesLite[i].Tagged = "No";
                }
            }
            liteScan.files = filesLite
            setInterval(() => {
                $("#top-scroll").css('width', $("#filesTable").width() + 16);
            }, 1000);
        }
        if (this.readyState === 4 && this.status !== 200) {
            console.error('Error: something went wrong getting files for crawl ' + thisScan.ID + '. HTTP Status: ' + this.status);
            filesLite = [];
            liteScan.files = filesLite
            setInterval(() => {
                $("#top-scroll").css('width', $("#filesTable").width() + 16);
            }, 1000);
        }
    };
    filesReqLite.send();

    changeViewStatus('isPro', false);
    changeViewStatus('url', url);
}

/**
* Creates the scroll bar for the top of lite scans
*/
{

    const topwrapper = document.querySelector("#top-scroll-wrapper");
    const filestable = document.querySelector("#litescan > div.e-control.e-grid.e-lib.e-gridhover.e-responsive" +
        ".e-default.e-droppable.e-tooltip.e-keyboard > div.e-gridcontent.e-responsive-header > div")


    /**
     * the table in lite is bigger then it shows for some reason
     * so this is the total of all the widths defined in lite tables
     * e-column elements
     * @see index.php -> ref="liteGrid"
     * @type {int}
     */
    const liteTableHeight = 1290

    topwrapper.onscroll = function () {
        filestable.scrollLeft = topwrapper.scrollLeft;
    };
    filestable.onscroll = function () {
        topwrapper.scrollLeft = filestable.scrollLeft;
    };
    $("#top-scroll").css('width', liteTableHeight);
}

/**
* Shows the data and graphs and hides the files list
* @global
*/
function SwitchOverallDash() {

    // make sure dash is showing
    $("#scan-history-table-container").show();

    // hide the crawl info card
    $("#crawl-info-wrapper").hide();
    // $("#overall-compliance-wrapper").removeClass('col-md-9').addClass('col-12');

    // change the date in the overall stats header
    overallStatHeader.date = allScanByDate[0].crawl_time_end.toISOString().split('T')[0]

    // show the correct elements
    $("#history-graph-container").css('display', 'block');
    $("#file-viewer").css('display', 'none');

    // update the top-data

    // update the top-data
    topData.compliant = overviewStats.compliant;
    topData.nonCompliant = overviewStats.nonCompliant;
    topData.untagged = overviewStats.untagged;
    topData.offsite = overviewStats.offsite;
    topData.errors = overviewStats.errors;
    topData.averageUA = overviewStats.averageUA;
    topData.scanID = null;

    // update the doughnut chart
    resChart.data.datasets[0].data = [topData.compliant, topData.nonCompliant, topData.untagged];
    resChart.update();

    // update the history graph
    showOverallHistory();

    // update the scan history to have all scans
    scanHistoryTable.scans = allScanByDate;

    // hide all showfiles links
    $(".showFiles").hide();

    changeViewStatus('isPro', true);
    changeViewStatus('url', 'overview');
    changeViewStatus('isShowingFiles', false);
}

/**
* Gets the overall stats for the client
*/
function getOverviewStats() {
    for (let i = 0; i < allScanMostRecent.length; i++) {
        overviewStats.compliant += allScanMostRecent[i].compliant;
        overviewStats.nonCompliant += allScanMostRecent[i].nonCompliant;
        overviewStats.untagged += allScanMostRecent[i].untagged;
        overviewStats.offsite += allScanMostRecent[i].offsiteFiles;
        overviewStats.errors += allScanMostRecent[i].Files_Error;
    }
}

let prefRanges = null;
function getAverageUAforCrawls() {
    if (sessionStorage.getItem("uaList") === null || sessionStorage.getItem("uaList") === "null") {
        // if we don't know the scores, go get them
        let uaGetReq = new XMLHttpRequest();
        uaGetReq.open('GET', 'scanAPI.php?action=getAvgUA&id=' + client.clientId, true)
        uaGetReq.onreadystatechange = function () {
            if (this.readyState === 4 && this.status !== 200) {
                console.error("Average UA scores for crawls couldn't be found. HTTP Status: " + this.status);
                avgUaList = [];
            }
            if (this.readyState === 4 && this.status === 200) {
                avgUaList = JSON.parse(this.responseText);
                sessionStorage.setItem("uaList", this.responseText);
            }
            if (this.readyState === 4) {
                uaListCallback(avgUaList);
            }
        }
        uaGetReq.send()
    } else {
        // if we already know the scores the dont fetch them
        avgUaList = JSON.parse(sessionStorage.getItem("uaList"));
        uaListCallback(avgUaList);
        return;
    }

}

function uaListCallback(avgUaList) {
    let total = 0;
    avgUaList.forEach((ua) => {
        total += ua.averageUA;
    });
    overviewStats.averageUA = parseFloat((total / avgUaList.length).toFixed(2));
    topData.averageUA = overviewStats.averageUA;

    // find the best and worst scans
    for (let i = 0; i < allScanMostRecent.length; i++) {
        // check if best scan
        if (getAvgUaForCrawl(allScanMostRecent[i].ID) > getAvgUaForCrawl(bestScan.ID)) {
            bestScan = allScanMostRecent[i];
        }
        // check if worst scan
        if (getAvgUaForCrawl(allScanMostRecent[i].ID) < getAvgUaForCrawl(worstScan.ID) || getAvgUaForCrawl(allScanMostRecent[i].ID) === null) {
            worstScan = allScanMostRecent[i];
        }
    }
}

/**
* Get the average UA for a crawl
* @param crawlID {number}
* @returns {null|number|string}
*/
function getAvgUaForCrawl(crawlID) {
    let bar = avgUaList.find(ua => {
        return ua.crawl_id === crawlID;
    });
    if (bar !== undefined) {
        return bar.averageUA;
    } else {
        return null;
    }

}

function showOverallHistory() {
    // get amount of months the total history encompasses
    let earliestScan = allScanByDate[allScanByDate.length - 1].crawl_time_end;
    let currentDate = new Date()
    let months = currentDate.getMonth() - earliestScan.getMonth() + (12 * (currentDate.getFullYear() - earliestScan.getFullYear()))
    months++;

    // stores the stats for month
    let statsByMonth = [];
    // make sure the amount of months doesnt go above 12
    if (months > 12) {
        months = 12;
    }
    for (let i = 0; i < months; i++) {
        // get date
        let date = new Date();
        date.setMonth((date.getMonth() + 1) - (months - i));
        // get all scans before date
        let scansInRange = allScanByDate.filter(scan => scan.crawl_time_end < date);
        scansInRange = scansInRange.reverse();
        // filter out duplicates
        let tempUrls = []
        scansInRange.forEach(scan => {
            if (!tempUrls.some(el => el.starturl === scan.starturl)) {
                tempUrls.push(scan);
            }
        });
        // make object for stats
        let stats = {
            date: date,
            compliant: 0,
            nonCompliant: 0,
            untagged: 0,
            total: 0
        }
        // count the compliant, non-comp and untagged
        tempUrls.forEach(crawl => {
            stats.compliant += crawl.compliant;
            stats.nonCompliant += crawl.nonCompliant;
            stats.untagged += crawl.untagged;
            stats.total = stats.compliant + stats.nonCompliant + stats.untagged;
        });
        // convert to percentages
        stats.compliant = ((stats.compliant / stats.total) * 100).toFixed(3)
        stats.nonCompliant = ((stats.nonCompliant / stats.total) * 100).toFixed(3)
        stats.untagged = ((stats.untagged / stats.total) * 100).toFixed(3)
        // add to array
        if (stats.total !== 0) {
            statsByMonth.push(stats);
        }
    }
    // update the bar graph
    let compliantBar = [];
    let non_compliantBar = [];
    let untaggedBar = [];
    let labels = [];
    statsByMonth.forEach(stat => {
        compliantBar.push(stat.compliant);
        non_compliantBar.push(stat.nonCompliant);
        untaggedBar.push(stat.untagged);
        labels.push(stat.date.toISOString().split('T')[0]);
    });
    historyChart.data.datasets[0].data = untaggedBar;
    historyChart.data.datasets[1].data = non_compliantBar;
    historyChart.data.datasets[2].data = compliantBar;
    historyChart.data.labels = labels;
    historyChart.update();
    $("#historyGraphURL").text("");
}

/**
* Gets a list of domains/subdomains of urls from the files of a crawl
* @param {boolean} includeOffsite whether or not to include offsite files
* @returns {string[]} list of domains/subdomains
*/
function getDomainsForCrawl(includeOffsite) {
    let domains = []
    files.forEach(file => {
        try {
            if ((includeOffsite) || (!includeOffsite && !file.offsite)) {
                let url = new URL(file.url);
                if (!domains.includes(url.origin)) {
                    domains.push(url.origin)
                }
            }
        } catch (err) {
            if (err instanceof TypeError) {
                console.error("TypeError making list of domains for for filtering: " + file.url + " is not a valid URL")
            } else {
                console.error(err);
            }

        }
    });
    return domains;
}

/**
* Resizes the scroll bar for the pro files table
*/
function resizeScrollPro() {
    // grab the elements needs
    let outerScroll = document.querySelector("#top-scroll-wrapper-pro");
    let filesTablePro = document.querySelector("#files-table-pro > div.e-gridcontent > div")
    let tableWidth = document.querySelector("#files-table-pro > div.e-gridheader.e-lib.e-droppable > div > table").offsetWidth

    // check if the table needs to have a scroll bar
    if (tableWidth <= filesTablePro.offsetWidth) {
        outerScroll.style.display = 'none';
        return;
    } else {
        outerScroll.style.display = 'block';
    }

    setTimeout(() => {
        // set the width of the inner div to the width of the table
        $('#top-scroll-pro').css('width', tableWidth + 16); // 16 for 1em of spacing
        // set the listeners for scrolling if needed
        if (!listenersSet) {
            outerScroll.onscroll = function () {
                filesTablePro.scrollLeft = outerScroll.scrollLeft;
            }
            filesTablePro.onscroll = function () {
                outerScroll.scrollLeft = filesTablePro.scrollLeft;
            }
            listenersSet = true;
        }
    }, 500);
}

getAverageUAforCrawls();

function logout() {
    window.location.href = "login/login.php";
}