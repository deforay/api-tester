<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Tester</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .method-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.6rem;
            border-radius: 0.375rem;
            font-weight: 600;
            display: inline-block;
            text-transform: uppercase;
        }

        .method-GET {
            background-color: #3b82f6;
            color: white;
        }

        .method-POST {
            background-color: #10b981;
            color: white;
        }

        .method-PUT {
            background-color: #f59e0b;
            color: white;
        }

        .method-DELETE {
            background-color: #ef4444;
            color: white;
        }

        .method-PATCH {
            background-color: #14b8a6;
            color: white;
        }

        .response-output {
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            background: #1f2937;
            color: #f3f4f6;
            padding: 1rem;
            border-radius: 0.375rem;
            max-height: 500px;
            overflow: auto;
        }

        .json-key {
            color: #a78bfa;
        }

        .json-value {
            color: #34d399;
        }

        .json-bracket {
            color: #9ca3af;
        }

        #response-label {
            display: none;
        }

        #history-heading,
        #clear-all-btn {
            display: none;
        }

        .history-item {
            cursor: pointer;
            transition: all 0.2s;
        }

        .history-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .delete-btn {
            cursor: pointer;
            color: #ef4444;
            font-size: 1.1rem;
            transition: color 0.2s;
            margin-left: 0.5rem;
        }

        .delete-btn:hover {
            color: #dc2626;
        }

        .gzip-info {
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }

        .json-tree .json-node {
            margin-left: 0.75rem;
        }

        .json-tree summary {
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            color: #0f172a;
        }

        .json-tree summary::-webkit-details-marker {
            margin-right: 0.35rem;
        }

        .json-tree .json-children {
            margin-left: 1rem;
            border-left: 1px dashed #d1d5db;
            padding-left: 0.75rem;
        }

        .json-leaf {
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            margin: 0.15rem 0;
        }

        .tree-tools {
            display: none;
        }

        .copy-btn {
            white-space: nowrap;
        }
    </style>

    <script>
        const MAX_HISTORY = 25;
        const MAX_TREE_NODES = 2000; // Safety limit to prevent freezing on very large payloads

        function generateUniqueId() {
            let now = new Date();
            let year = now.getFullYear().toString();
            let month = (now.getMonth() + 1).toString().padStart(2, '0');
            let day = now.getDate().toString().padStart(2, '0');
            let hour = now.getHours().toString().padStart(2, '0');
            let minute = now.getMinutes().toString().padStart(2, '0');
            let second = now.getSeconds().toString().padStart(2, '0');
            let dateTimeString = `${year}-${month}-${day}-${hour}-${minute}-${second}`;
            let randomString = Math.random().toString(36).substring(2, 8).toUpperCase();
            return `${dateTimeString}-${randomString}`;
        }

        function updateHeaders() {
            var gzipYes = document.getElementById('gzip_yes').checked;
            var headersTextarea = document.getElementById('headers');

            if (gzipYes) {
                if (!headersTextarea.value.includes('Accept-Encoding: gzip')) {
                    headersTextarea.value = (headersTextarea.value ? headersTextarea.value + '\n' : '') + "Accept-Encoding: gzip\nContent-Encoding: gzip";
                }
            } else {
                headersTextarea.value = headersTextarea.value.replace(/Accept-Encoding: gzip\n?/g, '').replace(/Content-Encoding: gzip\n?/g, '').trim();
            }
        }

        function formatJSON() {
            var payloadTextarea = document.getElementById('payload');
            var validationMessage = document.getElementById('json-validation-message');

            try {
                var jsonObj = JSON.parse(payloadTextarea.value);
                payloadTextarea.value = JSON.stringify(jsonObj, null, 2);
                validationMessage.innerHTML = '<div class="alert alert-success alert-dismissible fade show" role="alert">✓ JSON formatted successfully<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                setTimeout(() => validationMessage.innerHTML = '', 3000);
            } catch (e) {
                validationMessage.innerHTML = '<div class="alert alert-danger alert-dismissible fade show" role="alert">⚠ Invalid JSON: ' + e.message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        }

        function validateJSON() {
            var payloadTextarea = document.getElementById('payload');
            var validationMessage = document.getElementById('json-validation-message');

            if (!payloadTextarea.value.trim()) {
                validationMessage.innerHTML = '<div class="alert alert-warning alert-dismissible fade show" role="alert">⚠ Payload is empty<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                return;
            }

            try {
                JSON.parse(payloadTextarea.value);
                validationMessage.innerHTML = '<div class="alert alert-success alert-dismissible fade show" role="alert">✓ Valid JSON<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                setTimeout(() => validationMessage.innerHTML = '', 3000);
            } catch (e) {
                validationMessage.innerHTML = '<div class="alert alert-danger alert-dismissible fade show" role="alert">⚠ Invalid JSON: ' + e.message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        }

        function submitForm(event) {
            event.preventDefault();

            document.querySelector('.response-output').innerHTML = '<em>Waiting for response...</em>';

            var formData = new FormData(document.getElementById('apiForm'));
            var gzipValue = document.getElementById('gzip_yes').checked ? 'yes' : 'no';
            formData.set('gzip', gzipValue);

            var formObj = {
                id: generateUniqueId(),
                url: formData.get('url'),
                method: formData.get('method'),
                payload: formData.get('payload'),
                token: formData.get('token'),
                gzip: gzipValue,
                headers: formData.get('headers'),
                response: ''
            };

            let originalSize = new Blob([formObj.payload]).size;
            let compressedSize = originalSize;
            let percentageDecrease = 0;
            if (formObj.gzip === 'yes') {
                compressedSize = new Blob([pako.gzip(formObj.payload)]).size;
                percentageDecrease = ((originalSize - compressedSize) / originalSize * 100).toFixed(2);
            }

            document.querySelector('.gzip-info').innerText = `Original: ${originalSize} bytes, Sending: ${compressedSize} bytes, Reduction: ${percentageDecrease}%`;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'request.php', true);

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    formObj.response = xhr.responseText;

                    document.getElementById('responseTabs').style.display = 'flex';
                    const viewToggle = document.getElementById('viewToggle');
                    viewToggle.classList.remove('d-none');
                    viewToggle.style.display = 'inline-block';

                    // Parse the response to separate metadata from body
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(xhr.responseText, 'text/html');

                    // Extract metadata card
                    const metadataCard = doc.querySelector('.card');
                    const responseBody = doc.querySelector('.response-body');

                    if (metadataCard) {
                        document.getElementById('response-metadata').innerHTML = metadataCard.outerHTML;
                    }

                    let rawResponseText = xhr.responseText;

                    if (responseBody) {
                        rawResponseText = responseBody.textContent || responseBody.innerText;
                        document.querySelector('.response-output').innerHTML = responseBody.innerHTML;
                    } else {
                        document.querySelector('.response-output').innerHTML = xhr.responseText;
                    }

                    storeRawResponse(rawResponseText);

                    const responseLabel = document.getElementById('response-label');
                    responseLabel.innerText = 'Response From Server';
                    responseLabel.style.display = 'block';

                    if (window.lastResponseHeaders) {
                        displayHeaders(window.lastResponseHeaders);
                    }

                    saveToLocalStorage(formObj);
                    setView('pretty');
                } else {
                    document.querySelector('.response-output').innerHTML = `<pre>Error: ${xhr.status}</pre>`;
                }
            };

            xhr.onerror = function() {
                document.querySelector('.response-output').innerHTML = '<pre>Request failed</pre>';
            };

            xhr.send(formData);
        }

        function saveToLocalStorage(data) {
            let history = JSON.parse(localStorage.getItem('apiHistory')) || [];
            history.unshift(data);
            if (history.length > MAX_HISTORY) {
                history.pop();
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

            historyHeading.style.display = 'block';
            clearAllBtn.style.display = 'inline-block';

            historySection.innerHTML = '';

            history.forEach((entry, index) => {
                let historyItem = document.createElement('div');
                historyItem.classList.add('history-item', 'card', 'mb-2');

                const payloadPreview = entry.payload ? entry.payload.substring(0, 50) : 'No payload';
                const tokenPreview = entry.token ? entry.token.substring(0, 20) + '...' : 'None';
                const collapseId = `collapse-${index}`;

                historyItem.innerHTML = `
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1" onclick="populateFormFromHistory('${entry.id}')" style="cursor: pointer;">
                        <span class="method-badge method-${entry.method}">${entry.method}</span>
                        <span class="fw-bold ms-2">${entry.url.substring(0, 45)}${entry.url.length > 45 ? '...' : ''}</span>
                        <br>
                        <small class="text-muted">${entry.id}</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <button class="btn btn-sm btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" onclick="event.stopPropagation()">
                            ℹ️
                        </button>
                        <span class="delete-btn" onclick="event.stopPropagation(); deleteHistory('${entry.id}')">✕</span>
                    </div>
                </div>
                <div class="collapse mt-2" id="${collapseId}">
                    <div class="small text-muted">
                        <div><strong>Payload:</strong> ${payloadPreview}${entry.payload.length > 50 ? '...' : ''}</div>
                        <div><strong>Token:</strong> ${tokenPreview}</div>
                        <div><strong>GZIP:</strong> ${entry.gzip}</div>
                    </div>
                </div>
            </div>
        `;

                historySection.appendChild(historyItem);
            });
        }

        function populateForm(data) {
            document.getElementById('url').value = data.url;
            document.getElementById('payload').value = data.payload;
            document.getElementById('token').value = data.token;
            document.getElementById('method').value = data.method;
            document.getElementById('gzip_yes').checked = (data.gzip === 'yes');
            document.getElementById('headers').value = data.headers;
        }

        function populateFormFromHistory(id) {
            let history = JSON.parse(localStorage.getItem('apiHistory')) || [];
            const entry = history.find(item => item.id === id);

            if (entry) {
                populateForm(entry);
                displayResponse(entry.response);
                document.getElementById('response-label').innerText = 'Response from History ID: ' + entry.id;
                document.getElementById('response-label').style.display = 'block';

                document.getElementById('responseTabs').style.display = 'flex';
                const viewToggle = document.getElementById('viewToggle');
                viewToggle.classList.remove('d-none');
                viewToggle.style.display = 'inline-block';
            }
        }

        function displayResponse(response) {
            // Parse the response to separate metadata from body
            const parser = new DOMParser();
            const doc = parser.parseFromString(response, 'text/html');

            // Extract metadata card
            const metadataCard = doc.querySelector('.card');
            const responseBody = doc.querySelector('.response-body');

            if (metadataCard) {
                document.getElementById('response-metadata').innerHTML = metadataCard.outerHTML;
            }

            if (responseBody) {
                document.querySelector('.response-output').innerHTML = responseBody.innerHTML;
                storeRawResponse(responseBody.textContent || responseBody.innerText);
            } else {
                document.querySelector('.response-output').innerHTML = `<pre>${response}</pre>`;
                storeRawResponse(response);
            }

            setView('pretty');
        }

        function displayHeaders(headers) {
            const headersDiv = document.querySelector('.response-headers');
            let html = '<table class="table table-sm table-striped table-bordered"><thead><tr><th>Header</th><th>Value</th></tr></thead><tbody>';

            for (const [key, value] of Object.entries(headers)) {
                html += `<tr><td><strong>${key}</strong></td><td class="text-break">${value}</td></tr>`;
            }

            html += '</tbody></table>';
            headersDiv.innerHTML = html;
        }

        function deleteHistory(id) {
            let history = JSON.parse(localStorage.getItem('apiHistory')) || [];
            history = history.filter(item => item.id !== id);
            localStorage.setItem('apiHistory', JSON.stringify(history));
            renderHistory();
        }

        function clearAllHistory() {
            if (confirm('Are you sure you want to clear all history entries?')) {
                localStorage.removeItem('apiHistory');
                document.getElementById('history').innerHTML = '';
                document.getElementById('history-heading').style.display = 'none';
                document.getElementById('clear-all-btn').style.display = 'none';
            }
        }

        function copyResponse() {
            var responseText = document.querySelector('.response-output').innerText;
            navigator.clipboard.writeText(responseText).then(() => {
                alert('Response copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy: ', err);
            });
        }

        function setView(view) {
            // Toggle active state on view buttons
            document.querySelectorAll('#viewToggle button').forEach(btn => {
                const matchesView = btn.dataset.view === view;
                btn.classList.toggle('active', matchesView);
            });

            const responseOutput = document.querySelector('.response-output');
            const responseText = responseOutput.dataset.rawResponse || responseOutput.innerText || '';
            const treeTools = document.querySelector('.tree-tools');

            if (!responseText.trim()) return;

            try {
                const jsonObj = JSON.parse(responseText);

                if (view === 'pretty') {
                    const prettyJson = JSON.stringify(jsonObj, null, 2);
                    responseOutput.innerHTML = `<pre><code>${escapeHtml(prettyJson)}</code></pre>`;
                    if (treeTools) treeTools.style.display = 'none';
                } else if (view === 'tree') {
                    const nodeCount = countJsonNodes(jsonObj);
                    if (nodeCount > MAX_TREE_NODES) {
                        responseOutput.innerHTML = `<div class="alert alert-warning mb-2">Tree view disabled: payload too large (${nodeCount.toLocaleString()} nodes). Use Pretty or Raw.</div>`;
                        if (treeTools) treeTools.style.display = 'none';
                        return;
                    }
                    responseOutput.innerHTML = `<div class="json-tree">${formatJsonTree(jsonObj, null, true)}</div>`;
                    if (treeTools) treeTools.style.display = 'inline-flex';
                } else if (view === 'raw') {
                    responseOutput.innerHTML = `<pre><code>${escapeHtml(responseText)}</code></pre>`;
                    if (treeTools) treeTools.style.display = 'none';
                }
            } catch (e) {
                // If not JSON, just show as-is
                responseOutput.innerHTML = `<pre><code>${escapeHtml(responseText)}</code></pre>`;
                if (treeTools) treeTools.style.display = 'none';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function storeRawResponse(text) {
            document.querySelector('.response-output').dataset.rawResponse = (text || '').trim();
        }

        function formatJsonTree(obj, keyLabel = null, isRoot = false) {
            const keyHtml = keyLabel !== null
                ? `<span class="json-key">"${escapeHtml(keyLabel.toString())}"</span>: `
                : '';

            if (Array.isArray(obj)) {
                const children = obj.map((item, index) => {
                    return `<div class="json-node">${formatJsonTree(item, index)}</div>`;
                }).join('');

                return `
                    <details class="json-node" ${isRoot ? 'open' : ''}>
                        <summary>${keyHtml}<span class="json-bracket">Array [${obj.length}]</span></summary>
                        <div class="json-children">
                            ${children || '<div class="json-leaf text-muted">Empty array</div>'}
                        </div>
                    </details>
                `;
            }

            if (obj !== null && typeof obj === 'object') {
                const keys = Object.keys(obj);
                const children = keys.map((key) => {
                    return `<div class="json-node">${formatJsonTree(obj[key], key)}</div>`;
                }).join('');

                return `
                    <details class="json-node" ${isRoot ? 'open' : ''}>
                        <summary>${keyHtml}<span class="json-bracket">Object {${keys.length}}</span></summary>
                        <div class="json-children">
                            ${children || '<div class="json-leaf text-muted">Empty object</div>'}
                        </div>
                    </details>
                `;
            }

            // Primitive values
            const valueHtml = `<span class="json-value">${escapeHtml(JSON.stringify(obj))}</span>`;
            return `<div class="json-leaf">${keyHtml}${valueHtml}</div>`;
        }

        function toggleAllTreeNodes(open) {
            document.querySelectorAll('.json-tree details').forEach(d => {
                if (open) {
                    d.setAttribute('open', '');
                } else {
                    d.removeAttribute('open');
                }
            });
        }

        function copyCurrentView() {
            const responseOutput = document.querySelector('.response-output');
            const text = responseOutput ? responseOutput.innerText : '';
            if (!text) return;

            navigator.clipboard.writeText(text).catch(err => {
                console.error('Copy failed', err);
            });
        }

        function countJsonNodes(value) {
            let count = 1;
            if (Array.isArray(value)) {
                for (const item of value) {
                    count += countJsonNodes(item);
                    if (count > MAX_TREE_NODES) break;
                }
            } else if (value !== null && typeof value === 'object') {
                for (const key of Object.keys(value)) {
                    count += countJsonNodes(value[key]);
                    if (count > MAX_TREE_NODES) break;
                }
            }
            return count;
        }

        window.onload = function() {
            renderHistory();
        };
    </script>
</head>

<body>

    <div class="container-fluid py-4">
        <div class="row g-3">
            <!-- Form Section -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">API Request</h5>
                        <form id="apiForm" onsubmit="submitForm(event)">
                            <div class="mb-3">
                                <div class="row g-2">
                                    <div class="col-3">
                                        <select id="method" name="method" class="form-select">
                                            <option value="GET">GET</option>
                                            <option value="POST" selected>POST</option>
                                            <option value="PUT">PUT</option>
                                            <option value="PATCH">PATCH</option>
                                            <option value="DELETE">DELETE</option>
                                        </select>
                                    </div>
                                    <div class="col-9">
                                        <input type="text" id="url" name="url" class="form-control" placeholder="Enter API URL">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <button class="btn btn-outline-secondary btn-sm w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#authSection">
                                    🔒 Authentication
                                    <span class="float-end">▼</span>
                                </button>
                                <div id="authSection" class="collapse mt-2">
                                    <input type="text" id="token" name="token" class="form-control" placeholder="Bearer Token">
                                </div>
                            </div>

                            <div class="mb-3">
                                <button class="btn btn-outline-secondary btn-sm w-100 text-start" type="button" data-bs-toggle="collapse" data-bs-target="#headersSection">
                                    📋 Headers
                                    <span class="float-end">▼</span>
                                </button>
                                <div id="headersSection" class="collapse mt-2">
                                    <textarea id="headers" name="headers" class="form-control" rows="3" placeholder="Header: Value (one per line)"></textarea>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="gzip_yes" name="gzip" value="yes" onclick="updateHeaders()">
                                        <label class="form-check-label small" for="gzip_yes">
                                            Enable GZIP Compression
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">
                                    Payload
                                    <button type="button" class="btn btn-link btn-sm p-0 ms-2" onclick="formatJSON()">Format</button>
                                    <button type="button" class="btn btn-link btn-sm p-0 ms-1" onclick="validateJSON()">Validate</button>
                                </label>
                                <textarea id="payload" name="payload" class="form-control" rows="10" placeholder="Enter JSON payload"></textarea>
                                <div id="json-validation-message" class="mt-2"></div>
                                <div class="gzip-info"></div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                ▶️ Send Request
                            </button>
                        </form>

                        <div class="mt-4" style="position: relative;">
                            <h5 id="history-heading">History</h5>
                            <button id="clear-all-btn" class="btn btn-danger btn-sm" onclick="clearAllHistory()" style="position: absolute; top: 0; right: 0;">Clear All</button>
                            <div id="history"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Response Section -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">API Response</h5>
                        <div id="response-label" class="fw-bold mb-2"></div>

                        <ul id="responseTabs" class="nav nav-tabs d-none" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#bodyTab" type="button">Body</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#headersTab" type="button">Headers</button>
                            </li>
                            <li class="nav-item ms-auto">
                                <button class="btn btn-primary btn-sm" onclick="copyResponse()">
                                    📋 Copy
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content mt-2">
                            <div class="tab-pane fade show active" id="bodyTab" role="tabpanel">
                                <div id="response-metadata">
                                    <!-- Response metadata will be displayed here -->
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div id="viewToggle" class="btn-group btn-group-sm d-none" role="group">
                                        <button type="button" class="btn btn-outline-secondary active" data-view="pretty" onclick="setView('pretty')">Pretty</button>
                                        <button type="button" class="btn btn-outline-secondary" data-view="tree" onclick="setView('tree')">Tree</button>
                                        <button type="button" class="btn btn-outline-secondary" data-view="raw" onclick="setView('raw')">Raw</button>
                                    </div>
                                    <div class="btn-group btn-group-sm tree-tools" role="group">
                                        <button type="button" class="btn btn-outline-secondary" onclick="toggleAllTreeNodes(true)">Expand All</button>
                                        <button type="button" class="btn btn-outline-secondary" onclick="toggleAllTreeNodes(false)">Collapse All</button>
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm copy-btn" onclick="copyCurrentView()">Copy View</button>
                                </div>
                                <div class="response-output">
                                    <!-- Response will be displayed here -->
                                </div>
                            </div>
                            <div class="tab-pane fade" id="headersTab" role="tabpanel">
                                <div class="response-headers" style="max-height: 500px; overflow: auto;">
                                    <!-- Headers will be displayed here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pako/2.0.4/pako.min.js"></script>
</body>

</html>
