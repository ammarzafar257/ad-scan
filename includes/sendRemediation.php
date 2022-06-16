<?php

function send2api( $url, $array) {
    $data_string = json_encode($array);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
    );
    $response['content'] = curl_exec($ch);
    $response['errorNo'] = curl_errno( $ch );
    $response['errmsg']  = curl_error( $ch );
    $response['header'] = curl_getinfo( $ch );
    curl_close( $ch );
    return $response;
}

if(isset($_POST)){
    if(isset($_POST['urls'])){
        if (count($_POST['urls']) === 0) {
            http_response_code(400);
            echo "No Files Selected";
            exit();
        }
        if (!isset($_POST['clientID'])) {
            http_response_code(400);
            echo "Client ID is required";
            exit();
        }
        if (!isset($_POST['clientEmail'])) {
            http_response_code(400);
            echo "Client email is required";
            exit();
        }

        $urls = $_POST['urls'];
        $temp = array(
            'AppID'     => getenv('ADSCAN_API_KEY'), // required
            'ClientID'  => $_POST['clientID'],           // required
            'ContactID' => $_POST['adoContactID'],          // optional - this takes priority over email below
            'Email'     => $_POST['clientEmail'], // optional
            'urls'      => $_POST['urls'],     // required
            'Quote'     => 1,           // optional bool
            'Deadline'  => date("Y-m-d", time()), // optional
            'Contact'  => array( // optional for new contacts
                'FirstName'  => $_POST['clientFirst'],
                'LastName'  => $_POST['clientLast'],
                'Language'  => $_POST['lang'],
                'ADScanID'  => $_POST['userID'], // linking ID not yet implemented
            ),
        );

        $response = send2api('https://'.getenv('ADO_API_SERVER_NAME').'/adscan/create_job.php', $temp);

        if ($response['header']['http_code'] == 200) {
            // got a good reply from the API
            $content = json_decode($response['content']);
            if (!empty($content->JobID) && strlen($content->JobID) > 0) {
                // it's good to go
                http_response_code(200);
                echo "job created";
                exit();
            } else {
                // failed to create a job
                http_response_code($response['header']['http_code']);
                echo "failed to create a job: " . print_r($response);
                exit();
            }
        } else {
            http_response_code($response['header']['http_code']);
            echo "bad response from api";
        }
        exit;
    } else {
        http_response_code(400);
        echo "No urls were sent";
        exit();
    }
}

