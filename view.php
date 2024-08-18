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
                $blanks = 0;
                $uniqueEmails = [];
                $duplicates = 0;
                $emails = [];
                $pieChart = [];
                foreach($decoded_response['response'] as $k => $v) {
                    $k = array_keys($v);
                    $k = current($k);
                    $emails[$k] = $v[$k]; 
                }
                
                $badFormat = $catchAll = $valid = $invalid = 0;
                $row = 0;
                $col = $results[0]->email_col;
                if (($handle = fopen($results[0]->file, "r")) !== FALSE) {
                    while (($data = fgetcsv($handle, null, ",")) !== FALSE) {
                        if($row < 1) {$row++; continue;}
                        $data[$col] = trim(preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $data[$col]));
                        $blanks += (!$data[$col] ? 1 : 0);
                        
                        if($data[$col] && !filter_var($data[$col], FILTER_VALIDATE_EMAIL)) {
                            $badFormat++;
                            continue;
                        }

                        if(isset($emails[$data[$col]]) && $emails[$data[$col]] && $data[$col] && !in_array($data[$col], $uniqueEmails)) {
                            $uniqueEmails[] = $data[$col];
                        }
                        else if(isset($emails[$data[$col]]) && $emails[$data[$col]] && $data[$col] && in_array($data[$col], $uniqueEmails)) {
                            $duplicates++;
                        }

                        if(isset($emails[$data[$col]]) && $emails[$data[$col]] && (strtolower($emails[$data[$col]]) == 'valid')) {
                            $valid++;
                        }
                        elseif(isset($emails[$data[$col]]) && $emails[$data[$col]] && (strtolower($emails[$data[$col]]) == 'invalid')) {
                            $invalid++;
                        }
                        else {
                            $catchAll++;
                        }

                        $row++;
                    }
                    fclose($handle);
                }

                $pieChart = [
                    ['name' => 'Valid Emails', 'y' => (($valid/$row)*100) ],
                    ['name' => 'Invalid Emails', 'y' => (($invalid/$row)*100)],
                    ['name' => 'Catch All', 'y' => (($catchAll/$row)*100)],
                    ['name' => 'Duplicates Removed', 'y' => (($duplicates/$row)*100)],
                    ['name' => 'Bad Format', 'y' => (($badFormat/$row)*100)],
                    ['name' => 'Blanks', 'y' => (($blanks/$row)*100)],
                ];
                echo '<div class="w100">
                    <div class="w50 p1">
                        <p><strong>List Name:</strong> '.$results[0]->list_name.'</p>
                        <p><strong>Start Date/Time:</strong> '.$_GET['start_time'].'</p>
                        <div class="w100">
                            <div class="w50 p1">
                                <strong>Total Uploads:</strong> '.$row.'<br/>
                                <strong>Duplicates Removed:</strong> '.$duplicates.'<br/>
                                <strong>Bad Format:</strong> '.$badFormat.'<br/>
                                <strong>Blanks:</strong> '.$blanks.'
                            </div>
                            <div class="w50 p1">
                                <strong>Unique Emails:</strong> '.count($uniqueEmails).'<br/>
                                <strong>Valid Emails:</strong> '.$valid.'<br/>
                                <strong>Invalid Emails:</strong> '.$invalid.'<br/>
                                <strong>Catch All:</strong> '.$catchAll.'
                            </div>
                        </div>
                    </div>
                    <div class="w50 p1">
                        <p><a target="_blank" href="'.plugins_url('download.php', __FILE__).'?list='.$results[0]->list_name.'">Download Now</a></p>
                        <div class="w100">
                            <figure class="highcharts-figure">
                                <div id="c-container"></div>
                            </figure>
                            <script>
                                Highcharts.chart(\'c-container\', {
                                    chart: {
                                        type: \'pie\'
                                    },
                                    title: {
                                        text: \'Results Analysis\'
                                    },
                                    tooltip: {
                                        valueSuffix: \'%\'
                                    },
                                    plotOptions: {
                                        series: {
                                            allowPointSelect: true,
                                            cursor: \'pointer\',
                                            dataLabels: [{
                                                enabled: true,
                                                distance: 20
                                            }, {
                                                enabled: true,
                                                distance: -40,
                                                format: \'{point.percentage:.1f}%\',
                                                style: {
                                                    fontSize: \'1.2em\',
                                                    textOutline: \'none\',
                                                    opacity: 0.7
                                                },
                                                filter: {
                                                    operator: \'>\',
                                                    property: \'percentage\',
                                                    value: 10
                                                }
                                            }]
                                        }
                                    },
                                    series: [
                                        {
                                            name: \'Percentage\',
                                            colorByPoint: true,
                                            data: '.json_encode($pieChart).'
                                        }
                                    ]
                                });
                            </script>
                        </div>
                    </div>
                </div>'; die;
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