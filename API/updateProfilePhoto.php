<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "db.php";
require_once "checkCredentials.php";

// Use correct database connection variable
$conn = $db;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check authentication
$credentials = checkUserCredentials();
$user_id = null;

if ($credentials['authenticated']) {
    // Use authenticated user ID
    $user_id = $credentials['user_id'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    // For testing purposes, allow direct user ID
    $user_id = (int)$_POST['user_id'];
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error occurred']);
    exit;
}

$uploadedFile = $_FILES['profile_photo'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
$fileType = $uploadedFile['type'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes) || !in_array($fileType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, and GIF images are allowed']);
    exit;
}

// Validate file size (max 5MB)
$maxSize = 5 * 1024 * 1024; // 5MB in bytes
if ($uploadedFile['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File size too large. Maximum size is 5MB']);
    exit;
}

try {
    // Get user information for filename
    $userStmt = $conn->prepare("SELECT first_name, last_name, profile_picture FROM users WHERE user_id = ?");
    if (!$userStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $userStmt->bind_param("i", $user_id);
    $userStmt->execute();
    $result = $userStmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Create filename based on naming convention: user_{user_id}_{first_name}_{last_name}_profile.jpg
    $firstName = strtolower(str_replace(' ', '_', $user['first_name']));
    $lastName = strtolower(str_replace(' ', '_', $user['last_name']));
    $fileExtension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
    $newFileName = "user_{$user_id}_{$firstName}_{$lastName}_profile.{$fileExtension}";
    
    // Define upload directory (use relative path)
    $uploadDir = '../uploads/user_profile_picture/';
    
    // Ensure upload directory exists and is writable
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
            exit;
        }
        // Set permissions after creation
        chmod($uploadDir, 0777);
    }
    
    // Try to make directory writable if it isn't
    if (!is_writable($uploadDir)) {
        chmod($uploadDir, 0777);
        if (!is_writable($uploadDir)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Upload directory is not writable',
                'debug' => [
                    'uploadDir' => $uploadDir,
                    'exists' => file_exists($uploadDir),
                    'writable' => is_writable($uploadDir),
                    'permissions' => substr(sprintf('%o', fileperms($uploadDir)), -4)
                ]
            ]);
            exit;
        }
    }
    
    $newFilePath = $uploadDir . $newFileName;
    
    // Delete old profile picture if exists
    if (!empty($user['profile_picture'])) {
        $oldFilePath = $uploadDir . $user['profile_picture'];
        if (file_exists($oldFilePath)) {
            unlink($oldFilePath);
        }
    }
    
    // Move uploaded file to destination
    if (!move_uploaded_file($uploadedFile['tmp_name'], $newFilePath)) {
        $uploadError = error_get_last();
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to save uploaded file',
            'debug' => [
                'uploadDir' => $uploadDir,
                'newFilePath' => $newFilePath,
                'tmpName' => $uploadedFile['tmp_name'],
                'fileExists' => file_exists($uploadedFile['tmp_name']),
                'dirWritable' => is_writable($uploadDir),
                'error' => $uploadError
            ]
        ]);
        exit;
    }
    
    // Update database with new profile picture filename
    $updateStmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
    if (!$updateStmt) {
        // If file was uploaded but DB update fails, delete the uploaded file
        unlink($newFilePath);
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $updateStmt->bind_param("si", $newFileName, $user_id);
    $updateResult = $updateStmt->execute();
    
    if ($updateResult && $conn->affected_rows >= 0) {
        echo json_encode([
            'success' => true, 
            'message' => 'Profile photo updated successfully',
            'profile_picture' => $newFileName,
            'profile_picture_url' => './uploads/user_profile_picture/' . $newFileName,
            'affected_rows' => $conn->affected_rows
        ]);
    } else {
        // If DB update fails, delete the uploaded file
        unlink($newFilePath);
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to update profile picture in database',
            'error' => $updateStmt->error,
            'affected_rows' => $conn->affected_rows
        ]);
    }
    
} catch (Exception $e) {
    error_log("Update profile photo error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>