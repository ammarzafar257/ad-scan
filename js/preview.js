// create a vue instance for the header
new Vue({
    el: "#main-app-header",
    data: {
        companyName: client.companyName,
        username: client.firstName + " " + client.lastName,
    }
});

// vue instance that stores information about the pdf's for display
let pdfInfo = null;

// storage for information about the PDF
let file = null;

// stores the information for PDF issues
let checksArray = [];

/**
 * Gets the information about the PDF (url, filename, filesize, etc.)
 * @type {Promise<void>}
 */
const getFileInfo = new Promise((resolve, reject) => {
    fetch("scanAPI.php?action=getSingleFiles&id=" + fileID)
        .then(response => response.json())
        .then((data) => {
            file = data;
            // create a vue instance for the page
            pdfInfo = new Vue({
                el: "#pdf-viewer",
                data: {
                    file : file[0],
                    checks : [],
                    checkLabels: [],
                    passedCount: 0,
                    warnedCount: 0,
                    failedCount: 0,
                },
                methods: {
                    copyURL : function() {
                        let elem = document.createElement("textarea");
                        document.body.appendChild(elem);
                        elem.value = this.file.url;
                        elem.select();
                        document.execCommand("copy");
                        document.body.removeChild(elem);
                    }
                }
            });
            resolve();
        })
        .then( () => {
            loadPDF().catch(err => {
                console.error("PDF Loading Error: PDFjs library has not been fetched yet, trying again...", err);
                setTimeout(() => {
                    loadPDF().catch(err => { console.error("PDF Loading Error: PDFjs library could not be fetched", err) });
                }, 1500);
            });
        })
        .catch(err => {
            console.error(err);
            reject();
        })
});

/**
 * Get the number of issues for a PDF for each type
 * @type {Promise<void>}
 */
const getFileErrors = new Promise((resolve, reject) => {
    fetch("scanAPI.php?action=getFileErrorCounts&id=" + fileID)
        .then(response => response.json())
        .then((data) => {
            checksArray = data;
            resolve();
        })
        .catch(err => {
            console.error(err);
            reject();
        })
});

let requests = [getFileInfo, getFileErrors]

// check that the file actully belongs to this client
if (client.clientId !== 1) {
    const fileOwnership = new Promise((resolve, reject) => {
       fetch("scanAPI.php?action=fileOwnership&fileID=" + fileID + "&client=" + client.clientId)
           .then(response => response.text())
           .then(response => {
             if (response === "false") {
                 window.location.href = 'index.php'  // redirect back to dashboard if the file doesn't belong to them
             }
             resolve();
           })
           .catch(err => {
               console.error(err);
               reject();
           })
    });
    requests.push(fileOwnership); // add to the starting requests
}

