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
    </style>

    <script>
        const MAX_HISTORY = 25; // Maximum number of history items to store

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
                url: formData.get('url'),
                payload: formData.get('payload'),
                token: formData.get('token'),
                gzip: formData.get('gzip'),
                headers: formData.get('headers')
            };

            // Save form data to localStorage
            saveToLocalStorage(formObj);

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
            historySection.innerHTML = ''; // Clear existing history

            history.forEach((entry, index) => {
                let historyItem = document.createElement('div');
                historyItem.classList.add('history-item');

                // Display latest entry as the highest number
                let displayIndex = history.length - index; // Numbering starts from the highest

                historyItem.innerHTML = `
            <strong>Request ${displayIndex}</strong><br>
            URL: ${entry.url}<br>
            Payload: ${entry.payload.substring(0, 100)}...<br>
            Token: ${entry.token}<br>
            GZIP: ${entry.gzip}<br>
            <span class="delete-btn" onclick="deleteHistory(${index})">Delete</span>
        `;
                historyItem.onclick = function() {
                    populateForm(entry);
                };

                historySection.appendChild(historyItem);
            });

            // Add a clear-all button
            let clearAllBtn = document.createElement('button');
            clearAllBtn.classList.add('btn', 'btn-danger', 'clear-all-btn');
            clearAllBtn.innerText = "Clear All";
            clearAllBtn.onclick = function() {
                clearAllHistory();
            };
            historySection.appendChild(clearAllBtn);
        }

        function populateForm(data) {
            document.getElementById('url').value = data.url;
            document.getElementById('payload').value = data.payload;
            document.getElementById('token').value = data.token;
            document.getElementById(data.gzip === 'yes' ? 'gzip_yes' : 'gzip_no').checked = true;
            document.getElementById('headers').value = data.headers;
        }

        function deleteHistory(index) {
            let history = JSON.parse(localStorage.getItem('apiHistory')) || [];
            history.splice(index, 1); // Remove entry at the given index
            localStorage.setItem('apiHistory', JSON.stringify(history));
            renderHistory(); // Re-render history
        }

        function clearAllHistory() {
            localStorage.removeItem('apiHistory'); // Clear all history
            renderHistory(); // Re-render history
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
                <div class="history-section">
                    <h5>Request History</h5>
                    <div id="history"></div>
                </div>
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
