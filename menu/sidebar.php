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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat&family=Raleway&display=swap" rel="stylesheet">

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

    <script>
    const page_URL = window.location?.href.split('/');
    const pageName = page_URL[4] ? page_URL[4] : null;
    </script>
</head>

<body>
    <div class="sidenav">
        <div class=" wrapper">
            <!-- Sidebar  -->
            <nav id="sidebar" style="z-index: 1;">
                <div class="sidebar-header">
                    <h3>
                        <img src="images/logo.png" alt="logo">
                    </h3>
                    <strong>
                        <img src="images/fav.png" alt="logo">
                    </strong>
                </div>
                <div class="line"></div>
                <div class="close-btn">
                    <div class="icon" id="sidebarCollapse">
                        <img src="images/Group1401.png" alt="sidebartoggle">
                    </div>
                </div>
                <div class="list-unstyled">
                    <!-- My Dashboard -->
                    <div class="collapse-outer" v-if="showAllSideMenu">
                        <div class="collapse-head" onclick="SwitchOverallDash()">
                            <div class="side-icon">
                                <img src="images/Group1101.png" alt="my-dashboard">
                            </div>
                            <div class="titel-box">
                                <span>My Dashboard</span>
                                <img src="images/Path2390.png" alt="my-dashboard">
                            </div>
                        </div>
                    </div>
                    <!-- My URL List -->
                    <div class="collapse-outer" v-if="showAllSideMenu">
                        <div class="collapse-head" data-toggle="collapse" data-target="#collapseExample2">
                            <div class="side-icon">
                                <img src="images/Group1113.png" alt="my-url-list">
                            </div>
                            <div class="titel-box">
                                <span>My URL List </span>
                                <img src="images/Path2390.png" alt="my-url-list">
                            </div>
                        </div>
                        <div class="collapse" id="collapseExample2">
                            <div class="collapse-body">
                                <ul>
                                    <li v-for="(url, index) in urls">
                                        <a v-on:click="clickedDomain($event, url)" v-bind:id="'sidenav-' + index" href="javascript:void(0);">{{ url.split("//")[1] }}</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- My Profile -->
                    <div class="collapse-outer">
                        <div class="collapse-head" data-toggle="collapse" data-target="#my_profile_collapse">
                            <div class="side-icon">
                                <img src="images/Group1132.png" alt="my-profile">
                            </div>
                            <div class="titel-box">
                                <span>My Profile</span>
                                <img src="images/Path2390.png" alt="my-profile">
                            </div>
                        </div>
                        <div class="collapse" id="my_profile_collapse">
                            <div class="collapse-body">
                                <div class="my-profile">
                                    <div class="box2" data-toggle="collapse" data-target="#change_language">
                                        <div class="box">
                                            <i class="fa fa-envelope"></i>Change Language
                                        </div>
                                        <div class="box">
                                            <img src="images/Path2390.png" alt="change-language">
                                        </div>
                                    </div>
                                    <div class="collapse" id="change_language">
                                        <div class="collapse-body">
                                            <ul>
                                                <li onclick="updateLang('da')">
                                                    <a href="#">
                                                        Dansk
                                                    </a>
                                                </li>
                                                <li onclick="updateLang('it')">
                                                    <a href="#">
                                                        Italino
                                                    </a>
                                                </li>
                                                <li onclick="updateLang('en')">
                                                    <a href="#">
                                                        English
                                                    </a>
                                                </li>
                                                <li onclick="updateLang('de')">
                                                    <a href="#">
                                                        Deutsch
                                                    </a>
                                                </li>
                                                <li onclick="updateLang('fr')">
                                                    <a href="#">
                                                        Français
                                                    </a>
                                                </li>
                                                <li onclick="updateLang('nl')">
                                                    <a href="#">
                                                        Nederlands
                                                    </a>
                                                </li>
                                                <li onclick="updateLang('es')">
                                                    <a href="#">
                                                        Español
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="box2" id="inviteUser" v-if="client && client.isOwner" onclick="inviteNewUser()">
                                        <div class="box">
                                            <i class="fa fa-user"></i>Invite User
                                        </div>
                                        <div class="box">
                                            <img src="images/Path2390.png" alt="invite-user">
                                        </div>
                                    </div>
                                    <div class="box2" id="backToAdmin" v-if="client && client.companyName === 'AbleDocs'" onclick="backToAdmin()">
                                        <div class="box">
                                            <i class="fa fa-home"></i>Back to Admin
                                        </div>
                                        <div class="box">
                                            <img src="images/Path2390.png" alt="back-to-admin">
                                        </div>
                                    </div>
                                    <div class="box2" onclick="changePassword()">
                                        <div class="box">
                                            <i class="fa fa-user"></i>Change Password
                                        </div>
                                        <div class="box">
                                            <img src="images/Path2390.png" alt="change-password">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Divider -->
                    <div class="line"></div>
                    <!-- Logout -->
                    <div class="collapse-outer" onclick="logout()">
                        <div class="collapse-head">
                            <div class="side-icon">
                                <img src="images/logout.png" alt="logout">
                            </div>
                            <div class="titel-box">
                                <span>Log Out</span>
                                <img src="images/Path2390.png" alt="logout">
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </div>

    <!-- jQuery CDN - Slim version (=without AJAX) -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <!-- Popper.JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
            setTimeout($('#sidebar').toggleClass('active'), 2000);
        });

    </script>

    <script>
        function backToAdmin() {
            window.location.href = "master.php"
        }
    </script>

</body>

</html>