Promise.allSettled(requests)
    .then(() => {
        pdfInfo.checks = checksArray;
        pdfInfo.checkLabels=[
            {label:"Content",category:"Basic Requirements",passed:0,warnings:0,failed:0},
            {label:"Alternative Descriptions",category:"Logical Structure",passed:0,warnings:0,failed:0},
            {label:"Document Settings",category:"Metadata and Settings",passed:0,warnings:0,failed:0},
            {label:"Embedded Files",category:"Basic Requirements",passed:0,warnings:0,failed:0},
            {label:"Fonts",category:"Basic Requirements",passed:0,warnings:0,failed:0},
            {label:"ISO 32000-1",category:"Basic Requirements",passed:0,warnings:0,failed:0},
            {label:"Metadata",category:"Metadata and Settings",passed:0,warnings:0,failed:0},
            {label:"Natural Language",category:"Basic Requirements",passed:0,warnings:0,failed:0},
            {label:"Role Mapping",category:"Logical Structure",passed:0,warnings:0,failed:0},
            {label:"Structure Elements",category:"Logical Structure",passed:0,warnings:0,failed:0},
            {label:"Structure Tree",category:"Logical Structure",passed:0,warnings:0,failed:0}
        ];

        for (let i=0;i<checksArray.length;i++) {
            // add to the totals for the files
            pdfInfo.passedCount += parseInt(checksArray[i].PassedCount);
            pdfInfo.warnedCount += parseInt(checksArray[i].WarnedCount);
            pdfInfo.failedCount += parseInt(checksArray[i].FailedCount);

            switch(checksArray[i].Category) {
                case "Alternative Descriptions":
                    pdfInfo.checkLabels[1].passed += parseInt(checksArray[i].PassedCount);
                    pdfInfo.checkLabels[1].warnings += parseInt(checksArray[i].WarnedCount);
                    pdfInfo.checkLabels[1].failed += parseInt(checksArray[i].FailedCount);
                    break;
                case "Content":
                    pdfInfo.checkLabels[0].passed += parseInt(checksArray[i].PassedCount);
                    pdfInfo.checkLabels[0].warnings += parseInt(checksArray[i].WarnedCount);
                    pdfInfo.checkLabels[0].failed += parseInt(checksArray[i].FailedCount);
                    break;
                case "Document Settings":
                    pdfInfo.checkLabels[2].passed += parseInt(checksArray[i].PassedCount);
                    pdfInfo.checkLabels[2].warnings += parseInt(checksArray[i].WarnedCount);
                    pdfInfo.checkLabels[2].failed += parseInt(checksArray[i].FailedCount);
                    break;
                case "Embedded Files":
                    pdfInfo.checkLabels[3].passed += parseInt(checksArray[i].PassedCount);
                    pdfInfo.checkLabels[3].warnings += parseInt(checksArray[i].WarnedCount);
                    pdfInfo.checkLabels[3].failed += parseInt(checksArray[i].FailedCount);
                    break;
                case "Fonts":
                    pdfInfo.checkLabels[4].passed += parseInt(checksArray[i].PassedCount);
                    pdfInfo.checkLabels[4].warnings += parseInt(checksArray[i].WarnedCount);
                    pdfInfo.checkLabels[4].failed += parseInt(checksArray[i].FailedCount);
                    break;
                case "ISO 32000-1":
                    pdfInfo.checkLabels[5].passed += parseInt(checksArray[i].PassedCount);
                    pdfInfo.checkLabels[5].warnings += parseInt(checksArray[i].WarnedCount);
                    pdfInfo.checkLabels[5].failed += parseInt(checksArray[i].FailedCount);
                    break;
                case "Metadata":
                    pdfInfo.checkLabels[6].passed += parseInt(checksArray[i].PassedCount);
                    pdfInfo.checkLabels[6].warnings += parseInt(checksArray[i].WarnedCount);
                    pdfInfo.checkLabels[6].failed += parseInt(checksArray[i].FailedCount);
                    break;
                case "Natural Language":
                    pdfInfo.checkLabels[7].passed += parseInt(checksArray[i].PassedCount);
                    pdfInfo.checkLabels[7].warnings += parseInt(checksArray[i].WarnedCount);
                    pdfInfo.checkLabels[7].failed += parseInt(checksArray[i].FailedCount);
                    break;
                case "Role Mapping":
                    pdfInfo.checkLabels[8].passed += parseInt(checksArray[i].PassedCount);
                    pdfInfo.checkLabels[8].warnings += parseInt(checksArray[i].WarnedCount);
                    pdfInfo.checkLabels[8].failed += parseInt(checksArray[i].FailedCount);
                    break;
                case "Structure Elements":
                    pdfInfo.checkLabels[9].passed += parseInt(checksArray[i].PassedCount);
                    pdfInfo.checkLabels[9].warnings += parseInt(checksArray[i].WarnedCount);
                    pdfInfo.checkLabels[9].failed += parseInt(checksArray[i].FailedCount);
                    break;
                case "Structure Tree":
                    pdfInfo.checkLabels[10].passed += parseInt(checksArray[i].PassedCount);
                    pdfInfo.checkLabels[10].warnings += parseInt(checksArray[i].WarnedCount);
                    pdfInfo.checkLabels[10].failed += parseInt(checksArray[i].FailedCount);
                    break;
            }
        }

        // Get All the issues for a file
        let issuesFromReport = []
        let str = "" + pdfInfo.file.crawl_id
        let pad = "000"
        let crawlID = pad.substring(0, pad.length - str.length) + str
        let isDoneGettingIssue = false;

        checksArray = checksArray.filter(check => {
            return parseInt(check.FailedCount) > 0 || parseInt(check.WarnedCount) > 0
        });
        checksArray = checksArray.sort((a,b) => (parseInt(a.FailedCount) > parseInt(b.FailedCount)) ? 1 : ((parseInt(b.FailedCount) > parseInt(a.FailedCount)) ? -1 : 0));

        // go through each item in checksArray
        for (let i=0;i<checksArray.length;i++) {
            // Make a req to get the issue of that type
            let jsonURL = blobStorage + crawlID +"/" + pdfInfo.file.ID + "/" + checksArray[i].CheckId;
            $.ajax({
                type: 'GET',
                url:  jsonURL,
                dataType: "json",
                success: function(json) {
                    // upon getting them, print the first 100 to the list
                    addIssuesToTable(checksArray[i].CheckId, json)
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert("Issue: "
                        + textStatus + " "
                        + errorThrown + " !");
                }
            });
        }

        setTimeout(() => {
            $(".labelrow").each(function(i, obj) {
                obj.children[0].innerHTML = '<i class="fas fa-caret-right"></i> ' + obj.children[0].innerText;
            });
        }, 25)

        // add table row below matching data-checkId elems
        setTimeout(() => {
            $(".labelrow").on('click keydown', (e) => {
                if (e.type === 'keydown') {
                    if (e.keyCode !== 13) {  // 13 = enter
                        return;
                    }
                }
                // determine if open or closed
                let display = $($(e.target.parentNode).siblings()[0]).css('display');
                if (display === "none") {
                    $(e.target).attr("aria-expanded","true");
                    $($(e.target.parentNode).children()[0]).children()[0].classList.remove("fa-caret-right");
                    $($(e.target.parentNode).children()[0]).children()[0].classList.add("fa-caret-down");
                    for(let i=0;i<$(e.target.parentNode).siblings().length;i++) {
                        let thisRow = $(e.target.parentNode).siblings()[i]
                        if (thisRow.getAttribute("data-checkid") !== null) {
                            $(thisRow).fadeIn(500);
                        }
                    }
                } else {
                    $(e.target).attr("aria-expanded","false");
                    $($(e.target.parentNode).children()[0]).children()[0].classList.remove("fa-caret-down");
                    $($(e.target.parentNode).children()[0]).children()[0].classList.add("fa-caret-right");
                    $(e.target.parentNode).siblings().hide();

                    // make sure all children that can expand are set to not expanded
                    let elems = $(e.target.parentNode).siblings()
                    for (let i=0;i<elems.length;i++) {
                        if ($(elems[i]).attr('aria-expanded') === "true") {
                            $(elems[i]).attr('aria-expanded', false)
                            $(elems[i]).attr('is-showing-issue', false)
                            let arrow = $($(elems[i]).children()[0]).children()[0];
                            arrow.classList.remove("fa-caret-down");
                            arrow.classList.add("fa-caret-right");
                        }
                    }
                }
            });
            changeLangPDFComp(currentLang);
        }, 10);
    })
    .catch(err => {
        console.error(err);
    })
    // End of Promise.allSettled()

