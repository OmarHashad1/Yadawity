<?php
require_once "db.php";

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}


function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function validateUserAuthentication($db) {
    $user_id = null;
    
    // First check for user_login cookie (primary method from login.php)
    if (isset($_COOKIE['user_login'])) {
        $user_id = validateUserLoginCookie($db);
    }
    // Fallback: Check for session_id cookie (if still used elsewhere)
    elseif (isset($_COOKIE['session_id'])) {
        $user_id = validateSessionCookie($db);
    }
    // Fallback: Check for simple user_id cookie (legacy support)
    elseif (isset($_COOKIE['user_id'])) {
        $user_id = validateUserIdCookie($db);
    }
    // No valid authentication found
    else {
        throw new Exception('No session found. Please log in.');
    }
    
    return $user_id;
}


function validateUserLoginCookie($db) {
    $cookie_parts = explode('_', $_COOKIE['user_login'], 2);
    if (count($cookie_parts) !== 2) {
        throw new Exception('Invalid cookie format. Please log in again.');
    }
    
    $user_id = intval($cookie_parts[0]);
    $cookie_hash = $cookie_parts[1];
    
    // Validate user_id is positive integer
    if ($user_id <= 0) {
        throw new Exception('Invalid user session. Please log in again.');
    }
    
    // Verify user exists and is active, and validate cookie hash
    $stmt = $db->prepare("SELECT u.user_id, u.email, s.login_time FROM users u 
                         LEFT JOIN user_login_sessions s ON u.user_id = s.user_id 
                         WHERE u.user_id = ? AND u.is_active = 1 
                         AND s.is_active = 1 AND s.expires_at > NOW() 
                         ORDER BY s.login_time DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Database query failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        $stmt->close();
        throw new Exception('User session not found or expired. Please log in again.');
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Verify cookie hash matches expected pattern
    $expected_hash = hash('sha256', $user['email'] . $user['login_time'] . 'yadawity_salt');
    
    if (!hash_equals($expected_hash, $cookie_hash)) {
        throw new Exception('Invalid session. Please log in again.');
    }
    
    return $user_id;
}

function validateSessionCookie($db) {
    $session_id = $_COOKIE['session_id'];
    
    // Verify session in database
    $stmt = $db->prepare("
        SELECT user_id, expires_at, is_active
        FROM user_login_sessions 
        WHERE session_id = ? 
        AND is_active = 1 
        AND expires_at > NOW()
    ");
    $stmt->bind_param("s", $session_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Database query failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $session = $result->fetch_assoc();
    $stmt->close();
    
    if (!$session) {
        throw new Exception('Invalid or expired session. Please log in again.');
    }
    
    return $session['user_id'];
}


function validateUserIdCookie($db) {
    $user_id = intval($_COOKIE['user_id']);
    
    if ($user_id <= 0) {
        throw new Exception('Invalid user ID in cookie.');
    }
    
    // Verify user exists and is active
    $stmt = $db->prepare("SELECT user_id FROM users WHERE user_id = ? AND is_active = 1");
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Database query failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        $stmt->close();
        throw new Exception('User not found or inactive. Please log in again.');
    }
    
    $stmt->close();
    return $user_id;
}

function getUserInfoQuery() {
    return "SELECT 
                user_id,
                email,
                first_name,
                last_name,
                phone,
                user_type,
                profile_picture,
                bio,
                is_active,
                art_specialty,
                years_of_experience,
                achievements,
                artist_bio,
                location,
                education,
                created_at
            FROM users 
            WHERE user_id = ? AND is_active = 1";
}


function formatUserData($user_data) {
    $response_data = [
        'user_id' => (int)$user_data['user_id'],
        'email' => $user_data['email'],
        'first_name' => $user_data['first_name'],
        'last_name' => $user_data['last_name'],
        'full_name' => $user_data['first_name'] . ' ' . $user_data['last_name'],
        'phone' => $user_data['phone'],
        'user_type' => $user_data['user_type'],
        'profile_picture' => $user_data['profile_picture'],
        'profile_picture_url' => $user_data['profile_picture'] ? '../uploads/profiles/' . $user_data['profile_picture'] : null,
        'bio' => $user_data['bio'],
        'is_active' => (bool)$user_data['is_active'],
        'created_at' => $user_data['created_at']
    ];

    // Add artist-specific information if user is an artist
    if ($user_data['user_type'] === 'artist') {
        $response_data['artist_info'] = [
            'art_specialty' => $user_data['art_specialty'],
            'years_of_experience' => $user_data['years_of_experience'] ? (int)$user_data['years_of_experience'] : null,
            'achievements' => $user_data['achievements'],
            'artist_bio' => $user_data['artist_bio'],
            'location' => $user_data['location'],
            'education' => $user_data['education']
        ];
    }

    return $response_data;
}


function getUserInfo($db, $user_id) {
    try {
        $sql = getUserInfoQuery();
        $stmt = $db->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $db->error);
        }

        $stmt->bind_param("i", $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {
            $stmt->close();
            throw new Exception('User is not registered or account is inactive.');
        }

        $user_data = $result->fetch_assoc();
        $stmt->close();

        return formatUserData($user_data);
        
    } catch (Exception $e) {
        throw new Exception("Error fetching user information: " . $e->getMessage());
    }
}


function checkActiveSession($db, $user_id) {
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as active_sessions 
            FROM user_login_sessions 
            WHERE user_id = ? AND is_active = 1 AND expires_at > NOW()
        ");
        $stmt->bind_param("i", $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Session check query failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $session_data = $result->fetch_assoc();
        $stmt->close();

        return $session_data['active_sessions'] > 0;
        
    } catch (Exception $e) {
        throw new Exception("Error checking active session: " . $e->getMessage());
    }
}

/**
 * Main function to handle user info retrieval
 */
function handleGetUserInfo() {
    global $db;
    
    try {
        // Validate database connection
        if (!isset($db) || $db->connect_error) {
            throw new Exception("Database connection failed: " . ($db->connect_error ?? "Connection not established"));
        }
        
        // Authenticate user and get user ID
        $user_id = validateUserAuthentication($db);
        
        // Get user information
        $user_data = getUserInfo($db, $user_id);
        
        // Check if user has active session
        $has_active_session = checkActiveSession($db, $user_id);
        
        if (!$has_active_session) {
            throw new Exception('User is not registered or has no active session. Please log in again.');
        }
        
        $user_data['has_active_session'] = $has_active_session;
        
        // Send success response
        sendResponse(true, 'User information retrieved successfully', $user_data);
        
    } catch (Exception $e) {
        // Send error response
        sendResponse(false, 'An error occurred while retrieving user information: ' . $e->getMessage());
    } finally {
        // Close database connection if it exists
        if (isset($db) && !$db->connect_error) {
            $db->close();
        }
    }
}

// Execute the main function
handleGetUserInfo();
?>