<?php
// Prevent any output before JSON response
ob_start();

// CORS headers for API access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Turn off error display for production
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Log errors instead of displaying them
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Set error reporting level
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Send JSON response to client
function sendJsonResponse($success, $message, $data = [], $httpCode = 200) {
    if (ob_get_length()) {
        ob_clean();
    }
    
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => $data
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Basic rate limiting for login attempts
function checkRateLimit($ip, $email = '') {
    $max_attempts = 5;
    $time_window = 900; // 15 minutes
    $cache_key = 'login_attempts_' . md5($ip . $email);
    $temp_file = sys_get_temp_dir() . '/' . $cache_key . '.json';
    $current_time = time();
    $attempts = [];
    
    if (file_exists($temp_file)) {
        $data = file_get_contents($temp_file);
        $attempts = json_decode($data, true) ?: [];
    }
    
    // Clean old attempts
    $attempts = array_filter($attempts, function($timestamp) use ($current_time, $time_window) {
        return ($current_time - $timestamp) < $time_window;
    });
    
    if (count($attempts) >= $max_attempts) {
        return true;
    }
    
    $attempts[] = $current_time;
    file_put_contents($temp_file, json_encode($attempts));
    return false;
}

// Session cleanup function to remove expired sessions
function cleanupExpiredSessions() {
    global $db;
    
    try {
        // Delete expired sessions
        $cleanup_stmt = $db->prepare("DELETE FROM user_login_sessions WHERE expires_at < NOW() OR is_active = 0");
        if ($cleanup_stmt) {
            $cleanup_stmt->execute();
            $deleted_count = $cleanup_stmt->affected_rows;
            $cleanup_stmt->close();
            
            if ($deleted_count > 0) {
                error_log("Cleaned up {$deleted_count} expired sessions");
            }
            
            return $deleted_count;
        }
    } catch (Exception $e) {
        error_log("Session cleanup error: " . $e->getMessage());
    }
    
    return 0;
}

// AUTHENTICATION ENDPOINT FOR AJAX REQUESTS
if (isset($_GET['action']) && $_GET['action'] === 'authenticate') {
    try {
        // Clear any previous output
        if (ob_get_length()) {
            ob_clean();
        }
        
        // Security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        require_once "db.php";
        session_start();
        
        // Clean up expired sessions periodically (10% chance per request)
        if (rand(1, 10) === 1) {
            cleanupExpiredSessions();
        }
        
        // Get client IP address
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $email = $_POST['email'] ?? '';
        $remember_me = isset($_POST['remember_me']) && $_POST['remember_me'] === 'true';
        
        // Check rate limiting
        if (checkRateLimit($client_ip, $email)) {
            sendJsonResponse(false, 'Too many failed login attempts. Please wait 15 minutes before trying again.', [], 429);
        }
        
        if (!$db || $db->connect_error) {
            error_log("Database connection failed: " . ($db ? $db->connect_error : 'Database object is null'));
            sendJsonResponse(false, 'Unable to connect to the service. Please try again in a few moments.', [], 500);
        }

        // Get email and password from POST request
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $validation_errors = [];

        // Trim inputs and check if empty
        $email = trim($email);
        $password = trim($password);
        
        if (empty($email)) {
            $validation_errors[] = 'Email address is required.';
        }
        
        if (empty($password)) {
            $validation_errors[] = 'Password is required.';
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validation_errors[] = 'Please enter a valid email address.';
        }

        if (!empty($email) && strlen($email) > 255) {
            $validation_errors[] = 'Email address is too long.';
        }

        if (!empty($password) && strlen($password) < 6) {
            $validation_errors[] = 'Password must be at least 6 characters long.';
        }

        if (!empty($password) && strlen($password) > 255) {
            $validation_errors[] = 'Password is too long.';
        }

        // Security checks for common injection patterns
        $dangerous_patterns = ['union', 'select', 'insert', 'update', 'delete', 'drop', '--', '/*', '*/', 'script'];
        $combined_input = strtolower($email . ' ' . $password);
        
        foreach ($dangerous_patterns as $pattern) {
            if (strpos($combined_input, $pattern) !== false) {
                $validation_errors[] = 'Invalid input detected.';
                break;
            }
        }

        // If there are validation errors, return them
        if (!empty($validation_errors)) {
            sendJsonResponse(false, implode(' ', $validation_errors), [], 400);
        }

        // Sanitize email input
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $email = strtolower(trim($email));

        // Check if user exists in users table
        $stmt = $db->prepare("SELECT user_id, email, password, first_name, last_name, user_type FROM users WHERE email = ? AND is_active = 1");
        if (!$stmt) {
            sendJsonResponse(false, 'Database error occurred.', [], 500);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password (supports both bcrypt and md5 for backward compatibility)
            if (password_verify($password, $user['password']) || md5($password) === $user['password']) {
                
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                
                // Create session variables (2 weeks expiry)
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['login_time'] = time();

                // Generate unique session token
                $session_token = bin2hex(random_bytes(32));
                $_SESSION['session_token'] = $session_token;

                // Set cookie expiry based on remember me option
                $login_time = date('Y-m-d H:i:s');
                
                if ($remember_me) {
                    // Remember me: 30 days (persistent cookie)
                    $cookie_expiry_seconds = 30 * 24 * 60 * 60; // 30 days
                    $expires_at = date('Y-m-d H:i:s', time() + $cookie_expiry_seconds);
                    $cookie_expires = time() + $cookie_expiry_seconds; // Set expiry time
                } else {
                    // Regular login: Session cookie (deleted when browser closes)
                    $cookie_expiry_seconds = 24 * 60 * 60; // 1 day for database cleanup
                    $expires_at = date('Y-m-d H:i:s', time() + $cookie_expiry_seconds);
                    $cookie_expires = 0; // Session cookie (no expiry time)
                }
                
                // Use stronger deterministic cookie hash generation
                $cookie_data = $session_token . '|' . $user['email'] . '|' . $user['user_id'] . '|' . $login_time;
                $cookie_hash = hash_hmac('sha256', $cookie_data, $session_token . '_' . $user['email']);
                $cookie_value = $user['user_id'] . '_' . $cookie_hash;
                
                                // Set cookie with secure flags and dynamic expiry
                if ($remember_me) {
                    // Persistent cookie (30 days)
                    setcookie('user_login', $cookie_value, $cookie_expires, '/', '', isset($_SERVER['HTTPS']), true);
                } else {
                    // Session cookie (expires when browser closes)
                    setcookie('user_login', $cookie_value, 0, '/', '', isset($_SERVER['HTTPS']), true);
                }

                // Create session record in user_login_sessions table
                $session_stmt = $db->prepare("INSERT INTO user_login_sessions (session_id, user_id, login_time, expires_at, is_active) VALUES (?, ?, ?, ?, 1)");
                if ($session_stmt) {
                    $session_stmt->bind_param("siss", $session_token, $user['user_id'], $login_time, $expires_at);
                    
                    if ($session_stmt->execute()) {
                        // Success response for SweetAlert
                        $responseData = [
                            'user_name' => $user['first_name'] . ' ' . $user['last_name'],
                            'user_type' => $user['user_type'],
                            'redirect_url' => ($user['user_type'] === 'admin') ? 'admin-dashboard.html' : 'index.php',
                            'remember_me' => $remember_me,
                            'session_expiry' => $expires_at
                        ];
                        
                        sendJsonResponse(true, 'Welcome back, ' . $user['first_name'] . ' ' . $user['last_name'] . '!', $responseData, 200);
                    } else {
                        sendJsonResponse(false, 'Failed to create login session. Please try again.', [], 500);
                    }
                    $session_stmt->close();
                } else {
                    sendJsonResponse(false, 'Failed to prepare session query.', [], 500);
                }
                
            } else {
                // Invalid password
                sendJsonResponse(false, 'Invalid email or password. Please check your credentials.', [], 401);
            }
        } else {
            // User not found
            sendJsonResponse(false, 'Invalid email or password. Please check your credentials.', [], 401);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Login API Error: " . $e->getMessage());
        sendJsonResponse(false, 'An error occurred during login. Please try again.', [], 500);
    } finally {
        if (isset($db) && $db instanceof mysqli) {
            $db->close();
        }
    }
}

// CHECK AUTHENTICATION ENDPOINT FOR COOKIE VALIDATION
if (isset($_GET['action']) && $_GET['action'] === 'check_auth') {
    try {
        // Clear any previous output
        if (ob_get_length()) {
            ob_clean();
        }
        
        // Security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        require_once "db.php";
        
        if (!$db || $db->connect_error) {
            error_log("Database connection failed: " . ($db ? $db->connect_error : 'Database object is null'));
            sendJsonResponse(false, 'Database connection failed.', [], 500);
        }
        
        // Check cookie-based authentication
        if (isset($_COOKIE['user_login'])) {
            $cookieValue = $_COOKIE['user_login'];
            $parts = explode('_', $cookieValue, 2);
            
            if (count($parts) === 2) {
                $userId = $parts[0];
                $cookieHash = $parts[1];
                
                // Get user info to verify cookie
                $stmt = $db->prepare("SELECT user_id, email, first_name, last_name, user_type FROM users WHERE user_id = ? AND is_active = 1");
                if ($stmt) {
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();
                        
                        // Get active session for this user to verify cookie
                        $session_stmt = $db->prepare("SELECT session_id, login_time FROM user_login_sessions WHERE user_id = ? AND is_active = 1 AND expires_at > NOW() ORDER BY login_time DESC LIMIT 1");
                        if ($session_stmt) {
                            $session_stmt->bind_param("i", $userId);
                            $session_stmt->execute();
                            $session_result = $session_stmt->get_result();
                            
                            if ($session_result->num_rows === 1) {
                                $session = $session_result->fetch_assoc();
                                $session_token = $session['session_id'];
                                $login_time = $session['login_time'];
                                
                                // Generate expected hash using same deterministic method
                                $cookie_data = $session_token . '|' . $user['email'] . '|' . $user['user_id'] . '|' . $login_time;
                                $expectedHash = hash_hmac('sha256', $cookie_data, $session_token . '_' . $user['email']);
                                
                                if ($cookieHash === $expectedHash) {
                                    $session_stmt->close();
                                    $stmt->close();
                                    
                                    $responseData = [
                                        'user_id' => $user['user_id'],
                                        'user_name' => $user['first_name'] . ' ' . $user['last_name'],
                                        'user_type' => $user['user_type'],
                                        'redirect_url' => ($user['user_type'] === 'admin') ? 'admin-dashboard.php' : 'index.php'
                                    ];
                                    
                                    sendJsonResponse(true, 'User authenticated', $responseData, 200);
                                }
                            }
                            $session_stmt->close();
                        }
                    }
                    $stmt->close();
                }
            }
        }
        
        // If we reach here, authentication failed
        sendJsonResponse(false, 'Authentication failed', [], 401);
        
    } catch (Exception $e) {
        error_log("Auth Check API Error: " . $e->getMessage());
        sendJsonResponse(false, 'An error occurred during authentication check.', [], 500);
    } finally {
        if (isset($db) && $db instanceof mysqli) {
            $db->close();
        }
    }
}

// SESSION CLEANUP ENDPOINT
if (isset($_GET['action']) && $_GET['action'] === 'cleanup_sessions') {
    try {
        // Clear any previous output
        if (ob_get_length()) {
            ob_clean();
        }
        
        // Security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        require_once "db.php";
        
        if (!$db || $db->connect_error) {
            error_log("Database connection failed: " . ($db ? $db->connect_error : 'Database object is null'));
            sendJsonResponse(false, 'Database connection failed.', [], 500);
        }
        
        $deleted_count = cleanupExpiredSessions();
        
        sendJsonResponse(true, "Cleaned up {$deleted_count} expired sessions", [
            'deleted_sessions' => $deleted_count,
            'cleanup_time' => date('Y-m-d H:i:s')
        ], 200);
        
    } catch (Exception $e) {
        error_log("Session Cleanup API Error: " . $e->getMessage());
        sendJsonResponse(false, 'An error occurred during session cleanup.', [], 500);
    } finally {
        if (isset($db) && $db instanceof mysqli) {
            $db->close();
        }
    }
}

// Handle invalid requests
if (!isset($_GET['action']) || !in_array($_GET['action'], ['authenticate', 'check_auth', 'cleanup_sessions'])) {
    // Clear any output buffer
    if (ob_get_length()) {
        ob_clean();
    }
    
    sendJsonResponse(false, 'Invalid API endpoint or missing action parameter.', [], 400);
}

?>