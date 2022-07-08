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

    <!-- Font Awesome JS -->
    <link media="all" type="text/css" rel="stylesheet" href="https://site-assets.fontawesome.com/releases/v6.1.1/css/all.css">

    <!-- Recently Added End -->

    <link rel="shortcut icon" type="image/x-icon" href="images/favicon.ico">

    <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css" />
    <link type="text/css" rel="stylesheet" href="//unpkg.com/bootstrap-vue@latest/dist/bootstrap-vue.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

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

    <script src="https://cdn.syncfusion.com/ej2/19.2.46/ej2-vue-es5/dist/ej2-vue.min.js"></script>
    <link rel="stylesheet" href="https://cdn.syncfusion.com/ej2/material.css">

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            checkPage();
        });

        function checkPage() {
            const path = window.location.pathname;
            let index = path.indexOf("index.php");
            if (index != -1) {
                document.getElementById('pageTitle').innerHTML = 'Main Dashboard';
                $("#main-dashboard").removeClass('nav-link').addClass('nav-link active');
            } else {
                let index = path.indexOf("scanhistory.php");
                if (index != -1) {
                    document.getElementById('pageTitle').innerHTML = 'Scan History';
                    $("#scan-history").removeClass('nav-link').addClass('nav-link active');
                }
            }
        }
    </script>
</head>

<body>
    <div class="top-nav" id="main-app-header">
        <div class="topbar">
            <img src="images/esbjerg.png" alt="">
            <div class="dropdown">
                <div class=" dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                    Esbjerg.dk
                </div>
                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                    <li><a class="dropdown-item" href="#">Action</a></li>
                    <li><a class="dropdown-item" href="#">Another action</a></li>
                    <li><a class="dropdown-item" href="#">Something else here</a></li>
                </ul>
            </div>
        </div>
        <nav class="navbar navbar-expand-lg navbar-light ">
            <div class="container-fluid">
                <a class="navbar-brand" href="#" id="pageTitle">Main Dashboard</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTogglerDemo02" aria-controls="navbarTogglerDemo02" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" id="main-dashboard" aria-current="page" href="index.php">Main Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="scanhistory.php" id="scan-history">Scan History
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Help</a>
                        </li>
                        <li class="nav-item" onclick="logout()">
                            <a class="nav-link" href="#">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</body>

</html>