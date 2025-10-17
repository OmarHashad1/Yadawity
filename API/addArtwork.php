<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

require_once "db.php";

// Set proper headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only POST requests are accepted.',
        'error_code' => 'METHOD_NOT_ALLOWED'
    ]);
    exit;
}

// File upload function for artwork images
function uploadArtworkImage($file, $artistId, $artworkId, $imageIndex, $uploadDir = null) {
    try {
                // Set default upload directory with correct path
        if ($uploadDir === null) {
            // Use the current directory structure and create relative path
            $uploadDir = dirname(__DIR__) . '/uploads/artworks/';
        }
        
        // Create upload directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'message' => 'Failed to create upload directory: ' . $uploadDir];
            }
        }
        
        // Check if directory is writable
        if (!is_writable($uploadDir)) {
            return ['success' => false, 'message' => 'Upload directory is not writable: ' . $uploadDir . '. Please check directory permissions.'];
        }
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        // Validate file extension only
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            return ['success' => false, 'message' => 'Invalid file extension. Only JPG, JPEG, PNG, GIF, and WebP are allowed.'];
        }
        
        // Validate image signature/magic bytes
        $signatureValidation = validateImageSignature($file['tmp_name'], $fileExtension);
        if (!$signatureValidation['isValid']) {
            return ['success' => false, 'message' => $signatureValidation['message']];
        }
        
        // Check if temp file exists and is readable
        if (!file_exists($file['tmp_name']) || !is_readable($file['tmp_name'])) {
            return ['success' => false, 'message' => 'Temporary file not found or not readable.'];
        }
        
        // Generate unique filename
        $fileHash = md5_file($file['tmp_name']);
        $timestamp = time();
        $filename = "artist_{$artistId}_artwork_{$artworkId}_img_{$imageIndex}_{$timestamp}_{$fileHash}.{$fileExtension}";
        $targetPath = $uploadDir . $filename;
        
        // Ensure the target directory exists and is writable before attempting upload
        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Move uploaded file with better error handling
        $moveSuccess = false;
        if (is_uploaded_file($file['tmp_name'])) {
            // Real uploaded file
            $moveSuccess = move_uploaded_file($file['tmp_name'], $targetPath);
            if (!$moveSuccess) {
                $error = error_get_last();
                return ['success' => false, 'message' => 'Failed to move uploaded file. Error: ' . ($error['message'] ?? 'Unknown error')];
            }
        } else {
            // For testing purposes - use copy instead
            $moveSuccess = copy($file['tmp_name'], $targetPath);
            if (!$moveSuccess) {
                return ['success' => false, 'message' => 'Failed to copy file during testing.'];
            }
        }
        
        // Set proper permissions on the uploaded file
        if ($moveSuccess && file_exists($targetPath)) {
            chmod($targetPath, 0644);
        }
        
        // Verify the file was uploaded successfully
        if (!file_exists($targetPath)) {
            return ['success' => false, 'message' => 'File upload verification failed.'];
        }
        
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $targetPath,
            'size' => $file['size']
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Upload error: ' . $e->getMessage()];
    }
}

