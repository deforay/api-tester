<?php

// Enhanced API request handler with multiple HTTP methods support

function formatBytes($bytes) {
    if ($bytes == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB'];
    $factor = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $factor), 2) . ' ' . $units[$factor];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = $_POST['url'] ?? '';
    $method = $_POST['method'] ?? 'POST';
    $payload = $_POST['payload'] ?? '';
    $token = $_POST['token'] ?? '';
    $gzip = $_POST['gzip'] ?? 'no';
    $headersInput = $_POST['headers'] ?? '';

    // Validate required fields
    if (empty($url)) {
        echo "<div class='text-danger'><strong>Error:</strong> URL is required</div>";
        exit;
    }

    // Convert headers from textarea to an array
    $headersArray = explode("\n", trim($headersInput));
    $headersArray = array_filter(array_map('trim', $headersArray));

    // Add Authorization Bearer token if provided
    if (!empty($token)) {
        $headersArray[] = "Authorization: Bearer $token";
    }

    // Add Content-Type if not already specified and payload exists
    $hasContentType = false;
    foreach ($headersArray as $header) {
        if (stripos($header, 'content-type:') === 0) {
            $hasContentType = true;
            break;
        }
    }
    
    if (!$hasContentType && !empty($payload) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $headersArray[] = "Content-Type: application/json";
    }

    // Handle payload compression and preparation
    $requestPayload = '';
    if (!empty($payload) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        $requestPayload = ($gzip === 'yes') ? gzencode($payload) : $payload;
    }

    // Initialize cURL
    $curl = curl_init();

    // Base cURL options
    $curlOptions = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headersArray,
        CURLOPT_SSL_VERIFYPEER => false, // For testing purposes
    ];

    // Add payload for methods that support it
    if (!empty($requestPayload)) {
        $curlOptions[CURLOPT_POSTFIELDS] = $requestPayload;
    }

    curl_setopt_array($curl, $curlOptions);

    // Execute request and get response info
    $startTime = microtime(true);

    // Get response headers
    $responseHeaders = [];
    curl_setopt($curl, CURLOPT_HEADERFUNCTION,
        function($_, $header) use (&$responseHeaders) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) {
                return $len;
            }
            $responseHeaders[trim($header[0])] = trim($header[1]);
            return $len;
        }
    );

    $response = curl_exec($curl);
    $endTime = microtime(true);

    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
    $totalTime = curl_getinfo($curl, CURLINFO_TOTAL_TIME);
    $responseSize = curl_getinfo($curl, CURLINFO_SIZE_DOWNLOAD);
    $curlError = curl_error($curl);

    curl_close($curl);

    // Format and display response
    echo "<div class='card mb-2 border-0 shadow-sm' style='background-color: #f8f9fa;'>";
    echo "<div class='card-body py-2 px-3'>";
    echo "<div class='d-flex justify-content-between align-items-center flex-wrap gap-3'>";

    // Status
    echo "<div class='d-flex align-items-center'>";
    echo "<span class='text-muted small me-2'>Status:</span>";
    if ($httpCode >= 200 && $httpCode < 300) {
        echo "<span class='badge bg-success'>$httpCode</span>";
    } elseif ($httpCode >= 400) {
        echo "<span class='badge bg-danger'>$httpCode</span>";
    } else {
        echo "<span class='badge bg-warning text-dark'>$httpCode</span>";
    }
    echo "</div>";

    // Method
    echo "<div class='d-flex align-items-center'>";
    echo "<span class='text-muted small me-2'>Method:</span>";
    echo "<span class='method-badge method-$method'>$method</span>";
    echo "</div>";

    // Time
    echo "<div class='d-flex align-items-center'>";
    echo "<span class='text-muted small me-2'>Time:</span>";
    echo "<span class='small fw-semibold'>" . round($totalTime * 1000, 2) . " ms</span>";
    echo "</div>";

    // Size
    echo "<div class='d-flex align-items-center'>";
    echo "<span class='text-muted small me-2'>Size:</span>";
    echo "<span class='small fw-semibold'>" . formatBytes($responseSize) . "</span>";
    echo "</div>";

    // Type
    echo "<div class='d-flex align-items-center'>";
    echo "<span class='text-muted small me-2'>Type:</span>";
    echo "<span class='small fw-semibold'>" . ($contentType ?: 'Not specified') . "</span>";
    echo "</div>";

    echo "</div>";
    echo "</div>";
    echo "</div>";

    // Store headers for JavaScript
    echo "<script>window.lastResponseHeaders = " . json_encode($responseHeaders) . ";</script>";

    if (!empty($curlError)) {
        echo "<div class='alert alert-danger' role='alert'><strong>cURL Error:</strong> $curlError</div>";
    }

    echo "<div class='response-body'>";
    
    if ($response !== false) {
        // Try to detect and format JSON
        $decodedJson = json_decode($response);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Pretty print JSON without escaping
            $prettyResponse = json_encode($decodedJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            echo "<pre class='language-json'><code>" . $prettyResponse . "</code></pre>";
        } else {
            // Display raw response
            echo "<pre><code>" . htmlspecialchars($response) . "</code></pre>";
        }
    } else {
        echo "<div class='text-danger'>No response received</div>";
    }
    
    echo "</div>";
}