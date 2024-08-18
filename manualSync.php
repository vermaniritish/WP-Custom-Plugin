<?php 
session_start();
require_once('../../../wp-config.php');

if(isset($_GET['filename']) && $_GET['filename'] && isset($_GET['listname']) && $_GET['listname'])
{
    $_GET['filename'] = trim($_GET['filename']);
    $_GET['listname'] = trim($_GET['listname']);
    global $wpdb;
    $results = $wpdb->get_results('Select * from verify_files where (list_name is null or list_name = "") and file LIKE "%'.$_GET['filename'].'"');
    if($results && isset($results[0]) && $results[0]->id)
    {
        $api_key = 'YldGcGJIWmxjbWxtYVdWeVlYQnB8UUZKNWJYbHpNamcwTUE9PXxkR0Z5Wld0QWNubHRlWE11WTI5dA==';
        $url = 'https://api.mailverifier.io/ViewListStatus/';

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 500,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'listname' => $_GET['listname']
            ),
            CURLOPT_HTTPHEADER => array(
                'api_key: ' . $api_key
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($response) {
            $response = json_decode($response, true);
            if( isset($response['response']['Total Emails']) && $response['response']['Total Emails'] > 0) {
                $wpdb->update('verify_files', ['completed' => 1, 'list_name' => $_GET['listname'] ], ['id' => $results[0]->id]);
                echo json_encode([ 'status' => true, 'link' => plugins_url('view.php', __FILE__).'?start_time=&list='.$_GET['listname'] ]); exit;
            }
        }
        echo json_encode(['status' => false, 'error' => 'Record having this listname does not exist on Mailverifier.']);
    }
    else
    {
        echo json_encode(['status' => false, 'error' => 'File with this name is not exist.']);
    }
}
else
{
    echo json_encode(['status' => false, 'error' => 'Please enter both file names to continue.']);
}
exit;