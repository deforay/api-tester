<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = $_POST['url'];
    $payload = $_POST['payload'];
    $token = $_POST['token'];
    $gzip = $_POST['gzip'];
    $headersInput = $_POST['headers'];

    // Convert headers from textarea to an array
    $headersArray = explode("\n", trim($headersInput));
    $headersArray = array_filter(array_map('trim', $headersArray)); // Trim and remove empty lines

    // Append Authorization Bearer token to headers
    $headersArray[] = "Authorization: Bearer $token";

    // If GZIP is selected, compress the payload
    $gzippedPayload = ($gzip === 'yes') ? gzencode($payload) : $payload;

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $gzippedPayload,
        CURLOPT_HTTPHEADER => $headersArray,
    ]);

    $response = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    echo "<pre>";
    echo "<strong>Response Code:</strong> $httpcode <br><br>";

    // Check if the response is JSON and pretty print it
    if ($response !== false && json_decode($response)) {
        $prettyResponse = json_encode(json_decode($response), JSON_PRETTY_PRINT);
        echo $prettyResponse;
    } else {
        // If the response isn't JSON or curl failed, just print it as it is
        echo $response;
    }
    echo "</pre>";
}
