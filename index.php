<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Request Form</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
        }

        .response-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            min-height: 400px;
        }

        .form-section {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <!-- Form Section (Left Half) -->
            <div class="col-md-6 form-section">
                <h4 class="mb-4">API Request Form</h4>
                <form method="POST" class="mb-4">
                    <div class="mb-3">
                        <label for="url" class="form-label">URL:</label>
                        <input type="text" id="url" name="url" class="form-control" value="<?php echo $_POST['url'] ?? ''; ?>" placeholder="Enter API URL">
                    </div>

                    <div class="mb-3">
                        <label for="payload" class="form-label">Payload (JSON):</label>
                        <textarea id="payload" name="payload" class="form-control" rows="10" placeholder="Enter JSON payload"><?php echo $_POST['payload'] ?? ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="token" class="form-label">Bearer Token:</label>
                        <input type="text" id="token" name="token" class="form-control" value="<?php echo $_POST['token'] ?? ''; ?>" placeholder="Enter Bearer Token">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Send with GZIP Compression:</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="gzip_yes" name="gzip" value="yes" <?php echo ($_POST['gzip'] ?? 'yes') === 'yes' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="gzip_yes">Yes</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="gzip_no" name="gzip" value="no" <?php echo ($_POST['gzip'] ?? 'yes') === 'no' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="gzip_no">No</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>

            <!-- Response Section (Right Half) -->
            <div class="col-md-6 response-section">
                <h4 class="mb-4">API Response</h4>
                <div class="response-output">
                    <?php
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $url = $_POST['url'];
                        $payload = $_POST['payload'];
                        $token = $_POST['token'];
                        $gzip = $_POST['gzip'];

                        // If GZIP is selected, compress the payload
                        if ($gzip === 'yes') {
                            $gzippedPayload = gzencode($payload);
                            $headers = [
                                "Accept-Encoding: gzip",
                                "Content-Encoding: gzip",
                                "Content-Type: application/json",
                                "Authorization: Bearer $token"
                            ];
                        } else {
                            $gzippedPayload = $payload;
                            $headers = [
                                "Accept-Encoding: gzip",
                                "Content-Type: application/json",
                                "Authorization: Bearer $token"
                            ];
                        }

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
                            CURLOPT_HTTPHEADER => $headers,
                        ]);

                        $response = curl_exec($curl);
                        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                        curl_close($curl);

                        echo "<pre>";
                        echo "<strong>URL:</strong> $url <br>";
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
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
