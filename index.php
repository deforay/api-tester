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

    <script>
        function updateHeaders() {
            var gzipYes = document.getElementById('gzip_yes').checked;
            var headersTextarea = document.getElementById('headers');

            if (gzipYes) {
                headersTextarea.value = "Accept-Encoding: gzip\nContent-Encoding: gzip";
            } else {
                headersTextarea.value = ""; // Clear headers if GZIP is 'No'
            }
        }

        function submitForm(event) {
            event.preventDefault(); // Prevent form submission

            // Get form data
            var formData = new FormData(document.getElementById('apiForm'));

            // Send the form data via AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'request.php', true);

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    document.querySelector('.response-output').innerHTML = `<pre>${xhr.responseText}</pre>`;
                } else {
                    document.querySelector('.response-output').innerHTML = `<pre>Error: ${xhr.status}</pre>`;
                }
            };

            xhr.onerror = function() {
                document.querySelector('.response-output').innerHTML = '<pre>Request failed</pre>';
            };

            xhr.send(formData); // Send the form data
        }
    </script>
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <!-- Form Section (Left Half) -->
            <div class="col-md-6 form-section">
                <h4 class="mb-4">API Request Form</h4>
                <form id="apiForm" class="mb-4" onsubmit="submitForm(event)">
                    <div class="mb-3">
                        <label for="url" class="form-label">URL:</label>
                        <input type="text" id="url" name="url" class="form-control" placeholder="Enter API URL">
                    </div>

                    <div class="mb-3">
                        <label for="payload" class="form-label">Payload (JSON):</label>
                        <textarea id="payload" name="payload" class="form-control" rows="10" placeholder="Enter JSON payload"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="token" class="form-label">Bearer Token:</label>
                        <input type="text" id="token" name="token" class="form-control" placeholder="Enter Bearer Token">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Send with GZIP Compression:</label><br>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="gzip_no" name="gzip" value="no" onclick="updateHeaders()" checked>
                            <label class="form-check-label" for="gzip_no">No</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" id="gzip_yes" name="gzip" value="yes" onclick="updateHeaders()">
                            <label class="form-check-label" for="gzip_yes">Yes</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="headers" class="form-label">Custom Headers (one per line):</label>
                        <textarea id="headers" name="headers" class="form-control" rows="5"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>

            <!-- Response Section (Right Half) -->
            <div class="col-md-6 response-section">
                <h4 class="mb-4">API Response</h4>
                <div class="response-output">
                    <!-- Response will be displayed here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
