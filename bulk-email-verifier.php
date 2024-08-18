<?php
/*
Plugin Name: Bulk Email Verifier
Description: A plugin to verify bulk email addresses using MailVerifier API and download the verified emails.
Version: 1.0
Author: Your Name
*/
session_start();
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


// Enqueue necessary scripts and styles
function bev_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('bev-script', plugins_url('/bev-script.js', __FILE__), array('jquery'), 'V1.4', true);
    wp_enqueue_script('highchartjs', 'https://code.highcharts.com/highcharts.js', null, null, true);
    wp_enqueue_script('highchartjs', 'https://code.highcharts.com/modules/exporting.js', null, null, true);
    wp_enqueue_script('highchartjs', 'https://code.highcharts.com/modules/accessibility.js', null, null, true);
    wp_enqueue_script('datatables', 'https://cdn.datatables.net/2.0.8/js/dataTables.min.js', null, null, true);
    
    
    
    wp_localize_script('bev-script', 'bev_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    wp_enqueue_style('datatables', 'https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css', null, 'V1.2');
    wp_enqueue_style('bev-style', plugins_url('/bev-style.css', __FILE__), null, 'V1.4');
}
add_action('wp_enqueue_scripts', 'bev_enqueue_scripts');

// Function to verify multiple email addresses
function bev_verify_email_addresses($emails, $email_column, $csv_file) {

    $api_key = 'YldGcGJIWmxjbWxtYVdWeVlYQnB8UUZKNWJYbHpNamcwTUE9PXxkR0Z5Wld0QWNubHRlWE11WTI5dA=='; // Replace with your API key
    $url = 'https://api.mailverifier.io/BulkMailVerify/';

    $email_list = json_encode($emails);
    $list_name = explode('/', $csv_file);
    $list_name = end($list_name);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 600,
        CURLOPT_VERBOSE => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'listname' => $list_name,
            'emails' => $email_list
        ),
        CURLOPT_HTTPHEADER => array(
            'api_key: ' . $api_key
        ),
    ));

    $response = curl_exec($curl);
    $info = curl_getinfo($curl);
    $err = curl_error($curl);

    curl_close($curl);

    global $wpdb;
    $wpdb->insert('verify_files', [
        'list_name' => null,
        'file' => $csv_file,
        'email_col' => $email_column,
        'created' => date('Y-m-d H:i:s'),
        'response' => json_encode(['response' => $response, 'info' => $info, 'err' => $err])
    ]);
    

    if($response)
    {
        // Decode JSON response
        $_SESSION['last_Response'] = $response;
        $decoded_response = json_decode($response, true);
        if (isset($decoded_response['status']) && $decoded_response['status'] == 200 && $decoded_response['response']) {
            global $wpdb;
            $wpdb->insert('verify_files', [
                'list_name' => $decoded_response['response']['listname'],
                'file' => $csv_file,
                'email_col' => $email_column,
                'created' => date('Y-m-d H:i:s')
            ]);
            $_SESSION['email_list_response'] = $decoded_response['response'];
            $_SESSION['email_list_col'] = $email_column;
            return $decoded_response['response'];
        } else {
            return 'Error: Unable to get response message. Full response: ' . print_r($decoded_response, true);
        }
    }
    elseif ($err) {
        return 'Error: ' . $err;
    }
    else {
        return 'Error';
    }
}

// Handle AJAX request for email verification
function bev_verify_emails_ajax() {
    if (!isset($_POST['csv_file']) || !isset($_POST['email_column'])) {
        wp_send_json_error('Invalid request: Missing required parameters.');
    }

    $csv_file = $_POST['csv_file'];
    $email_column = intval($_POST['email_column']);

    // Read the CSV file
    $emails = array_map('str_getcsv', file($csv_file));
    $header = array_shift($emails);

    // Extract emails from the specified column
    $email_list = [];
    foreach ($emails as $row) {
        $em = trim(preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $row[$email_column]));
        if($em)
		$em = str_replace("'", '', $em); 
        $email_list[] = $em;
    }

    // Verify email addresses
    $result = bev_verify_email_addresses($email_list, $email_column, $csv_file);
    
    if(isset($result['listname']) && $result['listname']) 
        wp_send_json_success(array('status' => true));
    else
        wp_send_json_success(array('status' => false, 'message' => $result));
}
add_action('wp_ajax_bev_verify_emails', 'bev_verify_emails_ajax');
add_action( 'wp_ajax_nopriv_bev_verify_emails', 'bev_verify_emails_ajax' );

