<?php
require_once('../../../wp-config.php');
global $wpdb;
$results = $wpdb->get_results('Select * from verify_files where list_name is not null and completed = 0 order by id desc limit 1');

if($results && isset($results[0]) && $results[0]->id){
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
            'listname' => $results[0]->list_name
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
        if( isset($response['response']['Total Emails']) && $response['response']['Total Emails'] > 0 && ($response['response']['Total Emails'] == $response['response']['Verification Done'] || $response['response']['Verification Pending'] < 1) )
        {
            $to = 'vermani.ritish@gmail.com';
            $subject = 'Email Verification for ' . $results[0]->list_name . ' is completed.';
            $message = '<p>Hi</p><p>Your bulk email verification for '.$results[0]->list_name.' is completed. <a href="https://www.emailvalidationhq.com/elist-verifier-results/" target="_blank">Click here</a> to view the report.</p>';
            $headers = array('From: emailvalidationhq.com <admin@emailvalidationhq.com>', 'Content-Type: text/html; charset=UTF-8');
            $attachments = [];

            wp_mail( $to, $subject, $message, implode( PHP_EOL, $headers));
            $wpdb->update('verify_files', ['completed' => 1], ['id' => $results[0]->id]);
        }

    } else {
        return 'Curl error: ' . $err;
    }
}