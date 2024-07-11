<?php
// Include the library
require 'simple_html_dom.php';
mb_internal_encoding('UTF-8');
function download_table_as_csv($urls, $tableClass, $content) {
    // Generate a unique filename with timestamp
    $filename = 'table_data_' . date('Ymd_His') . '.csv';

    // Set CSV headers for UTF-8 encoding
    header('Content-Encoding: UTF-8');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
   

    // Open the CSV file in write mode
    $file = fopen('php://output', 'w');
   
fwrite($file, "\xEF\xBB\xBF"); // Write BOM (Byte Order Mark) for UTF-8

    // Define header
    $header = [
        'URL', 'Airport Name', 'Airport Code', 'Airport Address', 'Airport Contact Number', 
        'Arrivals', 'Departures', 'ICAO Code', 'IATA Code', 'Ticket Counter Hours', 
        'Official Website', 'Official Facebook', 'Official Twitter', 'Official YouTube', 
        'Official Instagram Account', 'Iframe'
    ];
    fputcsv($file, $header);

    // Loop through each URL
    foreach ($urls as $url) {
        $url = trim($url);
        if (!empty($url)) {
            // Create a DOM object
            $html = file_get_html($url);

            // Find all tables with the specified class
            $tables = $html->find("table.$tableClass");

            // Prepare an array to store table data
            $tableData = [];

            // Check all tables for the required data
            foreach ($tables as $table) {
                // Find all rows in the table
                $rows = $table->find('tr');
                foreach ($rows as $row) {
                    $cells = $row->find('td');
                    if (count($cells) == 2) {
                        $key = trim($cells[0]->plaintext);
                        $value = trim($cells[1]->plaintext);
                        $tableData[$key] = $value;
                    }
                }
            }

            // Flexible matching for each data field
            $fields = [
                'Airport Name' => ['Airport Name','Name','Name of the Airport','Airport'],
                'Airport Code' => ['Airport Code','Code'],
                'Airport Address' => ['Address', 'Terminal Address','Address of the Airport'], 
                'Airport Contact Number' => ['Contact Number','Contact No','Airport Contact No','Contact','Number','Phone No','Airport Phone Number'],
                'Arrivals' => ['Arrival','Arrivals','Terminal Arrival','Arrivals Terminal','Arrival Terminal','Terminal'],
                'Departures' => ['Departure','Departures Terminal','Departures','Terminal Departure','Departure Terminal','Terminal'],
                'ICAO Code' => ['ICAO Code','ICAO'],
                'IATA Code' => ['IATA Code','IATA'],
                'Ticket Counter Hours' => ['Ticket Counter Hours','Working Hours'],
                'Official Website' => ['Official Website','Website'],
                'Official Facebook' => ['Official Facebook','Facebook'],
                'Official Twitter' => ['Official Twitter','Twitter'],
                'Official YouTube' => ['Official YouTube','Youtube'],
                'Official Instagram Account' => ['Official Instagram Account','Instagram'],
            ];

            // Prepare row data
            $rowData = array($url);

            // Loop through each field and match keys
            foreach ($fields as $field => $possibleKeys) {
                $fieldValue = '';
                foreach ($possibleKeys as $possibleKey) {
                    foreach ($tableData as $key => $value) {
                        if (stripos($key, $possibleKey) !== false) {
                            $fieldValue = $value;
                            break 2; // Break both loops
                        }
                    }
                }
                // $rowData[] = $fieldValue;
                $rowData[] = html_entity_decode($fieldValue, ENT_QUOTES, 'UTF-8');
            }

            // Extract iframe content
            $iframe = $html->find('iframe', 0);
            if ($iframe) {
                if ($iframe->getAttribute('data-lazy-src')) {
                    $iframeSrc = $iframe->getAttribute('data-lazy-src');
                } else {
                    $iframeSrc = $iframe->src;
                }
                // Construct the complete iframe HTML tag
                $iframeContent = '<iframe src="' . $iframeSrc . '" width="' . $iframe->width . '" height="' . $iframe->height . '" style="' . $iframe->style . '" allowfullscreen="' . $iframe->allowfullscreen . '" loading="' . $iframe->loading . '" referrerpolicy="' . $iframe->referrerpolicy . '" data-rocket-lazyload="' . $iframe->getAttribute('data-rocket-lazyload') . '"></iframe>';
                $rowData[] = $iframeContent;
            } else {
                $rowData[] = '';
            }
            fputcsv($file, $rowData);
        } else {
            // Write error message if URL is empty
            fputcsv($file, array('URL is empty.'));
        }
    }

    // Close the CSV file
    fclose($file);
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the URLs, class, and content from the form input
    $urls = explode("\n", $_POST['urls']);
    $tableClass = trim($_POST['class']);
    $content = trim($_POST['content']);
    download_table_as_csv($urls, $tableClass, $content);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Download Table as CSV</title>
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 2em;
            background-color: #f8f9fa;
        }
        .form-container {
            background-color: #fff;
            padding: 2em;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="form-container">
            <h2 class="mb-4">Download Table as CSV</h2>
            <form method="post">
                <div class="form-group">
                    <label for="urls">Enter the URLs (one per line):</label>
                    <textarea id="urls" name="urls" class="form-control" rows="4" cols="50"></textarea>
                </div>
                <div class="form-group">
                    <label for="class">Enter the class:</label>
                    <input type="text" id="class" name="class" class="form-control">
                </div>
                <div class="form-group">
                    <label for="content">Enter the content:</label>
                    <input type="text" id="content" name="content" value="<td><strong>Airport Name</strong></td>" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Download Tables</button>
            </form>
        </div>
    </div>
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
</body>
</html>
