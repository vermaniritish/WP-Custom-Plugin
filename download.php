<?php 
session_start();
require_once('../../../wp-config.php');
function insertIntoIndexedArray($array, $key, $value, $position) {
    // Split the array into two parts
    $part1 = array_slice($array, 0, $position, true);
    $part2 = array_slice($array, $position, null, true);
    
    // Create a new array with the key-value pair
    $newElement = [$key => $value];
    
    // Merge the arrays back together
    $result = $part1 + $newElement + $part2;
    
    return $result;
}
if(isset($_GET['list']) && $_GET['list'])
{
    global $wpdb;
    $results = $wpdb->get_results('Select * from verify_files where list_name = "'.$_GET['list'].'"');
    
    if($results && isset($results[0]) && $results[0]->id)
    {
        $api_key = 'YldGcGJIWmxjbWxtYVdWeVlYQnB8UUZKNWJYbHpNamcwTUE9PXxkR0Z5Wld0QWNubHRlWE11WTI5dA==';
        $url = 'https://api.mailverifier.io/ViewResult/';

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

        if ($err) {
            return 'Curl error: ' . $err;
        } else {
            // Decode JSON response
            $decoded_response = json_decode($response, true);
            if( isset($decoded_response['status']) && $decoded_response['status'] == 200 && $decoded_response['response'])
            {
                
                $emails = [];
                foreach($decoded_response['response'] as $k => $v) {
                    $k = array_keys($v);
                    $k = current($k);
                    $emails[$k] = $v[$k]; 
                }

                
                
                $rows = [];
                $row = 0;
                if (($handle = fopen($results[0]->file, "r")) !== FALSE) {
                    while (($data = fgetcsv($handle, null, ",")) !== FALSE) {
                        $col = $results[0]->email_col;
                        $key = 'status';
                        $value = $row < 1 ? 'Status' : (isset($emails[$data[$col]]) && $emails[$data[$col]] ? $emails[$data[$col]] : '');
                        $newArray = insertIntoIndexedArray($data, $key, $value, $col+1);
                        $rows[] = array_values($newArray);  
                        $row++;
                    }
                    fclose($handle);
                    // echo '<pre>'; print_r($decoded_response); print_r($emails); print_r($rows); die;
                    // die;
                    $output = fopen('php://output', 'w');    
                    // Send headers to prompt the browser to download the file
                    header('Content-Type: text/csv');
                    header('Content-Disposition: attachment;filename="' . $_GET['list'] . '.csv' . '"');
                    
                    // Output the column headings if necessary
                    foreach ($rows as $row) {
                        fputcsv($output, $row);
                    }
                    
                    // Close the file pointer
                    fclose($output);
                    
                }
                else
                {
                    die('Error: Something went wrong. Please try again.');
                }
            }
            else
            {
                echo 'Error: Unable to get response message. Full response: ' . print_r($decoded_response, true);
                die;
            }
        }
    }
    else
    {
        die('Error: File is missing. Please try again.');
    }
}
else
{
    die('Error: File is missing. Please try again.');
}