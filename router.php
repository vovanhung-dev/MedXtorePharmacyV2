<?php
/**
 * Router for PHP Built-in Server
 * Handles API routing and static file serving
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Remove query string
$uri = strtok($uri, '?');

// If it's a real file or directory, serve it directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Handle API routes
if (preg_match('#^/api/pos/(.+)$#', $uri, $matches)) {
    $apiFile = __DIR__ . '/api/pos/' . $matches[1] . '.php';

    // Handle requests to files with extensions already
    if (!file_exists($apiFile)) {
        $apiFile = __DIR__ . '/api/pos/' . $matches[1];
    }

    if (file_exists($apiFile)) {
        require $apiFile;
        exit;
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'API endpoint not found']);
        exit;
    }
}

// Handle other API routes (if needed in future)
if (preg_match('#^/api/(.+)$#', $uri, $matches)) {
    $apiFile = __DIR__ . '/api/' . $matches[1] . '.php';

    if (!file_exists($apiFile)) {
        $apiFile = __DIR__ . '/api/' . $matches[1];
    }

    if (file_exists($apiFile)) {
        require $apiFile;
        exit;
    }
}

// For root, redirect to index.php
if ($uri === '/') {
    require __DIR__ . '/index.php';
    exit;
}

// Otherwise, let PHP's built-in server handle it
return false;