/**
 * Loads the pdf file into the preview pane
 * @throws {ReferenceError} if pdfJs has not been loaded yet
 */
async function loadPDF() {
    let url = file[0].url;
    //  used for getting around cors header issue
    url = "https://abledocs-cors-anywhere.herokuapp.com/" + url;

    pdfDoc = null;
    pageNum = 1;
    pageIsRendering = false;
    pageNumIsPending = null;

    scale = 1,
    canvas = document.querySelector('#pdf-render');
    ctx = canvas.getContext('2d');

    // Render the page
    renderPage = async function(num) {
        pageIsRendering = true;

        // Get page
        pdfDoc.getPage(num).then(page => {
            // Set scale
            let viewport = page.getViewport({ scale });
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            const renderCtx = {
                canvasContext: ctx,
                viewport
            };

            page.render(renderCtx).promise.then(() => {
                pageIsRendering = false;

                if (pageNumIsPending !== null) {
                    renderPage(pageNumIsPending);
                    pageNumIsPending = null;
                }
            });

            // Output current page
            document.querySelector('#page-num').textContent = num;
        });
    };

    // Check for pages rendering
    queueRenderPage = num => {
        if (pageIsRendering) {
            pageNumIsPending = num;
        } else {
            renderPage(num);
        }
    };

    // Show Prev Page
    showPrevPage = () => {
        if (pageNum <= 1) {
            return;
        }
        pageNum--;
        document.querySelectorAll('.issue').forEach(function(issue) {
            issue.style.fontWeight = "normal";
        });
        queueRenderPage(pageNum);
    };

    // Show Next Page
    showNextPage = () => {
        if (pageNum >= pdfDoc.numPages) {
            return;
        }
        pageNum++;
        document.querySelectorAll('.issue').forEach(function(issue) {
            issue.style.fontWeight = "normal";
        });
        queueRenderPage(pageNum);
    };

    // check the pdfjsLib has been initiated
    if (typeof pdfjsLib === 'undefined' || pdfjsLib === null) {
        throw new ReferenceError("PDFjs library has not been loaded yet")
    }

    pdfjsLib
        .getDocument(url)
        .promise.then(pdfDoc_ => {
        pdfDoc = pdfDoc_;
        document.querySelector('#page-count').textContent = pdfDoc.numPages;
        renderPage(pageNum);
        // remove the loading indictor and show the PDF
        $(canvas).show();
        $("#loading-pdf-indicator").hide();
    })
    .catch(err => {
        // log error in console
        console.error('PDF fetch error:', err);

        // hide the PDF section and the stats take the full width
        $("#pdf-section").css('display', 'none');
        $("#file-preformance-container").removeClass("col-lg-7").addClass("col-lg-12");

        // fire alert
        swal.fire(arrLang[currentLang]["error"], arrLang[currentLang]["pdfFetchError"] + ": <i>" + err.name + "</i>", "error");
    });

    // Button Events
    document.querySelector('#prev-page').addEventListener('click', showPrevPage);
    document.querySelector('#next-page').addEventListener('click', showNextPage);
}

