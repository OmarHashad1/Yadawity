<?php
// Prevent any output buffering issues
ob_start();

// Enable error reporting for debugging but don't display errors
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to prevent HTML in JSON response
ini_set('log_errors', 1); // Log errors instead

// Set timezone to UTC for consistency
date_default_timezone_set('UTC');

// Set JSON header first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Log that the API was accessed
error_log("[Email Verification API] API accessed. Method: " . $_SERVER['REQUEST_METHOD']);

require_once "db.php";

// Check if PHPMailer files exist before requiring them
$phpmailerPath = 'PHPMailer-master/src/';
if (!file_exists($phpmailerPath . 'Exception.php') || 
    !file_exists($phpmailerPath . 'PHPMailer.php') || 
    !file_exists($phpmailerPath . 'SMTP.php')) {
    logError("PHPMailer files not found in path: " . $phpmailerPath);
    sendResponse(false, 'Email service temporarily unavailable');
}

require $phpmailerPath . 'Exception.php';
require $phpmailerPath . 'PHPMailer.php';
require $phpmailerPath . 'SMTP.php';

// Check database connection
if (!$db || $db->connect_error) {
    logError("Database connection failed: " . ($db ? $db->connect_error : 'Database object not created'));
    sendResponse(false, 'Database connection error');
}

// Set MySQL timezone to UTC
if (!$db->query("SET time_zone = '+00:00'")) {
    logError("Failed to set timezone: " . $db->error);
}

// Rate limiting
session_start();
$rate_limit_key = 'email_change_' . $_SERVER['REMOTE_ADDR'];
$rate_limit = 3; // Max 3 attempts per 15 minutes
$rate_window = 900; // 15 minutes

if (!isset($_SESSION[$rate_limit_key])) {
    $_SESSION[$rate_limit_key] = [];
}

// Clean old attempts
$_SESSION[$rate_limit_key] = array_filter($_SESSION[$rate_limit_key], function($timestamp) use ($rate_window) {
    return (time() - $timestamp) < $rate_window;
});

function sendResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data
    ];
    
    // Log the exact response being sent
    logError("Sending JSON response: " . json_encode($response));
    
    echo json_encode($response);
    exit;
}

function logError($message) {
    error_log("[Email Verification API] " . $message);
}

function checkRateLimit($rate_limit_key, $rate_limit) {
    if (count($_SESSION[$rate_limit_key]) >= $rate_limit) {
        sendResponse(false, 'Too many email verification attempts. Please try again later.');
    }
    $_SESSION[$rate_limit_key][] = time();
}

function generateVerificationCode() {
    return sprintf('%06d', mt_rand(100000, 999999));
}

function sendVerificationEmail($email, $code) {
    try {
        $mail = new PHPMailer(true);
        
        // Enhanced server settings for production deployment
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
        
        // Extended timeout settings for slow servers
        $mail->Timeout = 120;
        $mail->SMTPKeepAlive = true;
        $mail->SMTPAutoTLS = false; // Disable auto TLS
        
        // Force disable local mail fallback
        ini_set('SMTP', '');
        ini_set('smtp_port', '');
        
        // Disable debug output for production
        $mail->SMTPDebug = 0;
        
        // Recipients
        $mail->setFrom("yadawitygallery@gmail.com", "Yadawity Gallery");
        $mail->addAddress($email);
        $mail->addReplyTo("yadawitygallery@gmail.com", "Yadawity Gallery");
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Email Verification Code - Yadawity Gallery";
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #8b6f47; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                .code { background: #fff; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; color: #8b6f47; border: 2px dashed #8b6f47; margin: 20px 0; border-radius: 5px; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Email Verification Required</h1>
                </div>
                <div class='content'>
                    <p>Hello,</p>
                    <p>You have requested to change your email address for your Yadawity Gallery account. Please use the verification code below to confirm this change:</p>
                    <div class='code'>{$code}</div>
                    <p><strong>Important:</strong></p>
                    <ul>
                        <li>This code will expire in 15 minutes</li>
                        <li>If you didn't request this email change, please ignore this email</li>
                        <li>Never share this code with anyone</li>
                        <li>Your current email will remain active until this change is confirmed</li>
                    </ul>
                    <p>Best regards,<br>The Yadawity Gallery Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";

        // Fallback plain text version
        $mail->AltBody = "Email Verification Code: {$code}\n\nThis code will expire in 15 minutes.\nIf you didn't request this email change, please ignore this email.";

        $mail->send();
        error_log("Email verification code sent successfully to: " . $email);
        return true;
        
    } catch (Exception $e) {
        error_log("Failed to send verification email to {$email}: " . $e->getMessage());
        return false;
    }
}

