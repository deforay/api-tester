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

        .history-section {
            margin-top: 20px;
        }

        .history-item {
            cursor: pointer;
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .history-item:hover {
            background-color: #e0e0e0;
        }

        .delete-btn {
            cursor: pointer;
            color: red;
            margin-left: 10px;
        }

        .delete-btn:hover {
            text-decoration: underline;
        }

        .clear-all-btn {
            margin-top: 10px;
        }

        #history-heading,
        #clear-all-btn {
            display: none;
            /* Hidden by default */
        }

        .response-output {
            white-space: pre-wrap;
            /* Ensure response text is properly displayed */
        }

        #response-label {
            font-weight: bold;
            margin-bottom: 10px;
            display: none;
            /* Hidden by default */
        }
    </style>

    <script>
        const MAX_HISTORY = 25; // Maximum number of history items to store

        function generateUniqueId() {
            let now = new Date();
            let year = now.getFullYear().toString();
            let month = (now.getMonth() + 1).toString().padStart(2, '0'); // Month is zero-indexed, so add 1
            let day = now.getDate().toString().padStart(2, '0');
            let hour = now.getHours().toString().padStart(2, '0');
            let minute = now.getMinutes().toString().padStart(2, '0');
            let second = now.getSeconds().toString().padStart(2, '0');

            // Combine all to form a unique human-readable datetime ID
            let dateTimeString = `${year}-${month}-${day}-${hour}-${minute}-${second}`;

            // Generate a random 5-character alphanumeric string
            let randomString = Math.random().toString(36).substring(2, 8).toUpperCase(); // Generates a random string

            return `${dateTimeString}-${randomString}`;
        }



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
            var formObj = {
                id: generateUniqueId(), // Assign a unique ID
                url: formData.get('url'),
                payload: formData.get('payload'),
                token: formData.get('token'),
                gzip: formData.get('gzip'),
                headers: formData.get('headers'),
                response: '' // Placeholder for response
            };

            // Send the form data via AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'request.php', true);

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    formObj.response = xhr.responseText; // Store the response in formObj
                    document.querySelector('.response-output').innerHTML = `<pre>${xhr.responseText}</pre>`;
                    document.getElementById('response-label').innerText = 'Response From Server'; // Label for new response
                    document.getElementById('response-label').style.display = 'block'; // Show the label
                    saveToLocalStorage(formObj); // Save both request and response
                } else {
                    document.querySelector('.response-output').innerHTML = `<pre>Error: ${xhr.status}</pre>`;
                }
            };

            xhr.onerror = function() {
                document.querySelector('.response-output').innerHTML = '<pre>Request failed</pre>';
            };

            xhr.send(formData); // Send the form data
        }

        function saveToLocalStorage(data) {
            let history = JSON.parse(localStorage.getItem('apiHistory')) || [];
            history.unshift(data); // Add new entry at the beginning
            if (history.length > MAX_HISTORY) {
                history.pop(); // Remove oldest entry if history exceeds the limit
            }
            localStorage.setItem('apiHistory', JSON.stringify(history));
            renderHistory();
        }

        function renderHistory() {
            let history = JSON.parse(localStorage.getItem('apiHistory')) || [];
            let historySection = document.getElementById('history');
            let historyHeading = document.getElementById('history-heading');
            let clearAllBtn = document.getElementById('clear-all-btn');

            if (history.length === 0) {
                historyHeading.style.display = 'none';
                clearAllBtn.style.display = 'none';
                return;
            }

            // Show heading and clear button if history exists
            historyHeading.style.display = 'block';
            clearAllBtn.style.display = 'inline-block';

            historySection.innerHTML = ''; // Clear existing history

            history.forEach((entry) => {
                let historyItem = document.createElement('div');
                historyItem.classList.add('history-item');

                historyItem.innerHTML = `
            <strong>Request ID: ${entry.id}</strong><br>
            URL: ${entry.url}<br>
            Payload: ${entry.payload.substring(0, 100)}...<br>
            Token: ${entry.token}<br>
            GZIP: ${entry.gzip}<br>
            <span class="delete-btn" onclick="deleteHistory('${entry.id}')">Delete</span>
        `;
                historyItem.onclick = function() {
                    populateForm(entry);
                    displayResponse(entry.response); // Show response for this history item
                    document.getElementById('response-label').innerText = 'Response from History ID : ' + entry.id; // Label for history response
                    document.getElementById('response-label').style.display = 'block'; // Show the label
                };

                historySection.appendChild(historyItem);
            });
        }

        function populateForm(data) {
            document.getElementById('url').value = data.url;
            document.getElementById('payload').value = data.payload;
            document.getElementById('token').value = data.token;
            document.getElementById(data.gzip === 'yes' ? 'gzip_yes' : 'gzip_no').checked = true;
            document.getElementById('headers').value = data.headers;
        }

        function displayResponse(response) {
            document.querySelector('.response-output').innerHTML = `<pre>${response}</pre>`;
        }

        function deleteHistory(id) {
            let history = JSON.parse(localStorage.getItem('apiHistory')) || [];
            history = history.filter(item => item.id !== id); // Remove entry with matching ID
            localStorage.setItem('apiHistory', JSON.stringify(history));
            renderHistory(); // Re-render history
        }

        function clearAllHistory() {
            localStorage.removeItem('apiHistory'); // Clear all history from localStorage
            document.getElementById('history').innerHTML = ''; // Clear the visible history section
            document.getElementById('history-heading').style.display = 'none'; // Hide the history heading
            document.getElementById('clear-all-btn').style.display = 'none'; // Hide the clear-all button
        }

        // Render history on page load
        window.onload = function() {
            renderHistory();
        };
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

                <!-- History Section -->
                <h5 id="history-heading">Request History</h5>
                <div id="history"></div>
                <button id="clear-all-btn" class="btn btn-danger clear-all-btn" onclick="clearAllHistory()">Clear All</button>
            </div>

            <!-- Response Section (Right Half) -->
            <div class="col-md-6 response-section">
                <h4 class="mb-4">API Response</h4>
                <div id="response-label">Response Label</div> <!-- Label to indicate New or History response -->
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