function addIssuesToTable(checkID, issues) {
    // find the row the issue belongs too
    let selector = "[data-checkid='" + checkID + "']";
    let parent = $(selector);

    for (let i = 0; i < issues.length && i < 49; i++) {

        // if more then 50 issues can be added, add on a see more link
        if (i === 0 && issues.length > 50) {
            parent.after("<tr style='display: none' onclick='loadMoreIssues(this)' data-issueID='" + checkID + "'><td class='issue' colspan='4'><a>See More...</a></td></tr>")
        }

        let issue = issues[i];

        // make the string of on onclick listener
        let onClickString = "";
        if (issue.cropBox !== undefined) {
            onClickString = "onclick='addHighlight(" + (issue.pageIndex + 1) + ", [" + issue.cropBox + "], [" + issue.rectangle + "]); selectIssue(this);'"
        } else {
            onClickString = "onclick='addHighlight(1, [], []); selectIssue(this);'"
        }

        // get the caption for the issue, in the desired language
        try {
            if (isNaN(issue.caption)) {
                let comp = issue.caption.split("\"")[1];
                issue.caption = pdfCaptionList.find(cap => cap.en.split(' ').slice(0, 2).join('+') === issue.caption.split(' ').slice(0, 2).join('+'))[currentLang]
                issue.caption = issue.caption.split("« {0} »")[0] + comp + issue.caption.split("« {0} »")[1]
            } else {
                issue.caption = pdfCaptionList.find(cap => cap.ia === issue.caption)[currentLang]
            }
        } catch (err) {
            if (currentLang !== "en") {
                console.error("Missing translations for error", issue);
            }
        }


        // underneath each check header, add the issues
        if (issue.severity === "error") {
            parent.after("<tr style='display: none' data-issueID='" + checkID + "'><td " + onClickString + " class='issue' colspan='4'><i class=\"fas fa-times-circle\" style=\"color: red;\"></i>&nbsp;" + issue.caption + "</td></tr>");
        } else {
            parent.after("<tr style='display: none' data-issueID='" + checkID + "'><td " + onClickString + " class='issue' colspan='4'><i class=\"fas fa-exclamation-circle\" style=\"color: orange;\"></i>&nbsp;" + issue.caption + "</td></tr>");
        }
    }

    // timeout is need so the indicator will display in edge, not sure why that's needed but it is
    setTimeout(() => {
        let thisElem = parent.children()[0];
        thisElem.innerHTML = '<i class="fas fa-caret-right"></i><span> ' + thisElem.innerHTML + '</span>';
    }, 50);
}

/**
 * Adds a red rectangle over the pdf to highlight a section
 * example addHighlight(1, 40, 30, 230, 30);
 * @param page the hightlight is shown on
 * @param top y cord of top-left corner
 * @param left x cord of top-left corner
 * @param width of the box in px
 * @param height of the box in px
 */