// Cleanup function for uploaded files in case of error
function cleanupUploadedFiles($filenames, $uploadDir = null) {
    if ($uploadDir === null) {
        $uploadDir = dirname(__DIR__) . '/uploads/artworks/';
    }
    foreach ($filenames as $filename) {
        $filePath = $uploadDir . $filename;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}

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

try {
    // Start transaction
    $db->autocommit(false);
    
    // Get form data
    $postData = $_POST;
    
    // This API is only for regular artworks, not auctions
    // Auctions should use addAuction.php
    
    // Validate required fields for regular artwork
    $errors = [];
    $requiredFields = [
        'title' => 'Artwork Title',
        'price' => 'Price',
        'quantity' => 'Stock Quantity',
        'type' => 'Artwork Type',
        'category' => 'Category',
        'style' => 'Art Style',
        'dimensions' => 'Dimensions',
        'description' => 'Description'
    ];
    
    foreach ($requiredFields as $field => $label) {
        if (empty($postData[$field])) {
            $errors[] = "$label is required";
        }
    }
    
    // Server-side validation for title
    if (!empty($postData['title'])) {
        $title = trim($postData['title']);
        if (strlen($title) < 3) {
            $errors[] = 'Title should be at least 3 characters long';
        }
        if (preg_match('/^\d+$/', $title)) {
            $errors[] = 'Title should contain words, not just numbers';
        }
    }
    
    // Server-side validation for price
    if (!empty($postData['price'])) {
        $price = floatval($postData['price']);
        if (!is_numeric($postData['price']) || $price <= 0) {
            $errors[] = 'Price must be a positive number';
        }
        if ($price > 3000000) {
            $errors[] = 'Price cannot exceed 3,000,000 EGP';
        }
    }
    
    // Server-side validation for quantity
    if (!empty($postData['quantity'])) {
        $quantity = intval($postData['quantity']);
        if (!is_numeric($postData['quantity']) || $quantity < 1) {
            $errors[] = 'Quantity must be at least 1';
        }
        if ($quantity > 1000) {
            $errors[] = 'Maximum quantity is 1000';
        }
    }
    
    // Server-side validation for type (free text input)
    if (!empty($postData['type'])) {
        $type = trim($postData['type']);
        if (strlen($type) < 2) {
            $errors[] = 'Artwork type must be at least 2 characters long';
        } elseif (strlen($type) > 50) {
            $errors[] = 'Artwork type must be less than 50 characters';
        }
    }
    
    // Server-side validation for category (enum validation)
    if (!empty($postData['category'])) {
        $allowedCategories = ['Portraits', 'Landscapes', 'Abstract', 'Photography', 'Mixed Media'];
        if (!in_array($postData['category'], $allowedCategories)) {
            $errors[] = 'Invalid category selected';
        }
    }
    
    // Server-side validation for style (optional field)
    if (!empty($postData['style'])) {
        $style = trim($postData['style']);
        if (strlen($style) > 255) {
            $errors[] = 'Style cannot exceed 255 characters';
        }
    }
    
    // Server-side validation for dimensions
    if (!empty($postData['dimensions'])) {
        $dimensions = trim($postData['dimensions']);
        // Check if dimensions contain numbers and letters with x or × (multiplication)
        if (!preg_match('/^\d+\s*[x×]\s*\d+(\s*[x×]\s*\d+)?\s*(cm|mm|in|inches?)?$/i', $dimensions)) {
            $errors[] = 'Dimensions must be in format like "50 x 70 cm" or "30 x 40 x 10 cm"';
        }
    }
    
    // Server-side validation for year
    if (!empty($postData['year'])) {
        $currentYear = date('Y');
        if (!is_numeric($postData['year']) || $postData['year'] < 1800 || $postData['year'] > $currentYear) {
            $errors[] = "Year must be between 1800 and $currentYear";
        }
    }
    
    // Server-side validation for description
    if (!empty($postData['description'])) {
        $description = trim($postData['description']);
        if (strlen($description) > 1000) {
            $errors[] = 'Description cannot exceed 1000 characters';
        }
    }
    
    // Return validation errors if any
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ]);
        exit;
    }
    
    // For regular artwork, images are optional
    // No specific image validation needed here

    // Start transaction
    $db->autocommit(false);
    
    // Get artist ID from authentication (session or cookie)
    $artist_id = null;
    $user = null;
    
    // First check for session-based authentication
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'artist') {
        $userId = (int)$_SESSION['user_id'];
        
        // Get user info from session
        $stmt = $db->prepare("SELECT user_id, email, first_name, user_type, is_active, is_verified FROM users WHERE user_id = ? AND is_active = 1 AND user_type = 'artist'");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $artist_id = $user['user_id'];
            }
            $stmt->close();
        }
    }
    
    // If session auth failed, try cookie authentication
    if (!$artist_id && isset($_COOKIE['user_login'])) {
        $cookieValue = $_COOKIE['user_login'];
        $parts = explode('_', $cookieValue, 2);
        
        if (count($parts) === 2) {
            $userId = (int)$parts[0];
            $cookieHash = $parts[1];
            
            // Get user info and validate authentication
            $stmt = $db->prepare("SELECT user_id, email, first_name, user_type, is_active, is_verified FROM users WHERE user_id = ? AND is_active = 1 AND user_type = 'artist'");
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
                            $artist_id = $user['user_id'];
                        }
                        $sessionStmt->close();
                    }
                }
                $stmt->close();
            }
        }
    }    // If neither authentication method worked
    if (!$artist_id) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required. Please log in to add artwork.',
            'debug_info' => [
                'session_user_id' => $_SESSION['user_id'] ?? 'not_set',
                'session_user_type' => $_SESSION['user_type'] ?? 'not_set',
                'cookie_exists' => isset($_COOKIE['user_login']) ? 'yes' : 'no'
            ]
        ]);
        exit;
    }
    
    // Extract validated data for regular artwork
    $artwork_title = $postData['title'];
    $price = floatval($postData['price']);
    $artworkType = $postData['type'];
    $category = $postData['category'];
    $style = trim($postData['style']); // Now required, no default null value
    $dimensions = $postData['dimensions'];
    $year = !empty($postData['year']) ? intval($postData['year']) : date('Y');
    $description = $postData['description'];
    $stock_quantity = intval($postData['quantity']);
    
    // Handle image uploads first to get the primary image path
    $primaryImagePath = null;
    $uploaded_images = [];
    $tempUploadedFiles = []; // Store temp file info for later processing
    
    // Check if primary image is uploaded
    if (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] === UPLOAD_ERR_OK) {
        // Store temp file info, don't upload yet
        $tempUploadedFiles['primary_image'] = $_FILES['primary_image'];
    }
    
    // Handle multiple images for artwork
    if (isset($_FILES['artwork_images']) && is_array($_FILES['artwork_images']['name'])) {
        $tempUploadedFiles['additional_images'] = [];
        for ($i = 0; $i < count($_FILES['artwork_images']['name']); $i++) {
            if ($_FILES['artwork_images']['error'][$i] === UPLOAD_ERR_OK) {
                $tempUploadedFiles['additional_images'][] = [
                    'name' => $_FILES['artwork_images']['name'][$i],
                    'type' => $_FILES['artwork_images']['type'][$i],
                    'tmp_name' => $_FILES['artwork_images']['tmp_name'][$i],
                    'size' => $_FILES['artwork_images']['size'][$i],
                    'error' => $_FILES['artwork_images']['error'][$i]
                ];
            }
        }
    }
    
    // Check if single artwork_image is uploaded (from regular artwork form)
    if (isset($_FILES['artwork_image']) && $_FILES['artwork_image']['error'] === UPLOAD_ERR_OK) {
        if (!isset($tempUploadedFiles['primary_image'])) {
            $tempUploadedFiles['primary_image'] = $_FILES['artwork_image'];
        }
    }
    
    // Insert artwork into artworks table (regular artwork only, no auction)
    $artworkSql = "INSERT INTO artworks (
        artist_id, title, description, price, dimensions, year, 
        artwork_image, type, category, style, is_available, on_auction, stock_quantity
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?)";
    
    $artworkStmt = $db->prepare($artworkSql);
    if (!$artworkStmt) {
        throw new Exception("Failed to prepare artwork statement: " . $db->error);
    }
    
    // Handle dimensions - use null if empty
    $dimensionsForDb = !empty($dimensions) ? $dimensions : null;
    $tempPrimaryImagePath = null; // Will be updated after processing images
    
    $artworkStmt->bind_param(
        "issdsissssi",
        $artist_id,
        $artwork_title,
        $description,
        $price,
        $dimensionsForDb,
        $year,
        $tempPrimaryImagePath, // Will be null initially
        $artworkType,
        $category,
        $style,
        $stock_quantity
    );
    
    if (!$artworkStmt->execute()) {
        throw new Exception("Failed to insert artwork: " . $artworkStmt->error);
    }
    
    $artwork_id = $db->insert_id;
    $artworkStmt->close();
    
    // Now process the uploaded images with the correct artwork_id
    $primaryImagePath = null;
    $uploaded_images = [];
    $totalUploadedImages = [];
    $uploadErrors = [];
    
    // Process primary image if uploaded
    if (isset($tempUploadedFiles['primary_image'])) {
        $upload_result = uploadArtworkImage($tempUploadedFiles['primary_image'], $artist_id, $artwork_id, 0);
        if ($upload_result['success']) {
            $primaryImagePath = $upload_result['filename'];
        } else {
            throw new Exception('Primary image upload failed: ' . $upload_result['message']);
        }
    }
    
    // Process additional images if uploaded
    if (isset($tempUploadedFiles['additional_images']) && !empty($tempUploadedFiles['additional_images'])) {
        foreach ($tempUploadedFiles['additional_images'] as $index => $file) {
            $upload_result = uploadArtworkImage($file, $artist_id, $artwork_id, $index + 1);
            if ($upload_result['success']) {
                $uploaded_images[] = [
                    'path' => $upload_result['filename'],
                    'is_primary' => 0 // Additional images are not primary
                ];
            } else {
                $uploadErrors[] = "Failed to upload image " . ($index + 1) . ": " . $upload_result['message'];
            }
        }
    }
    
    // If no primary image was uploaded but we have additional images, use the first one as primary
    if ($primaryImagePath === null && !empty($uploaded_images)) {
        $primaryImagePath = $uploaded_images[0]['path'];
        $uploaded_images[0]['is_primary'] = 1;
    }
    
    // Update the artwork record with the primary image path if we have one
    if ($primaryImagePath !== null) {
        $updateImageSql = "UPDATE artworks SET artwork_image = ? WHERE artwork_id = ?";
        $updateStmt = $db->prepare($updateImageSql);
        if ($updateStmt) {
            $updateStmt->bind_param("si", $primaryImagePath, $artwork_id);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }
    
    // Insert primary image into artwork_photos table if exists
    if ($primaryImagePath !== null) {
        $photoSql = "INSERT INTO artwork_photos (artwork_id, image_path, is_primary) VALUES (?, ?, 1)";
        $photoStmt = $db->prepare($photoSql);
        
        if ($photoStmt) {
            $photoStmt->bind_param("is", $artwork_id, $primaryImagePath);
            if (!$photoStmt->execute()) {
                throw new Exception("Failed to insert primary photo record: " . $photoStmt->error);
            }
            $photoStmt->close();
            $totalUploadedImages[] = $primaryImagePath;
        } else {
            throw new Exception("Failed to prepare primary photo statement: " . $db->error);
        }
    }
    
    // Insert additional images into artwork_photos table
    if (!empty($uploaded_images)) {
        $photoSql = "INSERT INTO artwork_photos (artwork_id, image_path, is_primary) VALUES (?, ?, ?)";
        $photoStmt = $db->prepare($photoSql);
        
        if ($photoStmt) {
            foreach ($uploaded_images as $image) {
                // Only add if it's not the same as the primary image
                if ($image['path'] !== $primaryImagePath) {
                    $photoStmt->bind_param("isi", $artwork_id, $image['path'], $image['is_primary']);
                    if (!$photoStmt->execute()) {
                        throw new Exception("Failed to insert photo record: " . $photoStmt->error);
                    }
                    $totalUploadedImages[] = $image['path'];
                }
            }
            $photoStmt->close();
        } else {
            throw new Exception("Failed to prepare photo statement: " . $db->error);
        }
    }
    
    // Commit transaction
    $db->commit();
    
    // Success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Artwork added successfully!',
        'data' => [
            'artwork_id' => $artwork_id,
            'artwork_title' => $artwork_title,
            'price' => $price,
            'type' => $artworkType,
            'category' => $category,
            'style' => $style,
            'images_uploaded' => count($totalUploadedImages),
            'image_files' => $totalUploadedImages
        ],
        'warnings' => !empty($uploadErrors) ? $uploadErrors : null
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $db->rollback();
    
    // Clean up any uploaded files
    if (!empty($totalUploadedImages)) {
        cleanupUploadedFiles($totalUploadedImages);
    }
    
    error_log("Artwork creation error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create artwork: ' . $e->getMessage(),
        'error_code' => 'ARTWORK_CREATION_FAILED'
    ]);
} finally {
    // Reset autocommit
    $db->autocommit(true);
}
?>
