<?php
// Authentication and authorization helper functions for admin API endpoints

// Common JSON response function
if (!function_exists('send_json')) {
    function send_json($data, int $status = 200): void {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

function require_admin(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        send_json(['error' => 'Forbidden: Authentication required'], 403);
    }
    
    // Check if user is admin
    if ($_SESSION['user_type'] !== 'admin') {
        send_json(['error' => 'Forbidden: Admins only'], 403);
    }
}

function require_csrf_for_write(): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Check if CSRF token exists in session
        if (!isset($_SESSION['csrf_token'])) {
            send_json(['error' => 'CSRF token missing from session'], 403);
        }
        
        // Get token from header or cookie
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $cookieToken = $_COOKIE['csrf_token'] ?? '';
        
        // Verify token matches
        if (!hash_equals($_SESSION['csrf_token'], $headerToken ?: $cookieToken)) {
            send_json(['error' => 'CSRF token missing or invalid'], 403);
        }
    }
}

function generate_csrf_token(): string {
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes(32));
    }
    return bin2hex(openssl_random_pseudo_bytes(32));
}

function set_session_security(): void {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_strict_mode', 1);
    
    // Set session timeout to 30 minutes
    ini_set('session.gc_maxlifetime', 1800);
    session_set_cookie_params(1800);
}

function check_session_timeout(): void {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        // Session expired, destroy it
        session_unset();
        session_destroy();
        send_json(['error' => 'Session expired'], 401);
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
}
