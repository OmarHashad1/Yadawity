<?php
// Start output buffering to prevent any unwanted output
ob_start();

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header for API responses
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check the action type - could be in POST or JSON body
$action = $_POST['action'] ?? 'upload';

// For JSON requests, check the request body
if ($action === 'upload') {
    $json_input = json_decode(file_get_contents('php://input'), true);
    if ($json_input && isset($json_input['action'])) {
        $action = $json_input['action'];
    }
}

$isSignupUpload = isset($_POST['signup_upload']) && $_POST['signup_upload'] === 'true';

// For regular uploads, check authentication (skip for signup uploads)
if (!$isSignupUpload && (!isset($_SESSION['user_id']) || !isset($_SESSION['email']))) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Database configuration
require_once 'db.php';

// Ensure the artist_id_photos table exists
$createTableQuery = "CREATE TABLE IF NOT EXISTS artist_id_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    national_id VARCHAR(20) NOT NULL,
    front_photo VARCHAR(255) NOT NULL,
    back_photo VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    verification_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    INDEX idx_user_id (user_id),
    INDEX idx_national_id (national_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

if (!$db->query($createTableQuery)) {
    ob_end_clean();
    error_log("Failed to create artist_id_photos table: " . $db->error);
    echo json_encode(['success' => false, 'message' => 'Database initialization failed']);
    exit;
}

// Log the request for debugging
error_log("uploadIdPhotos.php called - Action: $action, IsSignupUpload: " . ($isSignupUpload ? 'true' : 'false'));
error_log("POST data: " . json_encode($_POST));
error_log("FILES data: " . json_encode(array_keys($_FILES)));

try {
    // Get user_id based on context
    if ($isSignupUpload) {
        // For signup uploads, get the actual user_id passed from registration
        if (isset($_POST['actual_user_id']) && !empty($_POST['actual_user_id']) && $_POST['actual_user_id'] > 0) {
            $user_id = (int)$_POST['actual_user_id']; // Use actual user_id from registration
            $isTemporary = false; // Not temporary since we have real user_id
            error_log("Signup upload with actual_user_id: " . $user_id);
        } else {
            error_log("Signup upload missing or invalid actual_user_id - POST data: " . json_encode($_POST));
            error_log("actual_user_id value: " . ($_POST['actual_user_id'] ?? 'NOT_SET'));
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Valid User ID is required for signup upload. Received: ' . ($_POST['actual_user_id'] ?? 'NOT_SET')]);
            exit;
        }
    } else {
        $user_id = $_SESSION['user_id'];
        $isTemporary = false;
        error_log("Regular upload with session user_id: " . $user_id);
    }
    
    // Validate required fields
    if (!isset($_POST['national_id']) || empty(trim($_POST['national_id']))) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'National ID is required']);
        exit;
    }
    
    if (!isset($_FILES['front_photo']) || !isset($_FILES['back_photo'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Both front and back photos are required']);
        exit;
    }
    
    $national_id = trim($_POST['national_id']);
    
    // Validate national ID format (allow numbers, at least 8 digits)
    if (!preg_match('/^[0-9]{8,20}$/', $national_id)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid national ID format. Must be 8-20 digits.']);
        exit;
    }
    
    // Validate file uploads
    $front_photo = $_FILES['front_photo'];
    $back_photo = $_FILES['back_photo'];
    
    // Check for upload errors
    if ($front_photo['error'] !== UPLOAD_ERR_OK || $back_photo['error'] !== UPLOAD_ERR_OK) {
        $front_error = $front_photo['error'];
        $back_error = $back_photo['error'];
        error_log("Upload errors - Front: $front_error, Back: $back_error");
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => "Error uploading files. Front error: $front_error, Back error: $back_error"]);
        exit;
    }
    
    // Validate file types
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!in_array($front_photo['type'], $allowed_types) || !in_array($back_photo['type'], $allowed_types)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Only JPEG, PNG, and WebP images are allowed']);
        exit;
    }
    
    // Validate file sizes (max 10MB each)
    $max_size = 10 * 1024 * 1024; // 10MB
    if ($front_photo['size'] > $max_size || $back_photo['size'] > $max_size) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'File size must be less than 10MB']);
        exit;
    }
    
    // Create upload directory - use absolute path for better reliability
    $base_path = dirname(__DIR__); // Go up one directory from API to htdocs
    $upload_dir = $base_path . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'verification' . DIRECTORY_SEPARATOR;
    
    // Log the paths for debugging
    error_log("Base path: " . $base_path);
    error_log("Upload directory: " . $upload_dir);
    error_log("Upload directory exists: " . (file_exists($upload_dir) ? 'yes' : 'no'));
    error_log("Upload directory writable: " . (is_writable($upload_dir) ? 'yes' : 'no'));
    
    // Ensure the parent uploads directory exists first
    $parent_dir = $base_path . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    if (!file_exists($parent_dir)) {
        if (!mkdir($parent_dir, 0777, true)) {
            error_log("Failed to create parent uploads directory: " . $parent_dir);
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to create parent uploads directory']);
            exit;
        }
        // Set proper permissions
        chmod($parent_dir, 0777);
    }
    
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            error_log("Failed to create upload directory: " . $upload_dir);
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
            exit;
        }
        // Set proper permissions
        chmod($upload_dir, 0777);
    }
    
    // Check if directory is writable
    if (!is_writable($upload_dir)) {
        error_log("Upload directory not writable: " . $upload_dir);
        // Try to fix permissions
        if (chmod($upload_dir, 0777)) {
            error_log("Successfully changed permissions to 777 for: " . $upload_dir);
        } else {
            error_log("Failed to set permissions for: " . $upload_dir);
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Upload directory permissions cannot be set. Directory: ' . $upload_dir]);
            exit;
        }
        
        // Check again after permission change
        if (!is_writable($upload_dir)) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Upload directory not writable even after permission changes: ' . $upload_dir]);
            exit;
        }
    }
    
    // Test write capability by creating a temporary file
    $test_file = $upload_dir . 'test_write_' . time() . '.tmp';
    if (file_put_contents($test_file, 'test') === false) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Cannot write to upload directory: ' . $upload_dir]);
        exit;
    } else {
        // Clean up test file
        unlink($test_file);
        error_log("Write test successful for: " . $upload_dir);
    }
    
    // Generate filenames with the new format: national-id_{id}_user_{user_id}
    // We'll add the record ID after database insertion
    $timestamp = time();
    
    // Get file extensions
    $front_extension = strtolower(pathinfo($front_photo['name'], PATHINFO_EXTENSION));
    $back_extension = strtolower(pathinfo($back_photo['name'], PATHINFO_EXTENSION));
    
    // Temporary filenames (will be renamed after database insertion to include record ID)
    $temp_front_filename = 'temp_' . $national_id . '_user_' . $user_id . '_front_' . $timestamp . '.' . $front_extension;
    $temp_back_filename = 'temp_' . $national_id . '_user_' . $user_id . '_back_' . $timestamp . '.' . $back_extension;
    
    $front_filepath = $upload_dir . $temp_front_filename;
    $back_filepath = $upload_dir . $temp_back_filename;
    
    // Move uploaded files
    if (!move_uploaded_file($front_photo['tmp_name'], $front_filepath)) {
        $error_msg = 'Failed to upload front photo';
        if (!is_writable($upload_dir)) {
            $error_msg .= ' - Upload directory not writable';
        }
        if (!file_exists($front_photo['tmp_name'])) {
            $error_msg .= ' - Temporary file not found';
        }
        error_log("Front photo upload failed: " . $error_msg . " | Temp: " . $front_photo['tmp_name'] . " | Target: " . $front_filepath);
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => $error_msg]);
        exit;
    }
    
    if (!move_uploaded_file($back_photo['tmp_name'], $back_filepath)) {
        // If back photo fails, remove the front photo
        if (file_exists($front_filepath)) {
            unlink($front_filepath);
        }
        $error_msg = 'Failed to upload back photo';
        if (!is_writable($upload_dir)) {
            $error_msg .= ' - Upload directory not writable';
        }
        if (!file_exists($back_photo['tmp_name'])) {
            $error_msg .= ' - Temporary file not found';
        }
        error_log("Back photo upload failed: " . $error_msg . " | Temp: " . $back_photo['tmp_name'] . " | Target: " . $back_filepath);
        echo json_encode(['success' => false, 'message' => $error_msg]);
        exit;
    }
    
    // Check if user already has ID photos uploaded
    $check_stmt = $db->prepare("SELECT id, front_photo, back_photo FROM artist_id_photos WHERE user_id = ?");
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();
    
    if ($existing) {
        // Delete old files if they exist
        $old_front = '../uploads/verification/' . $existing['front_photo'];
        $old_back = '../uploads/verification/' . $existing['back_photo'];
        
        if (file_exists($old_front)) {
            unlink($old_front);
        }
        if (file_exists($old_back)) {
            unlink($old_back);
        }
        
        // Generate final filenames: national-id_{id}_user_{user_id}
        $final_front_filename = $national_id . '_' . $existing['id'] . '_user_' . $user_id . '_front.' . $front_extension;
        $final_back_filename = $national_id . '_' . $existing['id'] . '_user_' . $user_id . '_back.' . $back_extension;
        
        // Rename temp files to final names
        $final_front_filepath = $upload_dir . $final_front_filename;
        $final_back_filepath = $upload_dir . $final_back_filename;
        
        if (!rename($front_filepath, $final_front_filepath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to rename front photo']);
            exit;
        }
        
        if (!rename($back_filepath, $final_back_filepath)) {
            // If back fails, revert front
            rename($final_front_filepath, $front_filepath);
            echo json_encode(['success' => false, 'message' => 'Failed to rename back photo']);
            exit;
        }
        
        // Update existing record
        $update_stmt = $db->prepare("UPDATE artist_id_photos SET national_id = ?, front_photo = ?, back_photo = ?, updated_at = NOW() WHERE user_id = ?");
        $update_stmt->bind_param("sssi", $national_id, $final_front_filename, $final_back_filename, $user_id);
        $result = $update_stmt->execute();
        $update_stmt->close();
        
        if ($result) {
            $message = $isSignupUpload ? 'ID photos uploaded for verification successfully' : 'ID photos updated successfully. They will be reviewed for verification.';
            echo json_encode([
                'success' => true,
                'message' => $message,
                'front_photo' => $final_front_filename,
                'back_photo' => $final_back_filename
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update ID photos in database']);
        }
    } else {
        // Insert new record first with placeholder filenames
        $insert_stmt = $db->prepare("INSERT INTO artist_id_photos (user_id, national_id, front_photo, back_photo, upload_date, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $insert_stmt->bind_param("isss", $user_id, $national_id, $temp_front_filename, $temp_back_filename);
        $result = $insert_stmt->execute();
        $record_id = $db->insert_id;
        
        if (!$result) {
            error_log("Database insert failed: " . $db->error);
            error_log("SQL Error: " . $insert_stmt->error);
            $insert_stmt->close();
            
            // Remove uploaded files since database failed
            if (file_exists($front_filepath)) {
                unlink($front_filepath);
            }
            if (file_exists($back_filepath)) {
                unlink($back_filepath);
            }
            echo json_encode(['success' => false, 'message' => 'Failed to save ID photos to database']);
            exit;
        }
        
        $insert_stmt->close();
        
        // Generate final filenames: national-id_{id}_user_{user_id}
        $final_front_filename = $national_id . '_' . $record_id . '_user_' . $user_id . '_front.' . $front_extension;
        $final_back_filename = $national_id . '_' . $record_id . '_user_' . $user_id . '_back.' . $back_extension;
        
        // Rename temp files to final names
        $final_front_filepath = $upload_dir . $final_front_filename;
        $final_back_filepath = $upload_dir . $final_back_filename;
        
        if (!rename($front_filepath, $final_front_filepath)) {
            // If rename fails, delete the database record and files
            $db->prepare("DELETE FROM artist_id_photos WHERE id = ?")->execute([$record_id]);
            if (file_exists($back_filepath)) {
                unlink($back_filepath);
            }
            echo json_encode(['success' => false, 'message' => 'Failed to rename front photo']);
            exit;
        }
        
        if (!rename($back_filepath, $final_back_filepath)) {
            // If back fails, revert front and delete database record
            rename($final_front_filepath, $front_filepath);
            $db->prepare("DELETE FROM artist_id_photos WHERE id = ?")->execute([$record_id]);
            unlink($front_filepath);
            echo json_encode(['success' => false, 'message' => 'Failed to rename back photo']);
            exit;
        }
        
        // Update the database record with final filenames
        $update_stmt = $db->prepare("UPDATE artist_id_photos SET front_photo = ?, back_photo = ? WHERE id = ?");
        $update_stmt->bind_param("ssi", $final_front_filename, $final_back_filename, $record_id);
        $update_result = $update_stmt->execute();
        $update_stmt->close();
        
        if ($update_result) {
            $message = $isSignupUpload ? 'ID photos uploaded for verification successfully' : 'ID photos uploaded successfully. They will be reviewed for verification.';
            echo json_encode([
                'success' => true,
                'message' => $message,
                'front_photo' => $final_front_filename,
                'back_photo' => $final_back_filename,
                'record_id' => $record_id
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update photo filenames in database']);
        }
    }
    
} catch (Exception $e) {
    ob_end_clean();
    error_log("ID photo upload error: " . $e->getMessage());
    error_log("ID photo upload error trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'message' => 'An error occurred while uploading ID photos: ' . $e->getMessage()]);
} finally {
    // Always close database connection and clean output buffer
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
    // End output buffering
    if (ob_get_level()) {
        ob_end_flush();
    }
}
?>
