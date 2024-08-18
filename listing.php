<?php 
add_shortcode('bulk_email_verifier_listing', 'bulk_email_verifier_listing');
$listing = [];
function bulk_email_verifier_listing() {
    $api_key = 'YldGcGJIWmxjbWxtYVdWeVlYQnB8UUZKNWJYbHpNamcwTUE9PXxkR0Z5Wld0QWNubHRlWE11WTI5dA=='; // Replace with your API key
    $url = 'https://api.mailverifier.io/ViewHistory/';
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_HTTPHEADER => array(
            'api_key: ' . $api_key
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    $response = json_decode($response, true);
    $listing = isset($response['response']) ? $response['response'] : [];
    curl_close($curl);

    $html = "
        <p style=\"text-align:right;\"><button type=\"button\"  class=\"btn btn-primary\" id=\"manual-sync\">Sync Manually</button></p>
        <table style='max-width: 100%;' id=\"MVTable\">
        <thead>
            <tr>
                <th>List Name</th>
                <th>Start Date/Time</th>
                <th>End Date/Time</th>
                <th>Unique Emails</th>
                <th>Verification Done</th>
                <th>Verification Pending</th>
                <th>Report</th>
            </tr>
        </thead>
        <tbody>";
        foreach($listing as $r):
            $html .= "<tr>
                <td>{$r['List Name']}</td>
                <td>{$r['Start Date Time']}</td>
                <td>{$r['End Date Time']}</td>
                <td>{$r['Total Emails']}</td>
                <td>{$r['Verification Done']}</td>
                <td>{$r['Verification Pending']}</td>
                <td><a class='view-report' href=\"javascript:;\" data-href=\"".plugins_url('view.php', __FILE__).'?start_time='.$r['Start Date Time'].'&list='.$r['List Name']."\">View Report</a></td>
            </tr>";
        endforeach;
        $html .= "</tbody></table>";
        $html .= '<div id="view-modal" class="view-modal" style="display:none">
            <div class="view-modal-wrapper w100">
                <div class="modal-header w100"><h2>My report</h2>
                    <a href="javascript:;" class="close-view-modal">x Close</a>
                </div>
                <div class="modal-body w100">
                    
                </div>
            </div>
        </div>
        <div id="manual-sync-modal"  class="view-modal" style="display:none">
            <div class="view-modal-wrapper w100">
                <div class="modal-header w100"><h2>My report</h2>
                    <a href="javascript:;" class="close-view-modal">x Close</a>
                </div>
                <div class="modal-body w100">
                    <form action="'.plugins_url('manualSync.php', __FILE__).'">
                        <div class="form-group p1">
                            <label>Enter Uploaded File Name</label>
                            <input type="form-control" name="filename" required />
                        </div>
                        <div class="form-group p1">
                            <label>Enter Mailverifier List Name</label>
                            <input type="form-control" name="listname" required />
                        </div>
                        <p class="error-message" style="display:none; color: red;"></p>
                        <div class="form-group p1">
                            <button class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';
    return $html;
}
?>