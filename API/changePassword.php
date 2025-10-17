<?php
// Prevent any output buffering issues
ob_start();

// Enable error reporting for logging but don't display errors
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to prevent HTML in JSON response
ini_set('log_errors', 1); // Log errors instead

// Start session
session_start();

header('Content-Type: application/json');

// Enable CORS if needed
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Clean any output that might have been generated
ob_clean();

// Error handler for JSON responses
set_error_handler(function($severity, $message, $file, $line) {
    error_log("PHP Error: $message in $file on line $line");
    if ($severity === E_ERROR || $severity === E_CORE_ERROR || $severity === E_COMPILE_ERROR) {
        ob_clean(); // Clear any output
        echo json_encode(['success' => false, 'message' => 'Server error occurred']);
        exit;
    }
});

// Exception handler for JSON responses
set_exception_handler(function($exception) {
    error_log("Uncaught exception: " . $exception->getMessage());
    ob_clean(); // Clear any output
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
    exit;
});

// Include PHPMailer for email notifications
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if PHPMailer files exist before requiring them
$phpmailerPath = 'PHPMailer-master/src/';
if (!file_exists($phpmailerPath . 'Exception.php') || 
    !file_exists($phpmailerPath . 'PHPMailer.php') || 
    !file_exists($phpmailerPath . 'SMTP.php')) {
    error_log("PHPMailer files not found in path: " . $phpmailerPath);
    // Continue without email functionality
} else {
    require $phpmailerPath . 'Exception.php';
    require $phpmailerPath . 'PHPMailer.php';
    require $phpmailerPath . 'SMTP.php';
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Function to send clean JSON responses
function sendResponse($success, $message, $data = null) {
    // Clean any output that might have been generated
    ob_clean();
    
    // Ensure content type header is set
    header('Content-Type: application/json');
    
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Function to send password change notification email
function sendPasswordChangeNotification($email, $firstName, $userType) {
    // Check if PHPMailer class is available
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log("PHPMailer class not available, skipping email notification");
        return false;
    }
    
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;
        $mail->Username = "yadawitygallery@gmail.com";
        $mail->Password = "caur yohs lntb ljug";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Enhanced server compatibility settings
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
                'cafile' => '',
                'capath' => '',
                'ciphers' => 'DEFAULT@SECLEVEL=1'
            )
        );
        
        $mail->Timeout = 120;
        $mail->SMTPKeepAlive = true;
        $mail->SMTPAutoTLS = false;
        
        // Disable debug output for production
        $mail->SMTPDebug = 0;
        
        // Recipients
        $mail->setFrom("yadawitygallery@gmail.com", "Yadawity Gallery");
        $mail->addAddress($email, $firstName);
        $mail->addReplyTo("yadawitygallery@gmail.com", "Yadawity Gallery Support");
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Password Changed Successfully - Yadawity Gallery";
        
        // Get current timestamp
        $changeTime = date('F j, Y \a\t g:i A');
        $changeTimeUTC = gmdate('F j, Y \a\t g:i A') . ' UTC';
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #8b6f47; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                .success-icon { background: #22c55e; color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 20px auto; font-size: 24px; }
                .info-box { background: #e1f5fe; border-left: 4px solid #0288d1; padding: 15px; margin: 20px 0; border-radius: 0 5px 5px 0; }
                .security-tips { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 0 5px 5px 0; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
                .timestamp { color: #666; font-size: 14px; font-style: italic; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Password Changed Successfully</h1>
                </div>
                <div class='content'>
                   
                    
                    <p>Hello <strong>{$firstName}</strong>,</p>
                    
                    <p>This email confirms that your password for your Yadawity Gallery account has been successfully changed.</p>
                    
                    <div class='info-box'>
                        <p><strong>Account Details:</strong></p>
                        <ul>
                            <li>Email: {$email}</li>
                            <li>Account Type: " . ucfirst($userType) . "</li>
                            <li>Change Time: {$changeTime}</li>
                        </ul>
                    </div>
                    
                    <div class='security-tips'>
                        <p><strong>Security Reminder:</strong></p>
                        <ul>
                            <li>If you didn't make this change, please contact our support team immediately</li>
                            <li>Never share your password with anyone</li>
                            <li>Use a strong, unique password for your account</li>
                        </ul>
                    </div>

                    <p>If you have any concerns about this password change or believe your account may have been compromised, please contact our support team immediately at <a href='mailto:yadawitygallery@gmail.com'>yadawitygallery@gmail.com</a>.</p>

                    <p>Thank you for keeping your account secure!</p>
                    
                    <p>Best regards,<br>
                    The Yadawity Gallery Security Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated security notification. Please do not reply to this email.</p>
                    <p>© " . date('Y') . " Yadawity Gallery. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";

        // Fallback plain text version
        $mail->AltBody = "Password Changed Successfully\n\n";
        $mail->AltBody .= "Hello {$firstName},\n\n";
        $mail->AltBody .= "This email confirms that your password for your Yadawity Gallery account has been successfully changed.\n\n";
        $mail->AltBody .= "Account Details:\n";
        $mail->AltBody .= "- Email: {$email}\n";
        $mail->AltBody .= "- Account Type: " . ucfirst($userType) . "\n";
        $mail->AltBody .= "- Change Time: {$changeTime}\n";
        $mail->AltBody .= "- Change Time (UTC): {$changeTimeUTC}\n\n";
        $mail->AltBody .= "If you didn't make this change, please contact our support team immediately.\n\n";
        $mail->AltBody .= "Best regards,\nThe Yadawity Gallery Security Team";

        $mail->send();
        error_log("Password change notification sent successfully to: " . $email);
        return true;
        
    } catch (Exception $e) {
        error_log("Failed to send password change notification to {$email}: " . $e->getMessage());
        return false;
    }
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendResponse(false, 'Method not allowed');
}

try {
    // Use the standard database connection
    require_once "db.php";
    
    // Check database connection
    if (!$db || $db->connect_error) {
        sendResponse(false, 'Database connection failed');
    }

    // Authenticate user
    $userId = null;
    $isAuthenticated = false;

    // Try session authentication first
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        
        // Verify user exists and is active
        $stmt = $db->prepare("SELECT user_id, email, first_name, user_type, is_active, is_verified FROM users WHERE user_id = ? AND is_active = 1");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $isAuthenticated = true;
            }
            $stmt->close();
        }
    }

    // If session auth failed, try cookie authentication
    if (!$isAuthenticated && isset($_COOKIE['user_login'])) {
        $cookieValue = $_COOKIE['user_login'];
        $parts = explode('_', $cookieValue, 2);
        
        if (count($parts) === 2) {
            $userId = (int)$parts[0];
            $cookieHash = $parts[1];
            
            // Get user info and validate authentication
            $stmt = $db->prepare("SELECT user_id, email, first_name, user_type, is_active, is_verified FROM users WHERE user_id = ? AND is_active = 1");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    
                    // Validate session exists and is active
                    $sessionStmt = $db->prepare("SELECT session_id, login_time FROM user_login_sessions WHERE user_id = ? AND is_active = 1 AND expires_at > NOW() ORDER BY login_time DESC LIMIT 1");
                    if ($sessionStmt) {
                        $sessionStmt->bind_param("i", $userId);
                        $sessionStmt->execute();
                        $sessionResult = $sessionStmt->get_result();
                        
                        if ($sessionResult->num_rows > 0) {
                            $sessionData = $sessionResult->fetch_assoc();
                            
                            // Validate cookie hash using HMAC-SHA256
                            $expectedHash = hash_hmac('sha256', $userId . '_' . $sessionData['session_id'] . '_' . $sessionData['login_time'], 'your_secret_key_here');
                            
                            if (hash_equals($expectedHash, $cookieHash)) {
                                $isAuthenticated = true;
                            }
                        }
                        $sessionStmt->close();
                    }
                }
                $stmt->close();
            }
        }
    }

    // Check if user is authenticated
    if (!$isAuthenticated) {
        http_response_code(401);
        sendResponse(false, 'User not authenticated');
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($input['currentPassword']) || !isset($input['newPassword']) || !isset($input['confirmPassword'])) {
        sendResponse(false, 'Missing required fields');
    }
    
    $currentPassword = trim($input['currentPassword']);
    $newPassword = trim($input['newPassword']);
    $confirmPassword = trim($input['confirmPassword']);
    
    // Server-side validation
    $errors = [];
    
    // Validate current password is not empty
    if (empty($currentPassword)) {
        $errors[] = 'Current password is required';
    }
    
    // Validate new password strength
    if (empty($newPassword)) {
        $errors[] = 'New password is required';
    } else {
        // Password strength requirements
        if (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters long';
        }
        if (!preg_match('/[a-z]/', $newPassword)) {
            $errors[] = 'New password must contain at least one lowercase letter';
        }
        if (!preg_match('/[A-Z]/', $newPassword)) {
            $errors[] = 'New password must contain at least one uppercase letter';
        }
        if (!preg_match('/[0-9]/', $newPassword)) {
            $errors[] = 'New password must contain at least one number';
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $newPassword)) {
            $errors[] = 'New password must contain at least one special character';
        }
    }
    
    // Validate password confirmation
    if ($newPassword !== $confirmPassword) {
        $errors[] = 'New password and confirmation do not match';
    }
    
    // Check if new password is different from current
    if ($currentPassword === $newPassword) {
        $errors[] = 'New password must be different from current password';
    }
    
    // Return validation errors if any
    if (!empty($errors)) {
        sendResponse(false, implode('. ', $errors));
    }
    
    // Verify current password
    $stmt = $db->prepare("SELECT password, email, first_name, user_type FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();
    
    if (!$userData) {
        sendResponse(false, 'User not found');
    }
    
    // Verify current password (supports both bcrypt and md5 for backward compatibility)
    $passwordValid = false;
    if (password_verify($currentPassword, $userData['password'])) {
        $passwordValid = true;
    } elseif (md5($currentPassword) === $userData['password']) {
        $passwordValid = true;
    }
    
    if (!$passwordValid) {
        sendResponse(false, 'Current password is incorrect');
    }
    
    // Hash new password using bcrypt
    $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password in database
    $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $updateStmt->bind_param("si", $hashedNewPassword, $userId);
    $result = $updateStmt->execute();
    $updateStmt->close();
    
    if ($result) {
        // Log password change activity (optional) - check if table exists first
        $checkTable = $db->query("SHOW TABLES LIKE 'user_activity_log'");
        if ($checkTable->num_rows > 0) {
            $logStmt = $db->prepare("INSERT INTO user_activity_log (user_id, activity_type, activity_description, ip_address, created_at) VALUES (?, 'password_change', 'User changed password', ?, NOW())");
            $logStmt->bind_param("is", $userId, $_SERVER['REMOTE_ADDR']);
            $logStmt->execute();
            $logStmt->close();
        }
        
        // Send password change notification email
        error_log("Attempting to send password change notification to: " . $userData['email']);
        $emailSent = sendPasswordChangeNotification($userData['email'], $userData['first_name'], $userData['user_type']);
        error_log("Email sent result: " . ($emailSent ? 'true' : 'false'));
        
        // Prepare response data
        $responseData = [];
        
        // Add email notification status to response
        if ($emailSent) {
            $responseData['email_notification'] = 'Notification email sent successfully';
        } else {
            $responseData['email_notification'] = 'Password changed but notification email failed to send';
            error_log("Password change notification email failed for user ID: " . $userId);
        }
        
        sendResponse(true, 'Password changed successfully', $responseData);
    } else {
        sendResponse(false, 'Failed to update password');
    }
    
} catch (Exception $e) {
    error_log("Password change error: " . $e->getMessage());
    sendResponse(false, 'An error occurred while changing password');
} finally {
    // Close database connection
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
}
?>
