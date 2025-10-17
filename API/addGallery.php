<?php
session_start();
header('Content-Type: application/json');

// Disable HTML error output to prevent JSON corruption
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Custom error handler to log errors instead of displaying them
set_error_handler(function($severity, $message, $file, $line) {
    error_log("PHP Error: $message in $file on line $line");
    return true;
});

include 'db.php';

// Image signature validation function
function validateImageSignature($filePath, $expectedExtension) {
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return ['isValid' => false, 'message' => 'File not found or not readable for signature validation.'];
    }
    
    // Read the first 32 bytes of the file to check signature
    $handle = fopen($filePath, 'rb');
    if (!$handle) {
        return ['isValid' => false, 'message' => 'Unable to open file for signature validation.'];
    }
    
    $bytes = fread($handle, 32);
    fclose($handle);
    
    if ($bytes === false || strlen($bytes) < 4) {
        return ['isValid' => false, 'message' => 'Unable to read file signature.'];
    }
    
    // Convert bytes to hex for easier comparison
    $hex = bin2hex($bytes);
    
    // Define image signatures (magic bytes)
    $signatures = [
        'jpg' => [
            'ffd8ffe0', // JPEG with JFIF
            'ffd8ffe1', // JPEG with EXIF
            'ffd8ffe2', // JPEG with ICC
            'ffd8ffe3', // JPEG with JPS
            'ffd8ffe8', // JPEG with SPIFF
            'ffd8ffdb', // JPEG with quantization tables
            'ffd8ffee'  // JPEG with Adobe
        ],
        'jpeg' => [
            'ffd8ffe0', 'ffd8ffe1', 'ffd8ffe2', 'ffd8ffe3', 
            'ffd8ffe8', 'ffd8ffdb', 'ffd8ffee'
        ],
        'png' => ['89504e47'], // PNG signature
        'gif' => [
            '47494638', // GIF87a and GIF89a (first 4 bytes)
        ],
        'webp' => ['52494646'] // RIFF (first 4 bytes of WebP)
    ];
    
    // Get expected signatures for the file extension
    if (!isset($signatures[$expectedExtension])) {
        return ['isValid' => false, 'message' => 'Unknown image format for signature validation.'];
    }
    
    $expectedSignatures = $signatures[$expectedExtension];
    
    // Check if any of the expected signatures match
    $isValidSignature = false;
    foreach ($expectedSignatures as $signature) {
        $signatureLength = strlen($signature);
        $fileSignature = substr($hex, 0, $signatureLength);
        
        if (strcasecmp($fileSignature, $signature) === 0) {
            $isValidSignature = true;
            break;
        }
    }
    
    // Additional check for WebP (needs to check for WEBP signature after RIFF)
    if ($expectedExtension === 'webp' && $isValidSignature) {
        // For WebP, check if bytes 8-11 contain "WEBP"
        if (strlen($hex) >= 24) {
            $webpSignature = substr($hex, 16, 8); // bytes 8-11 in hex
            if (strcasecmp($webpSignature, '57454250') !== 0) { // "WEBP" in hex
                $isValidSignature = false;
            }
        } else {
            $isValidSignature = false;
        }
    }
    
    // Additional check for GIF (check full signature)
    if ($expectedExtension === 'gif' && $isValidSignature) {
        // For GIF, check if it's GIF87a or GIF89a
        if (strlen($hex) >= 12) {
            $gifSignature = substr($hex, 0, 12); // First 6 bytes
            if (strcasecmp($gifSignature, '474946383761') !== 0 && // GIF87a
                strcasecmp($gifSignature, '474946383961') !== 0) {  // GIF89a
                $isValidSignature = false;
            }
        } else {
            $isValidSignature = false;
        }
    }
    
    if (!$isValidSignature) {
        return [
            'isValid' => false, 
            'message' => 'File signature does not match expected image format. This may be a corrupted file or a file with wrong extension.'
        ];
    }
    
    return ['isValid' => true, 'message' => 'Valid image signature.'];
}

