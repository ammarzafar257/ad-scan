/**
 * @summary File has methods that control the actions dropdown in AD scan
 * @author: Colin Taylor
 */

// open the menu when the button has been clicked
function openOptionMenu() {
    document.getElementById("dropdown-content").classList.toggle("show");
    if ($("#dropdown-content > a:nth-child(1)").css('display') === 'block') {
        document.querySelector("#dropdown-content > a:nth-child(1)").focus();
        $("#dropdown-content").attr('aria-expanded', true);
    }
    // if closed, make aria-expanded false
    if (!$('#dropdown-content').hasClass('show')) {
        $("#dropdown-content").attr('aria-expanded', false);
    }
}

/**
 * Converts and array to a csv file and downloads it
 * @param array array being made into csv
 * @param name the name of file, always add .csv to end
 */
function writeCSV(array, name) {
    // Fix up values to be user friendly
    for (let x = 0; x < array.length; x++) {
        // convert UA to two decimal number
        try {
            if (isNaN(parseFloat(array[x].UA_Index))) {
                array[x].UA_Index = 0;
            } else {
                array[x].UA_Index = (Math.floor(parseFloat(array[x].UA_Index) * 100) / 100).toFixed(2)
            }
        } catch (err) {
            console.error("Error rounding UA index to 2 decimal places", err);
        }
    }
    /*
        * For Future Use If We Add Any Other Language Having A Decimal Operator "."
        * if(currentLang == "da" || currentLang == "fr" || currentLang == "it" || currentLang == "es" || currentLang == "nl")
    */
    if (currentLang != "en") {
        for (let element of array) {
            let value = element.UA_Index.toString();
            if (value.includes(".")) {
                value = value.replace(".", ",")
                element.UA_Index = value;
            }
        }
    }

    // convert file information into csv text
    let csv;
    if (array.length != 0) {
        let fields = Object.keys(array[0]);
        let replacer = function (key, value) {
            return value === null ? '' : value
        }
        csv = array.map(function (row) {
            return fields.map(function (fieldName) {
                return JSON.stringify(row[fieldName], replacer);
            }).join(',')
        });

        let new_fields = [];
        for (let field of fields) {
            let new_value = changeCSVHeader(field, currentLang);
            if (new_value) {
                new_fields.push(new_value);
            } else {
                new_fields.push(field);
            }
        }
        csv.unshift(new_fields.join(',')); // add header column
        csv = csv.join('\r\n');
    } else {
        // will download empty csv if there were no files scanned
        csv = [];
    }

    // close loading alert
    Swal.close();

    // download csv file
    const a = document.createElement("a");
    document.body.appendChild(a);
    a.style = "display: none";
    const blob = new Blob(["\uFEFF" + csv], { type: "text/csv; charset=utf-18" }),
        url = window.URL.createObjectURL(blob);
    a.href = url;
    a.download = name;
    a.click();
    window.URL.revokeObjectURL(url);
    // remove the link
    a.parentNode.removeChild(a);
}

/**
 * Creates a CSV file from the selected files
 * @param key (int) crawl_id the files come from
 * @param fileIDs (array-int) optional parameter for File IDs if only wanting some files to be included. If empty, will use all files
 */
function exportCsv(key, fileIDs = null) {
    Swal.fire({
        html:
            '<img src="./images/PdfFooter.png" style="position: absolute; top : 75px; left: 125px;" height=150 width="150" alt="Able Docs logo inside loading spinner">' +
            '<div style="height: 250px; width: 250px;" class="spinner-border text-primary" role="status"></div>',
        showCloseButton: false,
        showCancelButton: false,
        showConfirmButton: false,
        focusConfirm: false,
        onOpen: function () {
            setTimeout(() => {
                let files = [];
                let filesReq = new XMLHttpRequest();
                filesReq.open("POST", "scanAPI.php?action=csvDataCrawl", false);
                filesReq.setRequestHeader(
                    "Content-Type",
                    "application/json;charset=UTF-8"
                );
                filesReq.onreadystatechange = function () {
                    if (this.readyState === 4 && this.status === 200) {
                        files = JSON.parse(this.responseText);

                        // remove the selected and crawl_id properties, make the UA 2 decimals places
                        files.forEach(function (f) {
                            delete f.crawl_id;
                            f.UA_Index = (
                                Math.floor(parseFloat(f.UA_Index) * 100) / 100
                            ).toFixed(2);
                        });

                        writeCSV(files, "adscan-export.csv");
                    }
                };

                //checkSelectAll button if there's some search key and no row is selected
                if (
                    fileViewer.$refs.grid.getSelectedRecords().length == 0 &&
                    fileViewer.$refs.grid.ej2Instances.searchSettings.key
                ) {
                    checkSelectAll();
                }

                let fileIdArray = [];
                //download csv for selected row / data
                if (
                    fileIDs === null &&
                    fileViewer.$refs.grid.getSelectedRecords().length > 0 //will download the selected records
                ) {
                    fileIdArray = fileViewer.$refs.grid
                        .getSelectedRecords()
                        .map((row) => row.ID);
                }

                //download csv for filtered data
                else if (
                    fileIDs === null &&
                    fileViewer.$refs.grid.getFilteredRecords().length > 0 //will download the filtered record
                ) {
                    fileIdArray = fileViewer.$refs.grid
                        .getFilteredRecords()
                        .map((row) => row.ID);
                }

                filesReq.send(
                    JSON.stringify({
                        crawl: key, // crawl id
                        files: fileIdArray, // array of selected files ID
                    })
                );

                //after downloading csv clears all selected row
                if (
                    fileViewer.$refs.grid.getSelectedRecords().length > 0 &&
                    fileViewer.$refs.grid.ej2Instances.searchSettings.key
                ) {
                    fileViewer.$refs.grid.clearSelection();
                }
            }, 10);
        },
    });
}

