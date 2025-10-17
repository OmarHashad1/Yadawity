<?php
header('Content-Type: application/json');

$allowedMethods = ['POST', 'OPTIONS', 'HEAD'];

function send_json($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function method_not_allowed(array $methods): void {
    header('Allow: ' . implode(', ', $methods));
    send_json(['error' => 'Method Not Allowed'], 405);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'HEAD') {
    header('Allow: ' . implode(', ', $allowedMethods));
    exit;
}

if ($method === 'OPTIONS') {
    header('Allow: ' . implode(', ', $allowedMethods));
    header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods));
    header('Access-Control-Allow-Headers: Content-Type');
    exit;
}

if (!in_array($method, $allowedMethods, true)) {
    method_not_allowed($allowedMethods);
}

try {
    if ($method === 'POST') {
        // Start session if not already started
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Clear CSRF token cookie
        setcookie('csrf_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => false,
            'secure' => isset($_SERVER['HTTPS']),
            'samesite' => 'Strict'
        ]);
        
        // Destroy session
        session_unset();
        session_destroy();
        
        send_json([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }

    method_not_allowed($allowedMethods);
} catch (Throwable $e) {
    send_json(['error' => 'Server error', 'details' => $e->getMessage()], 500);
}