// Function to check and update database schema
function updateDatabaseSchema($conn) {
    try {
        // The galleries table already has an 'img' column, so we don't need to add primary_image
        // Just log that we're using the existing structure
        error_log("Using existing galleries table structure with 'img' column");
        
        // Check if gallery_photos table exists
        $checkTable = "SHOW TABLES LIKE 'gallery_photos'";
        $result = $conn->query($checkTable);
        
        if ($result->num_rows == 0) {
            // Create gallery_photos table if it doesn't exist
            $createTable = "CREATE TABLE gallery_photos (
                photo_id INT(11) NOT NULL AUTO_INCREMENT,
                gallery_id INT(11) NOT NULL,
                image_path VARCHAR(500) COLLATE utf8mb4_general_ci NOT NULL,
                is_primary TINYINT(1) NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
                PRIMARY KEY (photo_id),
                FOREIGN KEY (gallery_id) REFERENCES galleries(gallery_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            
            if ($conn->query($createTable)) {
                error_log("Successfully created gallery_photos table");
            } else {
                error_log("Error creating gallery_photos table: " . $conn->error);
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Database schema update error: " . $e->getMessage());
        return false;
    }
}

include 'db.php';

// Use correct database connection variable
$conn = $db;

// Update database schema if needed
updateDatabaseSchema($conn);

// Check if user is logged in using cookie-based authentication
function checkAuthentication() {
    global $db;
    
    // Check if cookie exists
    if (!isset($_COOKIE['user_login'])) {
        return ['success' => false, 'message' => 'No authentication cookie found. Please log in.'];
    }
    
    $cookieValue = $_COOKIE['user_login'];
    $parts = explode('_', $cookieValue, 2);
    
    if (count($parts) !== 2) {
        return ['success' => false, 'message' => 'Invalid authentication cookie. Please log in again.'];
    }
    
    $user_id = (int)$parts[0];
    $provided_hash = $parts[1];
    
    if ($user_id <= 0) {
        return ['success' => false, 'message' => 'Invalid user ID in cookie.'];
    }
    
    // Get user data
    $stmt = $db->prepare("SELECT email, first_name, user_type, is_active, is_verified FROM users WHERE user_id = ? AND is_active = 1");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error during authentication.'];
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'User not found. Please log in again.'];
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Check if user is active
    if (!$user['is_active']) {
        return ['success' => false, 'message' => 'Account is not active. Please contact support.'];
    }
    
    // Get all active login sessions for this user
    $session_stmt = $db->prepare("
        SELECT session_id, login_time 
        FROM user_login_sessions 
        WHERE user_id = ? AND is_active = 1 
        ORDER BY login_time DESC
    ");
    
    if (!$session_stmt) {
        return ['success' => false, 'message' => 'Database error during session validation.'];
    }
    
    $session_stmt->bind_param("i", $user_id);
    $session_stmt->execute();
    $session_result = $session_stmt->get_result();
    
    // Try to validate the hash against any active session
    $valid_session = false;
    while ($session = $session_result->fetch_assoc()) {
        $cookie_data = $session['session_id'] . '|' . $user['email'] . '|' . $user_id . '|' . $session['login_time'];
        $expected_hash = hash_hmac('sha256', $cookie_data, $session['session_id'] . '_' . $user['email']);
        if ($provided_hash === $expected_hash) {
            $valid_session = true;
            break;
        }
    }
    $session_stmt->close();
    
    if (!$valid_session) {
        return ['success' => false, 'message' => 'Invalid authentication token. Please log in again.'];
    }
    
    return [
        'success' => true, 
        'user_id' => $user_id, 
        'user_type' => $user['user_type'],
        'is_active' => $user['is_active'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'is_verified' => $user['is_verified']
    ];
}

// Authenticate user
$authResult = checkAuthentication();
if (!$authResult['success']) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $authResult['message']
    ]);
    exit;
}

$artist_id = (int)$authResult['user_id'];

// File upload handler function (similar to addArtwork.php)
function handleFileUpload($file, $uploadDir = null) {
    if ($uploadDir === null) {
        $uploadDir = dirname(__DIR__) . '/uploads/galleries/';
    }
    
    // Create upload directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            error_log("Failed to create upload directory: $uploadDir");
            return ['success' => false, 'message' => 'Failed to create upload directory. Please check permissions.'];
        }
        // Set proper permissions after creation
        chmod($uploadDir, 0777);
    }
    
    // Check if directory is writable
    if (!is_writable($uploadDir)) {
        // Try to make it writable
        chmod($uploadDir, 0777);
        if (!is_writable($uploadDir)) {
            error_log("Upload directory is not writable: $uploadDir");
            return ['success' => false, 'message' => 'Upload directory is not writable. Please check permissions.'];
        }
    }
    
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // Validate file extension only
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedExtensions)) {
        return ['success' => false, 'message' => 'Invalid file extension. Only JPG, PNG, GIF, and WebP are allowed.'];
    }
    
    // Validate image signature/magic bytes
    $signatureValidation = validateImageSignature($file['tmp_name'], $fileExtension);
    if (!$signatureValidation['isValid']) {
        return ['success' => false, 'message' => $signatureValidation['message']];
    }
    
    // Generate unique filename
    $filename = uniqid('gallery_') . '_' . time() . '.' . $fileExtension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Return relative path for database storage
        $relativePath = 'uploads/galleries/' . $filename;
        return ['success' => true, 'filename' => $filename, 'path' => $relativePath];
    } else {
        $error = error_get_last();
        error_log("Failed to move uploaded file: " . ($error ? $error['message'] : 'Unknown error'));
        return ['success' => false, 'message' => 'Failed to upload file. Please check file permissions and try again.'];
    }
}

