<?php
session_start();
// check for user logged in
if (!isset($_SESSION["userCompanyId"])) {
    header("Location: ./login/login.php");
    exit();
} else {
    if ($_SESSION["userCompanyId"] !== 1) {
        header("Location: ./login/login.php");
        exit();
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico">
    <title>ADscan - master</title>
    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.12/dist/vue.min.js"></script>
    <script defer src="js/master.js"></script>
</head>
<body>
    <table id="crawlsTable" class="mt-4 mb-3 table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Client ID</th>
                <th>Start URL</th>
                <th>Status</th>
                <th>Scan Type</th>
                <th>Start Time</th>
                <th>End time</th>
                <th>Change Type</th>
                <th>Total</th>
                <th>Compliant</th>
                <th>Non-Compliant</th>
                <th>Untagged</th>
                <th>Offsite</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="crawl in crawls">
                <td><a role="button" href="#" v-bind:aria-label="'login for crawl ' +  crawl.ID" v-on:click="loginAs( crawl.clientID , crawl.ID)">{{ crawl.ID }}</a></td>

                <td>{{ crawl.clientID }}</td>
                <td>{{ crawl.starturl }}</td>

                <td v-if="crawl.status === 'Crawling'" style="color: green; font-weight: bold">{{ crawl.status }}</td>
                <td v-else-if="crawl.status === 'Complete' && crawl.Files_Found !== crawl.Files_Error + crawl.Files_Scanned" style="color: #C78100; font-weight: bold">Scanning</td>
                <td v-else-if="crawl.status !== 'Complete'" style="color: red">{{ crawl.status }}</td>
                <td v-else>{{ crawl.status }}</td>

                <td>{{ crawl.ScanType }}</td>

                <td>{{ crawl.crawl_time_start }}</td>
                <td>{{ crawl.crawl_time_end }}</td>

                <td><button class="btn btn-primary" v-on:click="changeType(crawl.ScanType, crawl.ID)">Change Scan Type</button></td>

                <td class="font-weight-bold text-center">{{ crawl.compliant + crawl.nonCompliant + crawl.untagged + crawl.offsiteFiles}}</td>
                <td class="text-center">{{ crawl.compliant }}</td>
                <td class="text-center">{{ crawl.nonCompliant }}</td>
                <td class="text-center">{{ crawl.untagged }}</td>
                <td class="text-center">{{ crawl.offsiteFiles }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