// AJAX handler for file upload and column extraction
function bev_upload_csv_ajax() {
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    $uploadedfile = $_FILES['email_file'];
    $upload_overrides = array('test_form' => false);
    $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
    if ($movefile && !isset($movefile['error'])) {
        $_SESSION['verifier_file'] = $movefile;
        $csv_file = $movefile['file'];
        $emails = array_map('str_getcsv', file($csv_file));
        $header = array_shift($emails);

        wp_send_json_success(array('csv_file' => $csv_file, 'header' => $header));
    } else {
        wp_send_json_error('File upload failed: ' . $movefile['error']);
    }
}
add_action('wp_ajax_bev_upload_csv', 'bev_upload_csv_ajax');
add_action( 'wp_ajax_nopriv_bev_upload_csv', 'bev_upload_csv_ajax' );

// Shortcode function for bulk email verifier
function bev_bulk_email_verifier_shortcode() {
    ob_start();
        echo '<pre>'; print_r($_SESSION['last_Response']); echo '</pre>';
    ?>

    <?php if(isset($_SESSION['email_list_response']) && $_SESSION['email_list_response']):?>
        <blockquote>
            <p style="color: green">
            I'm good with List name <strong><?php echo $_SESSION['email_list_response']['listname'] ?></strong> has uploaded <strong><?php echo $_SESSION['email_list_response']['total unique emails'] ?></strong> emails to verify successfully. <br />
            <a href="https://www.emailvalidationhq.com/?page_id=2784">Click here to go to the Results page and View Reports.</a>
            </p>
            <p>Note: Depending on the size of your list, it may take up to 5 minutes or more to validate the list.</p>
        </blockquote>
        <div id="download_link">
            <!-- <a href="<?php echo plugins_url('download.php', __FILE__) . '?list=' . $_SESSION['email_list_response']['listname'] ?>" target="_blank">Download Verified Emails</a> -->
            <a href="<?php echo plugins_url('removeFile.php', __FILE__) ?>" style="color: red; margin-left: 40px;">&#8592; Back</a>
        </div>
    <?php else: ?>
    <form id="upload_form" method="post" enctype="multipart/form-data">
        <?php if(isset($_SESSION['verifier_file']['file']) && file_exists($_SESSION['verifier_file']['file'])): ?>
        <!-- <blockquote>
            <p>Your file is uploaded</p>
            <p>
                <a href="<?php echo $_SESSION['verifier_file']['url'] ?>" class=""><?php $file = explode('/', $_SESSION['verifier_file']['file']);  echo end($file); ?></a>
                <a href="<?php echo plugins_url('removeFile.php', __FILE__) ?>" onclick="return confirm('Are you sure to remove this file?')" style="color: red; margin-left: 20px;">x Remove</a>
            </p>
        </blockquote> -->
        <?php else: ?>
        <input type="file" name="email_file" id="email_file" required>
        <input type="submit" name="submit" value="Upload CSV" id="verify_emails" >
        <?php endif; ?>
    </form>

    <?php if(isset($_SESSION['verifier_file']['file']) && file_exists($_SESSION['verifier_file']['file'])): ?>
    <div id="column_selector">
        <h3>Select Email Column</h3>
        <form id="select_column_form" method="post">
            <input type="hidden" name="csv_file" id="csv_file" value="<?php echo (isset($_SESSION['verifier_file']['file']) && file_exists($_SESSION['verifier_file']['file']) ? $_SESSION['verifier_file']['file'] : '')  ?>">
            <div id="column_options_container">
                <table style="border: 1px solid #eee; width: 100%;">
                    <thead>
                        <tr>
                            <?php 
                            $csv_file = isset($_SESSION['verifier_file']['file']) && file_exists($_SESSION['verifier_file']['file']) ? $_SESSION['verifier_file']['file'] : null;
                            if($csv_file):
                                $emails = array_map('str_getcsv', file($csv_file));
                                $header = array_shift($emails);
                                foreach($header as $index => $column):
                            ?>
                            <th style="text-align: left;">
                                <label>
                                    <input type="radio" name="email_column" value="<?php echo $index ?>">
                                    <?php echo $column ?>
                                </label>
                            </th>
                        <?php 
                            endforeach;
                        endif;
                        ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $row = 0;
                    if (($handle = fopen($_SESSION['verifier_file']['file'], "r")) !== FALSE) {
                        while (($data = fgetcsv($handle, null, ",")) !== FALSE) {
                            if($row < 1) {$row++; continue;}
                    ?>
                        <tr>
                            <?php foreach($data as $k => $v): ?>
                            <td><?php echo $v ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php
                            $row++;
                            if($row >= 5) break;
                        }
                        fclose($handle);
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <input type="button" name="" value="Cancel" onclick="window.location.href = '<?php echo plugins_url('removeFile.php', __FILE__) ?>';">
            <input type="submit" name="verify_emails"value="Verify Emails">
        </form>
    </div>
    <?php endif; ?>

    <div id="verification_progress" style="display:none;">
        <h3>Verification Progress</h3>
        <div id="progress_status"></div>
    </div>
    <?php endif; ?>

    <?php
    return ob_get_clean();
}
add_shortcode('bulk_email_verifier', 'bev_bulk_email_verifier_shortcode');
include 'listing.php';
?>
