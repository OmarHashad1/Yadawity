<?php
require_once "db.php";

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

function parse_json_body(): array {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    return is_array($body) ? $body : [];
}

function sanitize($value) {
    if (is_array($value)) {
        return array_map('sanitize', $value);
    }
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token(): string {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes(32));
        }
        return bin2hex(openssl_random_pseudo_bytes(32));
    }
}

if (!function_exists('set_session_security')) {
    function set_session_security(): void {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.use_strict_mode', 1);
        
        // Set session timeout to 30 minutes
        ini_set('session.gc_maxlifetime', 1800);
        session_set_cookie_params(1800);
    }
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
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('Database connection not available');
    }

    if ($method === 'POST') {
        $body = sanitize(parse_json_body());
        
        // Validate required fields
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            send_json(['error' => 'Email and password are required'], 422);
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            send_json(['error' => 'Invalid email format'], 422);
        }
        
        // Query user from database
        $stmt = $pdo->prepare('SELECT user_id, email, password, user_type, first_name, last_name FROM users WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            send_json(['error' => 'Invalid credentials'], 401);
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            send_json(['error' => 'Invalid credentials'], 401);
        }
        
        // Check if user is admin
        if ($user['user_type'] !== 'admin') {
            send_json(['error' => 'Access denied: Admin privileges required'], 403);
        }
        
        // Start secure session
        set_session_security();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Generate CSRF token
        $csrfToken = generate_csrf_token();
        
        // Store user data in session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['csrf_token'] = $csrfToken;
    $_SESSION['last_activity'] = time();
    // Set admin_logged_in for admin session check
    $_SESSION['admin_logged_in'] = true;
        
        // Set CSRF token in cookie for client-side access
        setcookie('csrf_token', $csrfToken, [
            'expires' => time() + 1800, // 30 minutes
            'path' => '/',
            'httponly' => false, // Allow JavaScript access for AJAX requests
            'secure' => isset($_SERVER['HTTPS']),
            'samesite' => 'Strict'
        ]);
        
        send_json([
            'success' => true,
            'user_type' => $user['user_type'],
            'user_id' => $user['user_id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'csrf_token' => $csrfToken,
            'message' => 'Login successful'
        ]);
    }

    method_not_allowed($allowedMethods);
} catch (PDOException $e) {
    send_json(['error' => 'Database error', 'details' => $e->getMessage()], 500);
} catch (Throwable $e) {
    send_json(['error' => 'Server error', 'details' => $e->getMessage()], 500);
}