async function addHighlight(page, cropBox, rectangle) {
    // show the page the error is on
    await renderPage(page);
    pageNum = page;

    if (rectangle.length === 0) {
        return;
    }

    setTimeout(() => {
        // create the fabric object and set the background to the current page
        let bg = canvas.toDataURL("image/png");
        let fcanvas = new fabric.StaticCanvas("pdf-render", {
            selection: true
        });
        fcanvas.setBackgroundImage(bg, fcanvas.renderAll.bind(fcanvas));
        fcanvas.setWidth(parseInt($("#pdf-render").css('width')));

        // cropBox[2] is total page width in report
        // cropBox[3] is total page height in report

        let width = rectangle[2] - rectangle[0];
        let height = rectangle[3] - rectangle[1];

        let top = cropBox[3] - rectangle[3];
        let left = rectangle[0];


        // create the rectangle
        var rect = new fabric.Rect({
            left: left,
            top: top,
            width: width,
            height: height,
            fill: '#FF454F',
            opacity: 0.5,
            transparentCorners: true,
            borderColor: "gray",
            cornerColor: "gray",
            cornerSize: 5
        });

        // add the highlight to the canvas
        fcanvas.add(rect);
        fcanvas.renderAll();

        $("#pdf-render").css('width', '90%');
        // $("#pdf-render").css('height', '70%');
    }, 200)
}

function selectIssue(elem) {
    // get rid of old bold text
    document.querySelectorAll('.issue').forEach(function(issue) {
        issue.style.fontWeight = "normal";
    });
    // add new bold text
    elem.style.fontWeight = "bold";
}

function hideIssues(elem){
    elem = $(elem);
    let selector = "[data-issueid=" + elem.attr('data-checkid') + "]";
    if (elem.attr('is-showing-issue') === "true" || elem.attr('is-showing-issue') === undefined) {
        $(selector).hide();
        elem.attr('is-showing-issue', false).attr('aria-expanded', false);
        $(elem.children()[0]).children()[0].classList.remove("fa-caret-down");
        $(elem.children()[0]).children()[0].classList.add("fa-caret-right");

    } else {
        $(selector).show();
        elem.attr('is-showing-issue', true).attr('aria-expanded', true);
        $(elem.children()[0]).children()[0].classList.remove("fa-caret-right");
        $(elem.children()[0]).children()[0].classList.add("fa-caret-down");
    }
}

function loadMoreIssues(elem) {
    // get the json for the issue
    let str = "" + pdfInfo.file.crawl_id
    let pad = "000"
    let crawlID = pad.substring(0, pad.length - str.length) + str
    let jsonURL = blobStorage + crawlID +"/" + pdfInfo.file.ID
        + "/" + $(elem).attr('data-issueid');
    $.ajax({
        type: 'GET',
        url:  jsonURL,
        dataType: "json",
        success: function(json) {
            let newIssues = json;

            for (let i=loadedAlready;i<newIssues.length;i++) {

                if (i === loadedAlready + 49) {  break; }  // stop at 50 issues

                // if last element
                if (i === loadedAlready && newIssues.length > 50 + loadedAlready) {
                    lastIssue.after("<tr onclick='loadMoreIssues(this)' data-issueID='" + checkID + "'><td class='issue' colspan='4'><a>See More...</a></td></tr>")
                }

                let issue = newIssues[i];

                // make the string of on onclick listener
                let onClickString = "";
                if (issue.cropBox !== undefined) {
                    onClickString = "onclick='addHighlight(" + (issue.pageIndex + 1) + ", [" + issue.cropBox + "], [" + issue.rectangle + "]); selectIssue(this);'"
                } else {
                    onClickString = "onclick='addHighlight(1, [], []); selectIssue(this);'"
                }

                // get the caption for the issue, in the desired language
                // todo: Change when more translations are available, only have german and english
                let lang = (currentLang === 'de') ? 'de' : 'en';
                issue.caption = pdfCaptionList.find(cap => cap.ia === issue.caption)[lang]
                // underneath each check header, add the issues
                if (issue.severity === "error") {
                    lastIssue.after("<tr data-issueID='" + checkID + "'><td " + onClickString + " class='issue' colspan='4'><i class=\"fas fa-times-circle\" style=\"color: red;\"></i>&nbsp;" + issue.caption + "</td></tr>");
                } else {
                    lastIssue.after("<tr data-issueID='" + checkID + "'><td " + onClickString + " class='issue' colspan='4'><i class=\"fas fa-exclamation-circle\" style=\"color: orange;\"></i>&nbsp;" + issue.caption + "</td></tr>");
                }
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            alert("Issue: "
                + textStatus + " "
                + errorThrown + " !");
        }
    });

    // the number of issue that are loaded already
    let loadedAlready = $("tr[data-issueid=" + $(elem).attr('data-issueid') + "]").length;
    let lastIssue = $(elem.previousSibling)
    let checkID = $(elem).attr('data-issueid');
    elem.parentNode.removeChild(elem);

}
