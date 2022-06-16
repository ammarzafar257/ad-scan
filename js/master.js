sessionStorage.setItem('viewStatus', null);
sessionStorage.setItem("uaList", null);

var crawlsTable = new Vue({
    el: '#crawlsTable',
    data: {
        crawls: [],
    },
    methods: {
        /**
         * Swaps to dashboard and changes the user clientID to that of the selected crawl
         * @param id - clientID from crawl, matchs client ID in ADO
         * @param crawlID - ID of the crawl from adscan DB, used for selecting that scan
         */
        loginAs: function(id, crawlID) {
            sessionStorage.setItem('clientID', id);
            sessionStorage.setItem('fromMaster', crawlID);
            window.location.href = 'index.php';
        },
        changeType: function(currentType, ID) {
            fetch("includes/changeType.php?ScanType=" + currentType + "&ID=" + ID)
                .then((res) => {
                    if (res.status === 200) {
                        location.reload();
                    } else {
                        alert("Error occurred updating the crawl type");
                    }
                })
                .catch((err) => {
                    alert("An error has occurred");
                    console.error(err);
                });
        }
    }
});

fetch('scanAPI.php?action=getAllcrawls').then((res) => {
    return res.json();
})
.then((data => {
   crawlsTable.crawls = data;
}))
.catch((err) => {
   alert("An error has occurred");
   console.error(err);
});