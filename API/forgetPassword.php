<?php
// Clean output buffer to prevent any unwanted output
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Enable error reporting for debugging but don't display errors to browser
error_reporting(E_ALL);
ini_set('display_errors', 0); // Changed from 1 to 0 to prevent HTML output
ini_set('log_errors', 1);

// Set timezone to UTC for consistency
date_default_timezone_set('UTC');

// Set headers early
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Set custom error handler to prevent HTML output
set_error_handler(function($severity, $message, $file, $line) {
    error_log("PHP Error: $message in $file on line $line");
    return true; // Don't execute PHP's internal error handler
});

// Set exception handler
set_exception_handler(function($exception) {
    error_log("Uncaught exception: " . $exception->getMessage());
    sendResponse(false, 'An unexpected error occurred');
});

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Log that the API was accessed
error_log("[Forgot Password API] API accessed. Method: " . $_SERVER['REQUEST_METHOD']);

require_once "db.php";

// Ensure database errors don't output HTML
if (isset($db) && $db->connect_error) {
    error_log("Database connection failed: " . $db->connect_error);
    sendResponse(false, 'Database connection failed');
}

// Only load PHPMailer if needed (fallback)
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    require 'PHPMailer-master/src/Exception.php';
    require 'PHPMailer-master/src/PHPMailer.php';
    require 'PHPMailer-master/src/SMTP.php';
}

// Set MySQL timezone to UTC
$db->query("SET time_zone = '+00:00'");

// Rate limiting - Adjusted for better user experience
session_start();
$rate_limit_key = 'password_reset_' . $_SERVER['REMOTE_ADDR'];
$rate_limit = 3; // Max 3 attempts per 15 minutes (more reasonable)
$rate_window = 900; // 15 minutes instead of 1 hour

if (!isset($_SESSION[$rate_limit_key])) {
    $_SESSION[$rate_limit_key] = [];
}

// Clean old attempts
$_SESSION[$rate_limit_key] = array_filter($_SESSION[$rate_limit_key], function($timestamp) use ($rate_window) {
    return (time() - $timestamp) < $rate_window;
});

function sendResponse($success, $message, $data = null) {
    // Clean any output buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Ensure we only send clean JSON
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data
    ];
    
    echo json_encode($response);
    exit;
}

function logError($message) {
    error_log("[Forgot Password API] " . $message);
}

function checkRateLimit($rate_limit_key, $rate_limit) {
    if (count($_SESSION[$rate_limit_key]) >= $rate_limit) {
        sendResponse(false, 'Too many password reset attempts. Please try again later.');
    }
    $_SESSION[$rate_limit_key][] = time();
}

function generateResetCode() {
    return sprintf('%06d', mt_rand(100000, 999999));
}