// Function to validate input
function validateInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Function to validate phone
function validatePhone($phone) {
    return preg_match('/^[0-9+\-\s()]+$/', $phone);
}

// Function to handle gallery tags (insert tags and create relationships)
function handleGalleryTags($conn, $gallery_id, $tags) {
    if (empty($tags)) {
        return ['success' => true, 'message' => 'No tags to process'];
    }
    
    $tag_ids = [];
    
    try {
        // Begin transaction for tag operations
        $conn->begin_transaction();
        
        foreach ($tags as $tag_name) {
            $tag_name = trim($tag_name);
            if (empty($tag_name)) continue;
            
            // Check if tag already exists
            $check_tag_sql = "SELECT tag_id FROM tags WHERE name = ?";
            $check_stmt = $conn->prepare($check_tag_sql);
            if (!$check_stmt) {
                throw new Exception("Failed to prepare tag check statement: " . $conn->error);
            }
            
            $check_stmt->bind_param("s", $tag_name);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Tag exists, get its ID
                $row = $result->fetch_assoc();
                $tag_id = $row['tag_id'];
            } else {
                // Tag doesn't exist, create it
                $insert_tag_sql = "INSERT INTO tags (name, created_at) VALUES (?, NOW())";
                $insert_stmt = $conn->prepare($insert_tag_sql);
                if (!$insert_stmt) {
                    throw new Exception("Failed to prepare tag insert statement: " . $conn->error);
                }
                
                $insert_stmt->bind_param("s", $tag_name);
                if (!$insert_stmt->execute()) {
                    throw new Exception("Failed to insert tag: " . $insert_stmt->error);
                }
                
                $tag_id = $conn->insert_id;
                $insert_stmt->close();
            }
            
            $check_stmt->close();
            $tag_ids[] = $tag_id;
        }
        
        // Now create gallery-tag relationships
        foreach ($tag_ids as $tag_id) {
            // Check if relationship already exists
            $check_relation_sql = "SELECT * FROM gallery_tags WHERE gallery_id = ? AND tag_id = ?";
            $check_rel_stmt = $conn->prepare($check_relation_sql);
            if (!$check_rel_stmt) {
                throw new Exception("Failed to prepare relationship check statement: " . $conn->error);
            }
            
            $check_rel_stmt->bind_param("ii", $gallery_id, $tag_id);
            $check_rel_stmt->execute();
            $rel_result = $check_rel_stmt->get_result();
            
            if ($rel_result->num_rows == 0) {
                // Relationship doesn't exist, create it
                $insert_relation_sql = "INSERT INTO gallery_tags (gallery_id, tag_id) VALUES (?, ?)";
                $insert_rel_stmt = $conn->prepare($insert_relation_sql);
                if (!$insert_rel_stmt) {
                    throw new Exception("Failed to prepare relationship insert statement: " . $conn->error);
                }
                
                $insert_rel_stmt->bind_param("ii", $gallery_id, $tag_id);
                if (!$insert_rel_stmt->execute()) {
                    throw new Exception("Failed to insert gallery-tag relationship: " . $insert_rel_stmt->error);
                }
                $insert_rel_stmt->close();
            }
            
            $check_rel_stmt->close();
        }
        
        $conn->commit();
        return ['success' => true, 'message' => 'Tags processed successfully', 'tag_count' => count($tag_ids)];
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Tag processing error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to process tags: ' . $e->getMessage()];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Log received data for debugging
        error_log("Received POST data: " . print_r($_POST, true));
        
        // Validate required fields
        $errors = [];
        
        // Get and validate form data
        $title = validateInput($_POST['title'] ?? '');
        $description = validateInput($_POST['description'] ?? '');
        $gallery_type = validateInput($_POST['gallery_type'] ?? '');
        $price = validateInput($_POST['price'] ?? '0');
        $address = validateInput($_POST['address'] ?? '');
        $city = validateInput($_POST['city'] ?? '');
        $phone = validateInput($_POST['phone'] ?? '');
        $duration = validateInput($_POST['duration'] ?? '');
        $start_date = validateInput($_POST['start_date'] ?? '');
        
        // Handle virtual gallery tags (using normalized tags table)
        $tags = [];
        if (isset($_POST['tags']) && !empty($_POST['tags'])) {
            $decodedTags = json_decode($_POST['tags'], true);
            if (is_array($decodedTags)) {
                $tags = array_map('validateInput', $decodedTags);
                // Limit to 10 tags and validate each tag
                $tags = array_slice(array_filter($tags, function($tag) {
                    return strlen($tag) >= 2 && strlen($tag) <= 50;
                }), 0, 10);
            }
        }
        
        // Validation checks
        if (empty($title)) {
            $errors[] = 'Gallery title is required';
        } elseif (strlen($title) < 3) {
            $errors[] = 'Gallery title must be at least 3 characters long';
        }
        
        if (empty($description)) {
            $errors[] = 'Gallery description is required';
        }
        
        if (empty($gallery_type) || !in_array($gallery_type, ['virtual', 'physical'])) {
            $errors[] = 'Please select a valid gallery type';
        }
        
        // Validate start date - required and must be in the future
        if (empty($start_date)) {
            $errors[] = 'Start date is required';
        } else {
            $start_timestamp = strtotime($start_date);
            $current_timestamp = time();
            
            if ($start_timestamp === false) {
                $errors[] = 'Invalid start date format';
            } elseif ($start_timestamp <= $current_timestamp) {
                $errors[] = 'Start date must be in the future';
            }
        }
        
        if ($gallery_type === 'physical') {
            if (empty($address)) {
                $errors[] = 'Address is required for physical galleries';
            }
            
            if (empty($city)) {
                $errors[] = 'City is required for physical galleries';
            }
            
            if (empty($phone)) {
                $errors[] = 'Phone number is required for physical galleries';
            } elseif (!validatePhone($phone)) {
                $errors[] = 'Please enter a valid phone number';
            }
        } else if ($gallery_type === 'virtual') {
            if (empty($duration)) {
                $errors[] = 'Duration is required for virtual galleries';
            } elseif (!is_numeric($duration) || $duration < 1) {
                $errors[] = 'Duration must be a valid positive number in minutes';
            } elseif ($duration > 120) {
                $errors[] = 'Duration cannot exceed 2 hours (120 minutes)';
            }
        }
        
        if (!empty($price)) {
            if (!is_numeric($price) || $price < 0) {
                $errors[] = 'Price must be a valid positive number';
            }
        }
        
        // If there are validation errors, return them
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
            exit;
        }
        
        // Store data as plain text (no encryption)
        $plain_address = !empty($address) ? $address : null;
        $plain_phone = !empty($phone) ? $phone : null;
        
        // Set is_active based on gallery type
        $is_active = ($gallery_type === 'virtual') ? 1 : 0;
        
        // Convert empty strings to appropriate values for database
        $price = empty($price) ? null : floatval($price);
        
        // Handle duration - set to 0 for physical galleries, actual value for virtual
        if ($gallery_type === 'virtual') {
            $duration = !empty($duration) ? intval($duration) : 7; // Default 7 days for virtual
        } else {
            $duration = 0; // Set to 0 for physical galleries
        }
        
        // Handle image uploads
        $img = null;
        $uploaded_images = [];
        
        // Check if primary image is uploaded
        if (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = handleFileUpload($_FILES['primary_image']);
            if ($upload_result['success']) {
                $img = $upload_result['path'];
            } else {
                echo json_encode(['success' => false, 'message' => 'Primary image upload failed: ' . $upload_result['message']]);
                exit;
            }
        }
        
        // Handle multiple gallery images
        if (isset($_FILES['gallery_images']) && is_array($_FILES['gallery_images']['name'])) {
            for ($i = 0; $i < count($_FILES['gallery_images']['name']); $i++) {
                if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['gallery_images']['name'][$i],
                        'type' => $_FILES['gallery_images']['type'][$i],
                        'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
                        'size' => $_FILES['gallery_images']['size'][$i],
                        'error' => $_FILES['gallery_images']['error'][$i]
                    ];
                    
                    $upload_result = handleFileUpload($file);
                    if ($upload_result['success']) {
                        $uploaded_images[] = [
                            'path' => $upload_result['path'],
                            'is_primary' => ($i === 0 && $img === null) ? 1 : 0 // First image is primary if no specific primary image
                        ];
                    }
                }
            }
        }
        
        // If no primary image was specifically uploaded, use the first gallery image
        if ($img === null && !empty($uploaded_images)) {
            $img = $uploaded_images[0]['path'];
            $uploaded_images[0]['is_primary'] = 1;
        }
        
        // Prepare SQL statement - use existing 'img' field instead of 'primary_image'
        // Tags will be handled separately using normalized tables
        
        // Check if galleries table has a start_date column
        $check_start_date_column = "SHOW COLUMNS FROM galleries LIKE 'start_date'";
        $start_date_result = $conn->query($check_start_date_column);
        $has_start_date = $start_date_result && $start_date_result->num_rows > 0;
        
        if ($has_start_date) {
            $sql = "INSERT INTO galleries (artist_id, title, description, gallery_type, price, address, city, phone, duration, is_active, img, start_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        } else {
            $sql = "INSERT INTO galleries (artist_id, title, description, gallery_type, price, address, city, phone, duration, is_active, img) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        }
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $conn->error);
        }
        
        // Bind parameters based on which columns exist
        if ($has_start_date) {
            // With start_date column
            $stmt->bind_param(
                "isssdsssisss",
                $artist_id,
                $title,
                $description,
                $gallery_type,
                $price,
                $plain_address,
                $city,
                $plain_phone,
                $duration,
                $is_active,
                $img,
                $start_date
            );
        } else {
            // Without start_date column
            $stmt->bind_param(
                "isssdssssis",
                $artist_id,
                $title,
                $description,
                $gallery_type,
                $price,
                $plain_address,
                $city,
                $plain_phone,
                $duration,
                $is_active,
                $img
            );
        }
        
        // Execute the statement
        if ($stmt->execute()) {
            $gallery_id = $conn->insert_id;
            
            // Insert gallery photos into gallery_photos table
            $photo_sql = "INSERT INTO gallery_photos (gallery_id, image_path, is_primary) VALUES (?, ?, ?)";
            $photo_stmt = $conn->prepare($photo_sql);
            
            if ($photo_stmt) {
                // First, add the primary image to gallery_photos if it exists
                if ($img !== null) {
                    $is_primary = 1;
                    $photo_stmt->bind_param("isi", $gallery_id, $img, $is_primary); // Primary image marked as is_primary = 1
                    $photo_stmt->execute();
                }
                
                // Then add all additional gallery images
                if (!empty($uploaded_images)) {
                    $is_not_primary = 0;
                    foreach ($uploaded_images as $image) {
                        // Only add if it's not the same as the primary image
                        if ($image['path'] !== $img) {
                            $photo_stmt->bind_param("isi", $gallery_id, $image['path'], $is_not_primary); // Additional images marked as is_primary = 0
                            $photo_stmt->execute();
                        }
                    }
                }
                
                $photo_stmt->close();
            } else {
                error_log("Failed to prepare gallery photos statement: " . $conn->error);
            }
            
            // Handle tags for the gallery using normalized tables
            $tag_result = ['success' => true, 'message' => 'No tags to process'];
            if (!empty($tags)) {
                $tag_result = handleGalleryTags($conn, $gallery_id, $tags);
                if (!$tag_result['success']) {
                    error_log("Tag processing warning: " . $tag_result['message']);
                    // Don't fail the entire operation for tag errors, just log them
                }
            }
            
            // Return success message based on gallery type
            if ($gallery_type === 'virtual') {
                $response = [
                    'success' => true, 
                    'message' => 'Gallery published successfully!',
                    'gallery_id' => $gallery_id
                ];
                
                if (!empty($tags) && $tag_result['success']) {
                    $response['tags_processed'] = $tag_result['tag_count'] ?? 0;
                }
                
                echo json_encode($response);
            } else {
                $response = [
                    'success' => true, 
                    'message' => 'Gallery submitted successfully! An admin will contact you soon.',
                    'gallery_id' => $gallery_id
                ];
                
                if (!empty($tags) && $tag_result['success']) {
                    $response['tags_processed'] = $tag_result['tag_count'] ?? 0;
                }
                
                echo json_encode($response);
            }
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Gallery API Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error adding gallery: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
/*
-- Artwork Marketplace Database Schema

-- Users table (combined with artist information)
CREATE TABLE users (
user_id INT AUTO_INCREMENT PRIMARY KEY,
email VARCHAR(255) UNIQUE NOT NULL,
password VARCHAR(255) NOT NULL,
first_name VARCHAR(100) NOT NULL,
last_name VARCHAR(100) NOT NULL,
phone VARCHAR(20),
user_type ENUM('artist', 'buyer', 'admin') DEFAULT 'buyer',
profile_picture VARCHAR(500),
bio TEXT,
is_active TINYINT(1) DEFAULT 1,
-- Artist-specific fields (nullable)
art_specialty VARCHAR(255) NULL,
years_of_experience INT NULL,
achievements TEXT NULL,
national_id VARCHAR(50) NULL,
Img VARCHAR(50) NULL,
artist_bio TEXT NULL,
location VARCHAR(255) NULL,
education TEXT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_profile_photo (
    user_id INT NOT NULL,
    photo_filename VARCHAR(255) NOT NULL,

    -- Foreign key constraint
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Course table
CREATE TABLE courses (
course_id INT AUTO_INCREMENT PRIMARY KEY,
title VARCHAR(255) NOT NULL,
rate DECIMAL(3,2) DEFAULT 0.00 COMMENT 'Course rating out of 5',
artist_id INT NOT NULL,
duration_date INT NOT NULL COMMENT 'Duration in months',
description TEXT,
requirement TEXT,
difficulty ENUM('beginner', 'intermediate', 'advanced') NOT NULL,
course_type ENUM('online', 'offline', 'hybrid') NOT NULL,
price DECIMAL(10,2) NOT NULL,
thumbnail VARCHAR(500),
is_published TINYINT(1) DEFAULT 0,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (artist_id) REFERENCES users(user_id) ON DELETE CASCADE
);




-- Course enrollment table
CREATE TABLE course_enrollments (
id INT AUTO_INCREMENT PRIMARY KEY,
course_id INT NOT NULL,
user_id INT NOT NULL,
is_payed TINYINT(1) DEFAULT 0,
is_active TINYINT(1) DEFAULT 1,
enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
UNIQUE KEY unique_enrollment (course_id, user_id)
);

CREATE TABLE galleries (
    gallery_id INT AUTO_INCREMENT PRIMARY KEY,
    artist_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    gallery_type ENUM('virtual', 'physical') NOT NULL,
    
    -- Virtual gallery fields
    price DECIMAL(10,2) NULL,              -- Price for virtual access
    
    -- Physical gallery fields  
    address TEXT NULL,
    city VARCHAR(100) NULL,
    phone VARCHAR(20) NULL,
    
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    duration INT NOT NULL, --in minutes
    primary_image VARCHAR(500) NULL,       -- Main gallery image
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artist_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Artwork table
CREATE TABLE artworks (
artwork_id INT AUTO_INCREMENT PRIMARY KEY,
artist_id INT NOT NULL,
title VARCHAR(255) NOT NULL,
description TEXT,
price DECIMAL(10,2) NOT NULL,
dimensions VARCHAR(100),
year YEAR,
material VARCHAR(255),
artwork_image VARCHAR(500),
type ENUM('painting', 'sculpture', 'photography', 'digital', 'mixed_media', 'other') NOT NULL,
is_available TINYINT(1) DEFAULT 1,
on_auction TINYINT(1) DEFAULT 0,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (artist_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Update gallery_items to reference artworks
ALTER TABLE gallery_items
ADD FOREIGN KEY (artwork_id) REFERENCES artworks(artwork_id) ON DELETE CASCADE;

-- Orders table
CREATE TABLE orders (
id INT AUTO_INCREMENT PRIMARY KEY,
buyer_id INT NOT NULL,
total_amount DECIMAL(10,2) NOT NULL,
status ENUM('pending', 'paid', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
shipping_address TEXT,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (buyer_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE order_items (
id INT AUTO_INCREMENT PRIMARY KEY,
order_id INT NOT NULL,
artwork_id INT NOT NULL,
price DECIMAL(10,2) NOT NULL,
quantity INT DEFAULT 1,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
FOREIGN KEY (artwork_id) REFERENCES artworks(artwork_id) ON DELETE CASCADE
);

-- Artist reviews table
CREATE TABLE artist_reviews (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
artist_id INT NOT NULL,
artwork_id INT,
rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
feedback TEXT,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
FOREIGN KEY (artist_id) REFERENCES users(user_id) ON DELETE CASCADE,
FOREIGN KEY (artwork_id) REFERENCES artworks(artwork_id) ON DELETE SET NULL
);

-- Subscribers table (for artist subscription plans)
CREATE TABLE subscribers (
id INT AUTO_INCREMENT PRIMARY KEY,
artist_id INT NOT NULL,
plan ENUM('basic', 'premium', 'pro') NOT NULL,
duration INT NOT NULL COMMENT 'Duration in months',
start_date DATE NOT NULL,
end_date DATE NOT NULL,
is_active TINYINT(1) DEFAULT 1,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (artist_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Exam table
CREATE TABLE exams (
exam_id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
need_doctor TINYINT(1) DEFAULT 0,
draw_img VARCHAR(500),
exam_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
results TEXT,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Sessions table (for user sessions, course sessions, or gallery sessions)
CREATE TABLE sessions (
session_id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
session_type ENUM('user_login', 'course', 'gallery_visit', 'exam') NOT NULL,
reference_id INT COMMENT 'ID of course, gallery, or exam depending on session_type',
start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
end_time TIMESTAMP NULL,
duration INT COMMENT 'Session duration in minutes',
ip_address VARCHAR(45),
user_agent TEXT,
is_active TINYINT(1) DEFAULT 1,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Auction table
CREATE TABLE auctions (
id INT AUTO_INCREMENT PRIMARY KEY,
product_id INT NOT NULL,
artist_id INT NOT NULL,
starting_bid DECIMAL(10,2) NOT NULL,
current_bid DECIMAL(10,2) DEFAULT 0.00,
start_time DATETIME NOT NULL,
end_time DATETIME NOT NULL,
status ENUM('active', 'ended', 'cancelled') DEFAULT 'active',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (product_id) REFERENCES artworks(artwork_id) ON DELETE CASCADE,
FOREIGN KEY (artist_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Auction bids table (to track all bids placed on auctions)
CREATE TABLE auction_bids (
id INT AUTO_INCREMENT PRIMARY KEY,
auction_id INT NOT NULL,
user_id INT NOT NULL,
bid_amount DECIMAL(10,2) NOT NULL,
bid_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
is_winning_bid TINYINT(1) DEFAULT 0,
FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE user_login_sessions (
session_id VARCHAR(128) PRIMARY KEY, -- Unique session token
user_id INT NOT NULL, -- Reference to logged-in user
login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- When user logged in
expires_at TIMESTAMP NOT NULL, -- Session expiration time
is_active TINYINT(1) DEFAULT 1, -- Session active status
logout_time TIMESTAMP NULL, -- When user logged out
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE cart (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL, -- Links to the user
artwork_id INT NOT NULL, -- Links to the artwork
quantity INT DEFAULT 1, -- Quantity (usually 1 for unique artworks)
added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- When added to cart
is_active TINYINT(1) DEFAULT 1, -- Active/inactive status
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
FOREIGN KEY (artwork_id) REFERENCES artworks(artwork_id) ON DELETE CASCADE,
UNIQUE KEY unique_cart_item (user_id, artwork_id) -- Prevents duplicates
);
CREATE TABLE artwork_photos (
    photo_id INT AUTO_INCREMENT PRIMARY KEY,
    artwork_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artwork_id) REFERENCES artworks(artwork_id) ON DELETE CASCADE
);

CREATE TABLE gallery_photos (
    photo_id INT(11) NOT NULL AUTO_INCREMENT,
    gallery_id INT(11) NOT NULL,
    image_path VARCHAR(500) COLLATE utf8mb4_general_ci NOT NULL,
    is_primary TINYINT(1) NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
    PRIMARY KEY (photo_id),
    FOREIGN KEY (gallery_id) REFERENCES gallery(gallery_id) ON DELETE CASCADE
);
*/

?>