function sendEmailChangeNotification($oldEmail, $newEmail, $firstName, $userType) {
    try {
        $mail = new PHPMailer(true);
        
        // Enhanced server settings for production deployment
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
        
        // Extended timeout settings for slow servers
        $mail->Timeout = 120;
        $mail->SMTPKeepAlive = true;
        $mail->SMTPAutoTLS = false;
        
        // Disable debug output for production
        $mail->SMTPDebug = 0;
        
        // Recipients - send to both old and new email addresses
        $mail->setFrom("yadawitygallery@gmail.com", "Yadawity Gallery");
        $mail->addAddress($newEmail, $firstName); // Primary recipient (new email)
        $mail->addCC($oldEmail); // CC to old email for security
        $mail->addReplyTo("yadawitygallery@gmail.com", "Yadawity Gallery");
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Email Address Changed Successfully - Yadawity Gallery";
        
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
                .info-box { background: #e1f5fe; border-left: 4px solid #0288d1; padding: 15px; margin: 20px 0; border-radius: 0 5px 5px 0; }
                .security-tips { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 0 5px 5px 0; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Email Address Changed Successfully</h1>
                </div>
                <div class='content'>
                    <p>Hello <strong>{$firstName}</strong>,</p>
                    
                    <p>This email confirms that your email address for your Yadawity Gallery account has been successfully changed.</p>
                    
                    <div class='info-box'>
                        <p><strong>Account Details:</strong></p>
                        <ul>
                            <li>Old Email: {$oldEmail}</li>
                            <li>New Email: {$newEmail}</li>
                            <li>Account Type: " . ucfirst($userType) . "</li>
                            <li>Change Time: {$changeTime}</li>
                            <li>Change Time (UTC): {$changeTimeUTC}</li>
                        </ul>
                    </div>
                    
                    <div class='security-tips'>
                        <p><strong>Security Notice:</strong></p>
                        <ul>
                            <li>This email has been sent to both your old and new email addresses for security</li>
                            <li>For your protection, all other active sessions have been logged out - you may need to log in again on other devices</li>
                            <li>If you didn't make this change, please contact our support team immediately</li>
                            <li>Your login credentials remain the same - only your email address has changed</li>
                            <li>Use your new email address for future login attempts</li>
                        </ul>
                    </div>
                    
                    <p>If you have any concerns about this email change or believe your account may have been compromised, please contact our support team immediately at <a href='mailto:support@yadawitygallery.com'>support@yadawitygallery.com</a>.</p>
                    
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
        $mail->AltBody = "Email Address Changed Successfully\n\n";
        $mail->AltBody .= "Hello {$firstName},\n\n";
        $mail->AltBody .= "This email confirms that your email address for your Yadawity Gallery account has been successfully changed.\n\n";
        $mail->AltBody .= "Account Details:\n";
        $mail->AltBody .= "- Old Email: {$oldEmail}\n";
        $mail->AltBody .= "- New Email: {$newEmail}\n";
        $mail->AltBody .= "- Account Type: " . ucfirst($userType) . "\n";
        $mail->AltBody .= "- Change Time: {$changeTime}\n";
        $mail->AltBody .= "- Change Time (UTC): {$changeTimeUTC}\n\n";
        $mail->AltBody .= "SECURITY NOTICE:\n";
        $mail->AltBody .= "- For your protection, all other active sessions have been logged out\n";
        $mail->AltBody .= "- You may need to log in again on other devices\n";
        $mail->AltBody .= "- If you didn't make this change, please contact our support team immediately\n\n";
        $mail->AltBody .= "Best regards,\nThe Yadawity Gallery Security Team";

        $mail->send();
        error_log("Email change notification sent successfully to: " . $newEmail . " and " . $oldEmail);
        return true;
        
    } catch (Exception $e) {
        error_log("Failed to send email change notification: " . $e->getMessage());
        return false;
    }
}





if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logError("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    sendResponse(false, 'Invalid request method');
}

$action = $_POST['action'] ?? '';
logError("Action requested: " . $action);

switch ($action) {
    case 'send_verification':
        checkRateLimit($rate_limit_key, $rate_limit);
        
        $newEmail = filter_var($_POST['new_email'] ?? '', FILTER_VALIDATE_EMAIL);
        $currentEmail = filter_var($_POST['current_email'] ?? '', FILTER_VALIDATE_EMAIL);
        $userId = intval($_POST['user_id'] ?? 0);
        
        if (!$newEmail || !$currentEmail || !$userId) {
            sendResponse(false, 'Please provide all required information');
        }

        // Check if the new email is already in use by another user
        $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ? AND is_active = 1");
        if (!$stmt) {
            logError("Failed to prepare email check query: " . $db->error);
            sendResponse(false, 'Database error occurred');
        }

        $stmt->bind_param("si", $newEmail, $userId);
        if (!$stmt->execute()) {
            logError("Failed to execute email check query: " . $stmt->error);
            sendResponse(false, 'Database error occurred');
        }
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            sendResponse(false, 'This email address is already in use by another account');
        }

        // Verify the current user owns the current email
        $stmt = $db->prepare("SELECT user_id FROM users WHERE user_id = ? AND email = ? AND is_active = 1");
        if (!$stmt) {
            sendResponse(false, 'Database error occurred');
        }

        $stmt->bind_param("is", $userId, $currentEmail);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            sendResponse(false, 'Invalid user credentials');
        }

        $code = generateVerificationCode();
        $expires_at = gmdate('Y-m-d H:i:s', time() + 900); // 15 minutes in UTC

        // Check if email_verifications table exists, create if it doesn't
        $checkTable = $db->query("SHOW TABLES LIKE 'email_verifications'");
        if ($checkTable->num_rows === 0) {
            $createTable = "CREATE TABLE email_verifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                current_email VARCHAR(255) NOT NULL,
                new_email VARCHAR(255) NOT NULL,
                verification_code VARCHAR(6) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                is_used TINYINT(1) DEFAULT 0,
                INDEX idx_user_id (user_id),
                INDEX idx_new_email (new_email),
                INDEX idx_verification_code (verification_code),
                INDEX idx_expires_at (expires_at)
            ) ENGINE=InnoDB";
            
            if (!$db->query($createTable)) {
                logError("Failed to create email_verifications table: " . $db->error);
                sendResponse(false, 'Database setup error occurred');
            }
        }

        // Store verification code in database
        $verify_stmt = $db->prepare("INSERT INTO email_verifications (user_id, current_email, new_email, verification_code, expires_at, created_at) VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE verification_code = VALUES(verification_code), expires_at = VALUES(expires_at), created_at = UTC_TIMESTAMP(), is_used = 0");
        
        if (!$verify_stmt) {
            logError("Failed to prepare verification code insert query: " . $db->error);
            sendResponse(false, 'Database error occurred');
        }

        $verify_stmt->bind_param("issss", $userId, $currentEmail, $newEmail, $code, $expires_at);
        
        if (!$verify_stmt->execute()) {
            logError("Failed to execute verification code insert query: " . $verify_stmt->error);
            sendResponse(false, 'Failed to generate verification code');
        }
        $verify_stmt->close();

        // Send email using PHPMailer
        if (sendVerificationEmail($newEmail, $code)) {
            sendResponse(true, 'Verification code sent to your new email address');
        } else {
            logError("Failed to send verification email to: " . $newEmail);
            sendResponse(false, 'Failed to send verification email. Please try again later.');
        }
        break;

    case 'test_success':
        logError("Test success endpoint called");
        sendResponse(true, 'Test success message', ['test' => 'data']);
        break;

    case 'debug_user':
        $userId = intval($_POST['user_id'] ?? 0);
        if ($userId) {
            $debug_stmt = $db->prepare("SELECT user_id, email, first_name FROM users WHERE user_id = ?");
            if ($debug_stmt) {
                $debug_stmt->bind_param("i", $userId);
                $debug_stmt->execute();
                $result = $debug_stmt->get_result();
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    sendResponse(true, 'User found', $user);
                } else {
                    sendResponse(false, 'User not found');
                }
                $debug_stmt->close();
            } else {
                sendResponse(false, 'Database error');
            }
        } else {
            sendResponse(false, 'No user ID provided');
        }
        break;

    case 'verify_code':
        try {
            $newEmail = filter_var($_POST['new_email'] ?? '', FILTER_VALIDATE_EMAIL);
            $code = preg_replace('/[^0-9]/', '', $_POST['code'] ?? '');
            $userId = intval($_POST['user_id'] ?? 0);

            logError("Verify code attempt - User ID: $userId, Email: $newEmail, Code: $code");

            if (!$newEmail || !$code || !$userId) {
                logError("Missing required fields - User ID: $userId, Email: $newEmail, Code: $code");
                sendResponse(false, 'Please provide all required information');
            }

            if (strlen($code) !== 6) {
                logError("Invalid code length - Code: $code, Length: " . strlen($code));
                sendResponse(false, 'Please enter a valid 6-digit verification code');
            }

            // Check if code is valid and not expired
            // First, let's debug what verification records exist for this user
            $debug_stmt = $db->prepare("SELECT id, new_email, verification_code, expires_at, is_used FROM email_verifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
            if ($debug_stmt) {
                $debug_stmt->bind_param("i", $userId);
                $debug_stmt->execute();
                $debug_result = $debug_stmt->get_result();
                $debug_records = [];
                while ($row = $debug_result->fetch_assoc()) {
                    $debug_records[] = $row;
                }
                logError("Debug - Verification records for User ID $userId: " . json_encode($debug_records));
                $debug_stmt->close();
            }
            
            $stmt = $db->prepare("SELECT id, current_email, new_email FROM email_verifications WHERE user_id = ? AND new_email = ? AND verification_code = ? AND expires_at > UTC_TIMESTAMP() AND is_used = 0 ORDER BY created_at DESC LIMIT 1");
            
            if (!$stmt) {
                logError("Failed to prepare verification check query: " . $db->error);
                sendResponse(false, 'Database error occurred');
            }

            $stmt->bind_param("iss", $userId, $newEmail, $code);
            if (!$stmt->execute()) {
                logError("Failed to execute verification check query: " . $stmt->error);
                sendResponse(false, 'Database error occurred');
            }
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                logError("Verification failed - No matching record found for User ID: $userId, Email: $newEmail, Code: $code");
                $stmt->close();
                sendResponse(false, 'Invalid or expired verification code');
            }

            $verification = $result->fetch_assoc();
            $stmt->close();
            
            logError("Verification found for User ID: $userId, proceeding with email update");
            
            logError("DEBUG: Starting email update for User ID: $userId");
            
            // Update user's email
            $update_stmt = $db->prepare("UPDATE users SET email = ? WHERE user_id = ?");
            if (!$update_stmt) {
                logError("Failed to prepare email update query: " . $db->error);
                sendResponse(false, 'Database error occurred');
            }

            logError("DEBUG: About to execute email update query");
            $update_stmt->bind_param("si", $newEmail, $userId);
            if (!$update_stmt->execute()) {
                logError("Failed to execute email update query: " . $update_stmt->error);
                sendResponse(false, 'Failed to update email address');
            }
            
            logError("DEBUG: Email update query executed, affected rows: " . $update_stmt->affected_rows);
            
            logError("DEBUG: About to check affected rows");
            
            // Check if the update actually affected any rows or if the email was already set correctly
            if ($update_stmt->affected_rows === 0) {
                logError("DEBUG: No rows affected, checking if email already set correctly");
                // Check if the user already has the new email set
                $verify_email_stmt = $db->prepare("SELECT email FROM users WHERE user_id = ? AND email = ?");
                if ($verify_email_stmt) {
                    $verify_email_stmt->bind_param("is", $userId, $newEmail);
                    $verify_email_stmt->execute();
                    $verify_result = $verify_email_stmt->get_result();
                    
                    if ($verify_result->num_rows > 0) {
                        logError("Email already set to new value for User ID: $userId, considering this a success");
                        // Email is already set correctly, continue with success
                    } else {
                        logError("Email update executed but no rows were affected and user not found. User ID: $userId");
                        sendResponse(false, 'Failed to update email address - user not found');
                    }
                    $verify_email_stmt->close();
                } else {
                    logError("Failed to verify email after update for User ID: $userId");
                    sendResponse(false, 'Failed to update email address - verification failed');
                }
            }
            
            logError("DEBUG: Email update check passed");
            logError("Email successfully updated for User ID: $userId from verification to: $newEmail");
            $update_stmt->close();

            // Get user information for email notification
            $emailNotificationSent = false;
            try {
                $user_stmt = $db->prepare("SELECT first_name, user_type FROM users WHERE user_id = ?");
                if ($user_stmt) {
                    $user_stmt->bind_param("i", $userId);
                    $user_stmt->execute();
                    $user_result = $user_stmt->get_result();
                    if ($user_result->num_rows > 0) {
                        $user_info = $user_result->fetch_assoc();
                        
                        // Send email change notification (non-blocking)
                        logError("Attempting to send email change notification for User ID: $userId");
                        $emailNotificationSent = sendEmailChangeNotification(
                            $verification['current_email'], 
                            $verification['new_email'], 
                            $user_info['first_name'], 
                            $user_info['user_type']
                        );
                        logError("Email change notification result: " . ($emailNotificationSent ? 'success' : 'failed'));
                    }
                    $user_stmt->close();
                }
            } catch (Exception $e) {
                logError("Email notification failed but continuing: " . $e->getMessage());
                $emailNotificationSent = false;
            }

            logError("DEBUG: About to mark verification as used, verification ID: " . $verification['id']);
            
            // Mark verification as used (with SQL error handling)
            try {
                // Use a more forgiving approach - check if already marked as used first
                $check_used_stmt = $db->prepare("SELECT is_used FROM email_verifications WHERE id = ?");
                if ($check_used_stmt) {
                    $check_used_stmt->bind_param("i", $verification['id']);
                    $check_used_stmt->execute();
                    $check_result = $check_used_stmt->get_result();
                    if ($check_result->num_rows > 0) {
                        $current_status = $check_result->fetch_assoc();
                        if ($current_status['is_used'] == 1) {
                            logError("Verification already marked as used, skipping update");
                        } else {
                            // Try to mark as used
                            $mark_used_stmt = $db->prepare("UPDATE email_verifications SET is_used = 1 WHERE id = ? AND is_used = 0");
                            if ($mark_used_stmt) {
                                $mark_used_stmt->bind_param("i", $verification['id']);
                                if ($mark_used_stmt->execute()) {
                                    if ($mark_used_stmt->affected_rows > 0) {
                                        logError("Verification marked as used for verification ID: " . $verification['id']);
                                    } else {
                                        logError("Verification was already marked as used by another process");
                                    }
                                } else {
                                    logError("Failed to mark verification as used: " . $mark_used_stmt->error . " - continuing anyway");
                                }
                                $mark_used_stmt->close();
                            }
                        }
                    }
                    $check_used_stmt->close();
                }
            } catch (Exception $e) {
                logError("Exception when marking verification as used: " . $e->getMessage() . " - continuing with success anyway");
            }

            logError("DEBUG: Finished marking verification as used");

            // Update session information if user is currently logged in
            try {
                session_start();
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                    logError("Updating session email from " . ($_SESSION['email'] ?? 'unknown') . " to " . $newEmail);
                    $_SESSION['email'] = $newEmail;
                    
                    // Regenerate session ID for additional security
                    session_regenerate_id(true);
                    logError("Session email updated and session ID regenerated for security");
                    
                    // For security: Invalidate all other user sessions when email changes
                    // This prevents unauthorized access if someone else had access to the account
                    try {
                        $current_session_id = session_id();
                        $stmt = $db->prepare("UPDATE user_login_sessions SET is_active = 0, logout_time = NOW() WHERE user_id = ? AND session_id != ? AND is_active = 1");
                        $stmt->bind_param("is", $userId, $current_session_id);
                        if ($stmt->execute()) {
                            $affected_sessions = $stmt->affected_rows;
                            logError("Invalidated $affected_sessions other sessions for security after email change");
                        }
                        $stmt->close();
                    } catch (Exception $e) {
                        logError("Failed to invalidate other sessions: " . $e->getMessage() . " - continuing anyway");
                    }
                } else {
                    logError("User not in current session or different user, session not updated");
                }
            } catch (Exception $e) {
                logError("Failed to update session information: " . $e->getMessage() . " - continuing anyway");
            }

            // Prepare response data
            $responseData = [
                'old_email' => $verification['current_email'],
                'new_email' => $verification['new_email'],
                'security_note' => 'For security, all other active sessions have been logged out. You may need to log in again on other devices.'
            ];
            
            // Add email notification status to response
            if (isset($emailNotificationSent)) {
                if ($emailNotificationSent) {
                    $responseData['email_notification'] = 'Email change notification sent successfully';
                } else {
                    $responseData['email_notification'] = 'Email updated but notification email failed to send';
                }
            }

            logError("About to send success response for User ID: $userId with data: " . json_encode($responseData));
            sendResponse(true, 'Email address updated successfully', $responseData);
            
        } catch (Exception $e) {
            logError("Exception in verify_code case: " . $e->getMessage());
            sendResponse(false, 'An error occurred while verifying the code');
        }
        break;

    default:
        sendResponse(false, 'Invalid action');
}
?>