/**
 * checkSelectAll Button
 * @returns {*}
 */
function checkSelectAll() {
    return fileViewer.$refs.grid.ej2Instances.selectionModule.checkSelectAll();
}

/**
 * Creates a csv for the checks in a individual pdf file, used in flieview.php, No longer used
 * @deprecated
 * @param checks the value of pdfInfo.checks from fileview.php
 * @returns {boolean | null} false if falied otherwise returns nothing
 */
function exportCsvChecks(checks) {
    if (checks.length === 0) {
        console.log("exportCsvChecks has exited");
        return false;
    }
    writeCSV(checks, "pdf-checks.csv");
}

/**
 * Downloads a file from a blob
 * @param {Blob} blob data for file
 * @param {string} name filename for downloaded file
 */
function downloadFile(blob, name) {
    // create a download link and click it
    const newurl = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    document.body.appendChild(a);
    a.style = "display: none";
    a.href = newurl;
    a.download = decodeURI(name);
    a.click();
    // remove the link
    a.parentNode.removeChild(a);
}

/**
 * downloads a single file given a url and filename
 * @param {string} url location of the file
 * @param {string} name name for file, run through decodeURI()
 */
function downloadSinglePDF(url, name) {
    // add cors-anywhere to the url
    url = "https://abledocs-cors-anywhere.herokuapp.com/" + url;
    // get the pdf
    fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/pdf'
        }
    })
        // Get blob
        .then(async res => ({
            blob: await res.blob()
        }))
        .then(resObj => {
            // build the pdf
            const newBlob = new Blob([resObj.blob], { type: 'application/pdf' });
            downloadFile(newBlob, name);
        })
        .catch(err => {
            console.error(err);
            alert("An error occurred while trying to get the file\n" + err);
        });
}

/**
 * Downloads multiple pdf files into a .zip
 * @param files {[Object]} urls of the requested PDFs
 * @returns {boolean} false if fails, true if successful
 */
