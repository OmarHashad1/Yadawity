<?php
/**
 * Simple User Authentication Check API
 * 
 * Takes the token from cookie, validates it, and returns:
 * - user_id
 * - user_type 
 * - is_active
 * - message for the including file
 */

// Only set headers if called directly as API
if (basename($_SERVER['PHP_SELF']) === 'checkCredentials.php') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: http://localhost');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Credentials: true');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }
}

require_once 'db.php';

function checkUserCredentials() {
    global $db;
    
    // Ensure database connection is available
    if (!$db || $db->connect_error) {
        $db = new mysqli('127.0.0.1', 'root', '', 'yadawity');
        if ($db->connect_error) {
            return [
                'authenticated' => false,
                'user_id' => null,
                'user_type' => 'guest',
                'is_active' => false,
                'message' => 'Database connection failed'
            ];
        }
    }
    
    $response = [
        'authenticated' => false,
        'user_id' => null,
        'user_type' => 'guest',
        'is_active' => false,
        'message' => 'User not authenticated'
    ];
    
    try {
        // Check if user_login cookie exists
        if (!isset($_COOKIE['user_login'])) {
            $response['message'] = 'No authentication cookie found';
            return $response;
        }
        
        $cookieValue = $_COOKIE['user_login'];
        $parts = explode('_', $cookieValue, 2);
        
        if (count($parts) !== 2) {
            $response['message'] = 'Invalid cookie format';
            return $response;
        }
        
        $userId = (int)$parts[0];
        $cookieHash = $parts[1];
        
        // Get user info
        $stmt = $db->prepare("SELECT user_id, email, first_name, user_type, is_active, is_verified FROM users WHERE user_id = ? AND is_active = 1");
        if (!$stmt) {
            $response['message'] = 'Database prepare error: ' . $db->error;
            return $response;
        }
        
        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            $response['message'] = 'Database execute error: ' . $stmt->error;
            $stmt->close();
            return $response;
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $response['message'] = 'User not found or inactive';
            $stmt->close();
            return $response;
        }
        
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // Get active session
        $sessionStmt = $db->prepare("SELECT session_id, login_time FROM user_login_sessions WHERE user_id = ? AND is_active = 1 AND expires_at > NOW() ORDER BY login_time DESC LIMIT 1");
        if (!$sessionStmt) {
            $response['message'] = 'Session query prepare error: ' . $db->error;
            return $response;
        }
        
        $sessionStmt->bind_param("i", $userId);
        if (!$sessionStmt->execute()) {
            $response['message'] = 'Session execute error: ' . $sessionStmt->error;
            $sessionStmt->close();
            return $response;
        }
        
        $sessionResult = $sessionStmt->get_result();
        
        if ($sessionResult->num_rows === 0) {
            $response['message'] = 'No valid session found';
            $sessionStmt->close();
            return $response;
        }
        
        $session = $sessionResult->fetch_assoc();
        $sessionStmt->close();
        
        // Validate cookie hash
        $sessionToken = $session['session_id'];
        $loginTime = $session['login_time'];
        $cookieData = $sessionToken . '|' . $user['email'] . '|' . $user['user_id'] . '|' . $loginTime;
        $expectedHash = hash_hmac('sha256', $cookieData, $sessionToken . '_' . $user['email']);
        
        if (!hash_equals($cookieHash, $expectedHash)) {
            $response['message'] = 'Invalid authentication token';
            return $response;
        }
        
        // Success
        $response = [
            'authenticated' => true,
            'user_id' => $user['user_id'],
            'user_name' => $user['first_name'],
            'user_email' => $user['email'],
            'user_type' => strtolower($user['user_type']),
            'is_active' => (bool)$user['is_active'],
            'is_verified' => (bool)$user['is_verified'],
            'message' => "Welcome back, {$user['first_name']}!"
        ];
        
        return $response;
        
    } catch (Exception $e) {
        error_log('CheckCredentials Error: ' . $e->getMessage());
        $response['message'] = 'Authentication error occurred';
        return $response;
    }
}

// If called directly as API
if (basename($_SERVER['PHP_SELF']) === 'checkCredentials.php') {
    echo json_encode(checkUserCredentials());
    exit;
}

// For include usage - set global variables
$userCredentials = checkUserCredentials();
$isAuthenticated = $userCredentials['authenticated'];
$currentUserId = $userCredentials['user_id'];
$currentUserType = $userCredentials['user_type'];
$isUserActive = $userCredentials['is_active'];
$authMessage = $userCredentials['message'];