function sendResetEmail($email, $code) {
    try {
        // Enhanced logging
        error_log("Attempting to send reset email to: " . $email . " with code: " . $code);
        
        // Check if PHPMailer is available first
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            error_log("PHPMailer class not available, attempting to load...");
            
            // Try different possible paths for PHPMailer
            $phpmailer_paths = [
                'PHPMailer-master/src/',
                '../PHPMailer-master/src/',
                '../../PHPMailer-master/src/',
                'PHPMailer/src/',
                '../PHPMailer/src/',
                '../../PHPMailer/src/'
            ];
            
            $phpmailer_loaded = false;
            foreach ($phpmailer_paths as $path) {
                if (file_exists($path . 'PHPMailer.php')) {
                    require $path . 'Exception.php';
                    require $path . 'PHPMailer.php';
                    require $path . 'SMTP.php';
                    $phpmailer_loaded = true;
                    error_log("PHPMailer loaded from: " . $path);
                    break;
                }
            }
            
            if (!$phpmailer_loaded) {
                error_log("PHPMailer not found in any expected location");
                return false;
            }
        }
        
        // Try using native PHP mail() function with XAMPP sendmail configuration first
        $subject = "Password Reset Code - Yadawity Gallery";
        
        $htmlBody = "
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
                    <h1>Password Reset Request</h1>
                </div>
                <div class='content'>
                    <p>Hello,</p>
                    <p>You have requested to reset your password for your Yadawity Gallery account. Please use the verification code below:</p>
                    <div class='code'>" . $code . "</div>
                    <p><strong>Important:</strong></p>
                    <ul>
                        <li>This code will expire in 15 minutes</li>
                        <li>If you didn't request this reset, please ignore this email</li>
                        <li>Never share this code with anyone</li>
                    </ul>
                    <p>Best regards,<br>The Yadawity Gallery Team</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Yadawity Gallery <yadawitygallery@gmail.com>" . "\r\n";
        $headers .= "Reply-To: yadawitygallery@gmail.com" . "\r\n";

        // Skip native mail() function on server - use PHPMailer directly
        error_log("Using PHPMailer for server email sending...");
        
        $mail = new PHPMailer(true);
        
        try {
            // Server settings for Gmail SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'yadawitygallery@gmail.com';
            $mail->Password = 'cauryohslntbljug'; // Your app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            
            // Server-optimized settings
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            $mail->Timeout = 60;
            $mail->SMTPKeepAlive = false;
            
            // Disable debugging in production
            $mail->SMTPDebug = 0;
            
            // Recipients
            $mail->setFrom('yadawitygallery@gmail.com', 'Yadawity Gallery');
            $mail->addAddress($email);
            $mail->addReplyTo('yadawitygallery@gmail.com', 'Yadawity Gallery');
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = "Password Reset Code: {$code}\n\nThis code will expire in 15 minutes.\nIf you didn't request this reset, please ignore this email.";

            // Send the email
            $result = $mail->send();
            
            if ($result) {
                error_log("Password reset email sent successfully via PHPMailer to: " . $email);
                return true;
            } else {
                error_log("PHPMailer send() returned false - check SMTP configuration");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("PHPMailer Exception during send: " . $e->getMessage());
            throw $e; // Re-throw to be caught by outer try-catch
        }
        
    } catch (Exception $e) {
        // Enhanced error logging for debugging
        $errorDetails = [
            'email' => $email,
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'method_used' => 'PHPMailer fallback',
            'server_info' => [
                'php_version' => phpversion(),
                'openssl_loaded' => extension_loaded('openssl') ? 'Yes' : 'No',
                'sendmail_path' => ini_get('sendmail_path'),
                'smtp_setting' => ini_get('SMTP'),
                'smtp_port' => ini_get('smtp_port')
            ]
        ];
        
        error_log("DETAILED EMAIL ERROR: " . json_encode($errorDetails, JSON_PRETTY_PRINT));
        error_log("Failed to send reset email to {$email}: " . $e->getMessage());
        
        return false;
    } catch (Error $e) {
        // Catch fatal errors from mail() function
        error_log("Mail function error: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logError("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    sendResponse(false, 'Invalid request method');
}

try {
    $action = $_POST['action'] ?? '';
    logError("Action requested: " . $action);

    switch ($action) {
    case 'send_code':
        checkRateLimit($rate_limit_key, $rate_limit);
        
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        if (!$email) {
            sendResponse(false, 'Please provide a valid email address');
        }

        logError("Checking if email exists in database: " . $email);

        // Check if user exists
        $stmt = $db->prepare("SELECT user_id, email, first_name FROM users WHERE email = ? AND is_active = 1");
        if (!$stmt) {
            logError("Failed to prepare user check query: " . $db->error);
            sendResponse(false, 'Database error occurred');
        }

        $stmt->bind_param("s", $email);
        if (!$stmt->execute()) {
            logError("Failed to execute user check query: " . $stmt->error);
            sendResponse(false, 'Database error occurred');
        }
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // Log the attempt for unregistered email
            logError("Password reset attempted for unregistered email: " . $email);
            // For security, we still return success but don't send email
            sendResponse(true, 'If this email is registered, you will receive a reset code shortly');
        }

        $user = $result->fetch_assoc();
        logError("Password reset requested for registered user: " . $email . " (User ID: " . $user['user_id'] . ")");
        
        $code = generateResetCode();
        $expires_at = gmdate('Y-m-d H:i:s', time() + 900); // 15 minutes in UTC

        // Store reset code in database
        $reset_stmt = $db->prepare("INSERT INTO password_resets (email, reset_code, expires_at, created_at) VALUES (?, ?, ?, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE reset_code = VALUES(reset_code), expires_at = VALUES(expires_at), created_at = UTC_TIMESTAMP(), is_used = 0");
        
        if (!$reset_stmt) {
            logError("Failed to prepare reset code insert query: " . $db->error);
            sendResponse(false, 'Database error occurred');
        }

        $reset_stmt->bind_param("sss", $email, $code, $expires_at);
        
        if (!$reset_stmt->execute()) {
            logError("Failed to execute reset code insert query: " . $reset_stmt->error);
            sendResponse(false, 'Failed to generate reset code');
        }

        logError("Reset code generated and stored for: " . $email . " (Code: " . $code . ")");

        // Send email
        $emailResult = sendResetEmail($email, $code);
        
        if ($emailResult) {
            logError("Reset email sent successfully to: " . $email);
            sendResponse(true, 'Reset code sent to your email address');
        } else {
            logError("Failed to send reset email to: " . $email);
            // Clean up the reset code since email failed
            $cleanup_stmt = $db->prepare("DELETE FROM password_resets WHERE email = ? AND reset_code = ?");
            if ($cleanup_stmt) {
                $cleanup_stmt->bind_param("ss", $email, $code);
                $cleanup_stmt->execute();
                logError("Cleaned up reset code for failed email attempt");
            }
            sendResponse(false, 'Unable to send reset code. Please check your email address and try again.');
        }
        break;

    case 'verify_code':
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $code = preg_replace('/[^0-9]/', '', $_POST['code'] ?? '');

        if (!$email || !$code) {
            sendResponse(false, 'Please provide email and verification code');
        }

        // Check if code is valid and not expired
        $stmt = $db->prepare("SELECT id, reset_code FROM password_resets WHERE email = ? AND reset_code = ? AND expires_at > UTC_TIMESTAMP() AND is_used = 0 ORDER BY created_at DESC LIMIT 1");
        
        if (!$stmt) {
            sendResponse(false, 'Database error occurred');
        }

        $stmt->bind_param("ss", $email, $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            sendResponse(false, 'Invalid or expired verification code');
        }

        sendResponse(true, 'Code verified successfully');
        break;

    case 'reset_password':
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $code = preg_replace('/[^0-9]/', '', $_POST['code'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$code || !$password) {
            sendResponse(false, 'Please provide all required fields');
        }

        // Validate password strength
        if (strlen($password) < 8) {
            sendResponse(false, 'Password must be at least 8 characters long');
        }

        // Verify code again
        $stmt = $db->prepare("SELECT id FROM password_resets WHERE email = ? AND reset_code = ? AND expires_at > UTC_TIMESTAMP() AND is_used = 0 ORDER BY created_at DESC LIMIT 1");
        
        if (!$stmt) {
            sendResponse(false, 'Database error occurred');
        }

        $stmt->bind_param("ss", $email, $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            sendResponse(false, 'Invalid or expired verification code');
        }

        $reset_record = $result->fetch_assoc();

        // Update user password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ? AND is_active = 1");
        
        if (!$update_stmt) {
            sendResponse(false, 'Database error occurred');
        }

        $update_stmt->bind_param("ss", $hashed_password, $email);
        
        if (!$update_stmt->execute()) {
            sendResponse(false, 'Failed to update password');
        }

        // Mark reset code as used
        $mark_used_stmt = $db->prepare("UPDATE password_resets SET is_used = 1 WHERE id = ?");
        if ($mark_used_stmt) {
            $mark_used_stmt->bind_param("i", $reset_record['id']);
            $mark_used_stmt->execute();
        }

        // Invalidate all user sessions for security
        $user_stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        if ($user_stmt) {
            $user_stmt->bind_param("s", $email);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            
            if ($user_result->num_rows > 0) {
                $user_data = $user_result->fetch_assoc();
                $cleanup_stmt = $db->prepare("UPDATE user_login_sessions SET is_active = 0, logout_time = NOW() WHERE user_id = ?");
                if ($cleanup_stmt) {
                    $cleanup_stmt->bind_param("i", $user_data['user_id']);
                    $cleanup_stmt->execute();
                }
            }
        }

        sendResponse(true, 'Password reset successfully');
        break;

    default:
        sendResponse(false, 'Invalid action');
    }

} catch (Exception $e) {
    error_log("Unexpected error in forgetPassword API: " . $e->getMessage());
    sendResponse(false, 'An unexpected error occurred');
} catch (Error $e) {
    error_log("Fatal error in forgetPassword API: " . $e->getMessage());
    sendResponse(false, 'A system error occurred');
}
?>