function downloadMultiplePDF(files) {
    // if no files exit
    if (files.length === 0) {
        Swal.fire('', arrLang[currentLang]["noFiles"], 'warning');
        return false;
    }

    // if one file just send that file
    if (files.length === 1) {
        let filename = decodeURI(files[0].url).split(".pdf")[0].split('/')[decodeURI(files[0].url).split(".pdf")[0].split('/').length - 1] + ".pdf";
        downloadSinglePDF(files[0].url, filename);
        return true;
    }

    Swal.fire({
        title: arrLang[currentLang]["fetching"],
        html: "<label for=\"file\">" + arrLang[currentLang]["downloading"] + "</label><br><progress id='file'></progress>",
        showCancelButton: false,
        showConfirmButton: false,
        onOpen: function () {
            let errors = [];
            let zip = new JSZip();
            let pdfs = zip.folder("pdfs");

            // get each url and tag on the cors proxy
            let urls = files.map(f => f.url);
            for (let i = 0; i < urls.length; i++) {
                urls[i] = 'https://abledocs-cors-anywhere.herokuapp.com/' + urls[i];
            }

            // fetch each url
            Promise.all(urls.map(url => fetch(url, { method: 'GET', headers: { 'Content-Type': 'application/pdf' } })
                // check that response is a PDF
                .then((res) => {
                    let resCopy = res.clone();
                    resCopy.text()
                        .then((resText) => {
                            if (resText.substring(0, 4) !== "%PDF") {
                                errors.push(url);
                                throw new Error("Not a PDF file");
                            }
                        })
                        .catch(err => {
                            console.error(res.url + ": ", err);
                        });
                    return res;
                })
                // read the response and make in blob
                .then(async res => ({ blob: await res.blob() }))
                // Make the blob a pdf and add to zip
                .then(resObj => {
                    if (!errors.includes(url)) {
                        let filename = '';
                        try {
                            // get the filename for the file
                            filename = decodeURI(files.find(obj => { return obj.url === url.substring(45); }).filename) + ".pdf";
                        } catch (err) {  // if it cannot be found
                            console.error(err);
                            // get the filename from the url, remove and periods, add .pdf extension
                            filename = decodeURI(url).split(".pdf")[0].split('/')[decodeURI(url).split(".pdf")[0].split('/').length - 1];
                            filename = filename.split('.').join("");
                            filename += ".pdf";
                        }

                        // Add the file to the zip
                        const newBlob = new Blob([resObj.blob], { type: 'application/pdf' });
                        pdfs.file(filename, newBlob, { binary: true });
                    }
                })
                .catch(err => {
                    console.error(err);
                })
            ))
                .then(value => {
                    try {
                        if (errors.length === files.length) {  // display error to user if files can't be found
                            Swal.fire(arrLang[currentLang]["error"], arrLang[currentLang]["pdfDownloadFail"], "error");
                        } else { // zip if there are some to be added
                            zip.generateAsync({ type: "blob" })
                                .then(function (content) {
                                    downloadFile(content, "scannedFiles.zip");
                                    Swal.close();
                                    // send alert if errors
                                    if (errors.length > 0) {
                                        let list = '<ul>';
                                        errors.forEach(err => {
                                            list += "<li>" + err.substring(45) + "</li>"  // removes the cors-proxy part of the url
                                        });
                                        list += "</ul>"
                                        Swal.fire({
                                            title: errors.length + " " + arrLang[currentLang]["cantDownloadCount"],
                                            icon: 'error',
                                            html: list
                                        });
                                    }
                                    return true;
                                }
                                );
                        }
                    } catch (err) {
                        console.error(err);
                        Swal.fire({
                            title: arrLang[currentLang]["errorBundle"],
                            icon: 'error',
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    return false;
                });
        }
    });
}

async function changePassword() {
    const { value: formValues } = await Swal.fire({
        title: arrLang[currentLang]["changePass"],
        html:
            '<label for="password">' + arrLang[currentLang]["password"] + ':</label>' +
            '<input type="password" id="password" class="swal2-input">' +
            '<label for="confirmed-password">' + arrLang[currentLang]["conPass"] + ':</label>' +
            '<input type="password" id="confirmed-password" class="swal2-input">',
        focusConfirm: false,
        preConfirm: () => {
            return [
                document.getElementById('password').value,
                document.getElementById('confirmed-password').value
            ]
        }
    })
    if (formValues) {
        // if values are empty
        if (formValues[0] === "" || formValues[1] === 1) {
            Swal.fire(arrLang[currentLang]["error"], arrLang[currentLang]["emptyFields"], "error");
            return;
        }
        // if values do not match
        if (formValues[0] !== formValues[1]) {
            Swal.fire(arrLang[currentLang]["error"], arrLang[currentLang]["passswordNoMatch"], "error");
            return;
        }
        $.post('includes/changePassword.php', {
            password: formValues[0],
            user: client.userID
        }, function (response) {
            console.log(response);
            if (response === "1") {
                Swal.fire(arrLang[currentLang]["success"], arrLang[currentLang]["passChange"], "success");
            } else {
                Swal.fire(arrLang[currentLang]["error"], arrLang[currentLang]["passErrorChange"], "error");
            }
        });
    }
}

/**
 * Takes the selected files and sends them for remediation
 * @param files {[object]} the selected files, grabbed from fileViewer.selectedRows
 * @param client {object} object containing the users ID, name, email, ADO ID, etc.
 * @see includes/sendRemediation.php
 */
function sendForRemediation(files, client) {
    // check that some files have been selected
    if (files.length === 0) {
        swal.fire(arrLang[currentLang]["error"], arrLang[currentLang]["noSelected"], 'error')
        return;
    }

    // grab the urls for the files that are being sent
    let urls = files.map(a => a.urls);

    // send a post request with those urls + client information to generate a quote
    $.post('includes/sendRemediation.php', {
        urls: urls,
        userID: parseInt(client.userID),
        clientID: parseInt(client.clientId),
        clientFirst: client.firstName,
        clientLast: client.lastName,
        clientEmail: client.email,
        adoContactID: (client.adoContactID === "") ? null : client.adoContactID,
        lang: client.lang
    })

        // check if the quote was created and give the appropriate response
        .done(function (msg) {
            if (msg === "job created") {
                swal.fire({ icon: 'success', title: arrLang[currentLang]['sentforQuote'] });
            } else {
                swal.fire({ icon: 'error', title: arrLang[currentLang]['errorSending'], text: arrLang[currentLang]['tryAgainLater'] });
                console.error(msg);
            }
        })

        // if the request fails, send an error saying to try again later
        .fail(function (xhr, status, error) {
            swal.fire({ icon: 'error', title: arrLang[currentLang]['errorSending'], text: arrLang[currentLang]['tryAgainLater'] });
        });
}

async function inviteNewUser() {
    const { value: formValues } = await Swal.fire({
        title: arrLang[currentLang]["inviteUser"],
        html:
            '<label for="invite-fname">' + arrLang[currentLang]["fName"] + ':</label>' +
            '<input type="text" id="invite-fname" class="swal2-input">' +
            '<label for="invite-lname">' + arrLang[currentLang]["Lname"] + ':</label>' +
            '<input type="text" id="invite-lname" class="swal2-input">' +
            '<label for="invite-email">' + arrLang[currentLang]["Email"] + ':</label>' +
            '<input type="text" id="invite-email" class="swal2-input">',
        focusConfirm: false,
        confirmButtonText: arrLang[currentLang]["createAccount"],
        preConfirm: () => {
            return [
                document.getElementById('invite-fname').value,
                document.getElementById('invite-lname').value,
                document.getElementById('invite-email').value
            ]
        }
    })
    if (formValues) {
        // make sure all fields have a value
        if (formValues[0] === "" || formValues[1] === "" || formValues[2] === "") {
            Swal.fire(arrLang[currentLang]["error"], arrLang[currentLang]["emptyFields"], "error");
            return;
        }
        if (!/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(formValues[2])) {
            Swal.fire(arrLang[currentLang]["error"], arrLang[currentLang]["invalidEmail"], "error");
            return;
        }
        //ensure the email is valid
        // All good make account
        $.post('includes/invite.php', {
            email: formValues[2],
            firstName: formValues[0],
            lastName: formValues[1],
            clientId: client.clientId,
            company: client.companyName,
            lang: client.lang
        }, function (data, statusText, xhr) {
            if (xhr.status === 201) {
                Swal.fire(arrLang[currentLang]["newAccount"], arrLang[currentLang]["tempPass"] + " " + data, "success");
            } else {
                Swal.fire(arrLang[currentLang]["error"], data, "error");
            }
        });
    }
}

function backToAdmin() {
    const params = new URLSearchParams(window.location.search)
    let currentLanguage = params.get('lang') ? params.get('lang') : "en";
    window.location.href = `master.php`;
}

/**
 * Opens up a popup looking for the new language
 * @returns {Promise<void> || string}
 */
async function getnewLang() {
    const { value: newlang } = await Swal.fire({
        title: '',
        html: "<input type='radio' id='en-swap' name='lang-swap' value='en'/>" +
            "<label style='margin-left: 8px' for='en-swap'>English</label><br />" +

            "<input type='radio' id='fr-swap' name='lang-swap' value='fr'/>" +
            "<label style='margin-left: 8px' for='fr-swap'>Français</label><br />" +

            "<input type='radio' id='da-swap' name='lang-swap' value='da'/>" +
            "<label style='margin-left: 8px' for=''>Dansk</label><br />" +

            "<input type='radio' id='de-swap' name='lang-swap' value='de'/>" +
            "<label style='margin-left: 8px' for='de-swap'>Deutsch</label><br />" +

            "<input type='radio' id='nl-swap' name='lang-swap' value='nl'/>" +
            "<label style='margin-left: 8px' for='nl-swap'>Nederlands</label><br />" +

            "<input type='radio' id='it-swap' name='lang-swap' value='it'/>" +
            "<label style='margin-left: 8px' for='it-swap'>Italiano</label><br />" +

            "<input type='radio' id='es-swap' name='lang-swap' value='es'/>" +
            "<label style='margin-left: 8px' for='es-swap'>Español</label><br />",
        showCloseButton: true,
        showCancelButton: true,
        focusConfirm: false,
        // select the current language when the list opens
        onRender: () => {
            $("input[name='lang-swap'][value='" + currentLang + "']").prop("checked", true);
        },
        // grab the selected value and return that only when confirmed
        preConfirm: () => {
            let newLang = document.querySelector('input[name="lang-swap"]:checked').value;
            currentLang = newLang;
            return newLang;
        }
    });
    // change the lang to the new one
    if (newlang) {
        changeLang(newlang);
    }
}