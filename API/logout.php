<?php
// Include database connection
require_once "db.php";

// Set content type to HTML for SweetAlert display
header('Content-Type: text/html; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

function startSessionSafely() {
    try {
        if (session_status() === PHP_SESSION_NONE) {
            if (!session_start()) {
                throw new Exception('Failed to start session');
            }
        }
    } catch (Exception $e) {
        throw new Exception('Error starting session: ' . $e->getMessage());
    }
}


function deleteSessionFromDatabase($db, $session_token, $user_id) {
    try {
        $stmt = $db->prepare("DELETE FROM user_login_sessions WHERE session_id = ? AND user_id = ?");
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $db->error);
        }
        
        $stmt->bind_param("si", $session_token, $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete session: " . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        throw new Exception('Error deleting session from database: ' . $e->getMessage());
    }
}

function deleteAllUserSessions($db, $user_id) {
    try {
        $stmt = $db->prepare("UPDATE user_login_sessions SET is_active = 0, logout_time = NOW() WHERE user_id = ? AND is_active = 1");
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $db->error);
        }
        
        $stmt->bind_param("i", $user_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to deactivate sessions: " . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        throw new Exception('Error deactivating user sessions: ' . $e->getMessage());
    }
}

function clearSessionData() {
    try {
        // Destroy all session data
        session_unset();
        session_destroy();

        // Remove the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Remove custom cookies
        setcookie('user_login', '', time() - 3600, '/');
        setcookie('user_id', '', time() - 3600, '/');
        setcookie('session_id', '', time() - 3600, '/');
        
    } catch (Exception $e) {
        // Log error but don't stop logout process
        error_log('Error clearing session data: ' . $e->getMessage());
    }
}

/**
 * Get user ID from various sources
 * @return int|null User ID if found
 */
function getUserIdFromSession() {
    $user_id = null;
    
    // Check session first
    if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
        $user_id = (int)$_SESSION['user_id'];
    }
    // Check user_login cookie
    elseif (isset($_COOKIE['user_login'])) {
        $cookie_parts = explode('_', $_COOKIE['user_login'], 2);
        if (count($cookie_parts) === 2 && is_numeric($cookie_parts[0])) {
            $user_id = (int)$cookie_parts[0];
        }
    }
    // Check simple user_id cookie
    elseif (isset($_COOKIE['user_id']) && is_numeric($_COOKIE['user_id'])) {
        $user_id = (int)$_COOKIE['user_id'];
    }
    
    return $user_id;
}


function getSessionToken() {
    $session_token = null;
    
    // Check session first
    if (isset($_SESSION['session_token'])) {
        $session_token = $_SESSION['session_token'];
    }
    // Check session_id cookie
    elseif (isset($_COOKIE['session_id'])) {
        $session_token = $_COOKIE['session_id'];
    }
    
    return $session_token;
}

function validateUserSession($db, $user_id, $session_token = null) {
    try {
        if (!$user_id) {
            return false;
        }
        
        // Check if user has any active session in database
        if ($session_token) {
            // Check specific session
            $stmt = $db->prepare("SELECT COUNT(*) as session_count FROM user_login_sessions WHERE user_id = ? AND session_id = ? AND is_active = 1");
            $stmt->bind_param("is", $user_id, $session_token);
        } else {
            // Check any active session for this user
            $stmt = $db->prepare("SELECT COUNT(*) as session_count FROM user_login_sessions WHERE user_id = ? AND is_active = 1");
            $stmt->bind_param("i", $user_id);
        }
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $db->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['session_count'] > 0;
        
    } catch (Exception $e) {
        error_log('Error validating user session: ' . $e->getMessage());
        return false;
    }
}

function showSweetAlertAndRedirect($title, $message, $icon, $redirectUrl) {
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Logout</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
        <script>
            Swal.fire({
                title: '$title',
                text: '$message',
                icon: '$icon',
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                window.location.href = '$redirectUrl';
            });
        </script>
    </body>
    </html>
    ";
}

function handleLogout() {
    global $db;
    
    try {
        // Validate database connection
        if (!isset($db) || $db->connect_error) {
            throw new Exception("Database connection failed: " . ($db->connect_error ?? "Connection not established"));
        }
        
        // Start session safely
        startSessionSafely();
        
        // Get user ID and session token
        $user_id = getUserIdFromSession();
        $session_token = getSessionToken();
        
        // Validate that user has an active session in database
        if (!validateUserSession($db, $user_id, $session_token)) {
            // User doesn't have an active session, redirect silently to index.php
            header('Location: ../index.php');
            exit();
        }

        // User has a valid session - proceed with logout and show SweetAlert
        // If we have user session data, clean up the database
        if ($user_id && $session_token) {
            try {
                // Delete specific session from database
                deleteSessionFromDatabase($db, $session_token, $user_id);
            } catch (Exception $e) {
                // If specific session deletion fails, try to delete all user sessions
                error_log('Failed to delete specific session, attempting to delete all user sessions: ' . $e->getMessage());
                deleteAllUserSessions($db, $user_id);
            }
        } elseif ($user_id) {
            // If we only have user ID, deactivate all sessions for this user
            deleteAllUserSessions($db, $user_id);
        }
        
        // Clear all session data and cookies
        clearSessionData();
        
        // Show SweetAlert for successful logout
        showSweetAlertAndRedirect(
            'Logged Out Successfully!',
            '',
            'success',
            '../index.php'
        );
        
    } catch (Exception $e) {
        // Even if there's an error, still clear session data
        clearSessionData();
        
        // Log the error
        error_log('Logout error: ' . $e->getMessage());
        
        // Show error alert for users with sessions, redirect silently for others
        $user_id = getUserIdFromSession();
        if ($user_id && validateUserSession($db, $user_id)) {
            showSweetAlertAndRedirect(
                'Logout Error',
                'An error occurred during logout, but you have been logged out for security.',
                'warning',
                '../index.php'
            );
        } else {
            // Redirect silently if no valid session
            header('Location: ../index.php');
            exit();
        }
    } finally {
        if (isset($db) && !$db->connect_error) {
            $db->close();
        }
    }
}

handleLogout();
?>