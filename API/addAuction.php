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

// File upload function for auction artwork images
function uploadAuctionImage($file, $artistId, $artworkId, $imageIndex, $uploadDir = null) {
    try {
        // Set default upload directory with correct path
        if ($uploadDir === null) {
            $uploadDir = dirname(__DIR__) . '/uploads/artworks/';
        }
        
        // Create upload directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'message' => 'Failed to create upload directory: ' . $uploadDir];
            }
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
        
        // Generate unique filename
        $fileHash = md5_file($file['tmp_name']);
        $timestamp = time();
        $filename = "artist_{$artistId}_artwork_{$artworkId}_img_{$imageIndex}_{$timestamp}_{$fileHash}.{$fileExtension}";
        $targetPath = $uploadDir . $filename;
        
        // Move uploaded file - use copy for testing if move_uploaded_file fails
        $moveSuccess = false;
        if (is_uploaded_file($file['tmp_name'])) {
            // Real uploaded file
            $moveSuccess = move_uploaded_file($file['tmp_name'], $targetPath);
        } else {
            // For testing purposes - use copy instead
            $moveSuccess = copy($file['tmp_name'], $targetPath);
        }
        
        if (!$moveSuccess) {
            return ['success' => false, 'message' => 'Failed to move uploaded file.'];
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
function cleanupUploadedFiles($filenames, $uploadDir = '../uploads/artworks/') {
    foreach ($filenames as $filename) {
        $filePath = $uploadDir . $filename;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}

try {
    // Start transaction
    $db->autocommit(false);
    
    // Get form data
    $postData = $_POST;
    
    // Determine if this is auction or regular artwork based on field names
    $isAuction = isset($postData['start_date']) && isset($postData['end_date']);
    
    // Normalize field names - handle both auction and regular artwork formats
    if (!$isAuction) {
        // For regular artwork, map to expected field names
        if (isset($postData['title']) && !isset($postData['artwork_title'])) {
            $postData['artwork_title'] = $postData['title'];
        }
        if (isset($postData['price']) && !isset($postData['starting_bid'])) {
            $postData['starting_bid'] = $postData['price'];
        }
        if (isset($postData['style']) && !isset($postData['art_style'])) {
            $postData['art_style'] = $postData['style'];
        }
    }
    
    // Validate required fields
    $errors = [];
    if ($isAuction) {
        // Auction required fields
        $requiredFields = [
            'artwork_title' => 'Artwork Title',
            'starting_bid' => 'Starting Bid',
            'artwork_type' => 'Artwork Type',
            'category' => 'Category',
            'art_style' => 'Art Style',
            'dimensions' => 'Dimensions',
            'year' => 'Year',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'description' => 'Description'
        ];
    } else {
        // Regular artwork required fields
        $requiredFields = [
            'artwork_title' => 'Artwork Title',
            'starting_bid' => 'Price',
            'category' => 'Category',
            'art_style' => 'Art Style',
            'description' => 'Description'
        ];
    }
    
    foreach ($requiredFields as $field => $label) {
        if (empty($postData[$field])) {
            $errors[] = "$label is required";
        }
    }
    
    // Validate numeric fields
    if (!empty($postData['starting_bid'])) {
        if (!is_numeric($postData['starting_bid']) || $postData['starting_bid'] <= 0) {
            $errors[] = 'Starting bid must be a positive number';
        }
    }
    
    // Validate dimensions format (basic validation - more detailed validation on server)
    if (!empty($postData['dimensions'])) {
        $dimensions = trim($postData['dimensions']);
        if (strlen($dimensions) < 3) {
            $errors[] = 'Dimensions must be at least 3 characters long';
        } elseif (strlen($dimensions) > 100) {
            $errors[] = 'Dimensions must be less than 100 characters long';
        }
    }
    
    if (!empty($postData['year'])) {
        $currentYear = date('Y');
        if (!is_numeric($postData['year']) || $postData['year'] < 1800 || $postData['year'] > $currentYear) {
            $errors[] = "Year must be between 1800 and $currentYear";
        }
    }
    
    // Validate artwork type (free text input)
    if (!empty($postData['artwork_type'])) {
        $artwork_type = trim($postData['artwork_type']);
        if (strlen($artwork_type) < 2) {
            $errors[] = 'Artwork type must be at least 2 characters long';
        } elseif (strlen($artwork_type) > 50) {
            $errors[] = 'Artwork type must be less than 50 characters';
        }
    }
    
    // Validate category
    if (!empty($postData['category'])) {
        $validCategories = ['Portraits', 'Landscapes', 'Abstract', 'Photography', 'Mixed Media'];
        if (!in_array($postData['category'], $validCategories)) {
            $errors[] = 'Invalid category selected';
        }
    }
    
    // Validate art style length if provided
    if (!empty($postData['art_style']) && strlen($postData['art_style']) > 100) {
        $errors[] = 'Art style must be less than 100 characters';
    }
    
    // Validate description length
    if (!empty($postData['description'])) {
        if (strlen($postData['description']) < 10) {
            $errors[] = 'Description must be at least 10 characters long';
        } elseif (strlen($postData['description']) > 1000) {
            $errors[] = 'Description must be less than 1000 characters';
        }
    }
    
    // Validate artwork title
    if (!empty($postData['artwork_title'])) {
        if (strlen($postData['artwork_title']) < 3) {
            $errors[] = 'Artwork title must be at least 3 characters long';
        } elseif (strlen($postData['artwork_title']) > 200) {
            $errors[] = 'Artwork title must be less than 200 characters';
        } elseif (preg_match('/^\d+$/', $postData['artwork_title'])) {
            $errors[] = 'Artwork title should contain words, not just numbers';
        }
    }
    
    // Validate starting bid range
    if (!empty($postData['starting_bid'])) {
        $bid = floatval($postData['starting_bid']);
        if ($bid > 5000000) {
            $errors[] = 'Starting bid cannot exceed 5,000,000 EGP';
        }
    }
    
    // Validate dates (only for auctions)
    if ($isAuction) {
        if (!empty($postData['start_date'])) {
            $startDate = DateTime::createFromFormat('Y-m-d\TH:i', $postData['start_date']);
            if (!$startDate) {
                $errors[] = 'Invalid start date format';
            } else {
                $now = new DateTime();
                if ($startDate <= $now) {
                    $errors[] = 'Start date must be in the future';
                }
            }
        }
        
        if (!empty($postData['end_date'])) {
            $endDate = DateTime::createFromFormat('Y-m-d\TH:i', $postData['end_date']);
            if (!$endDate) {
                $errors[] = 'Invalid end date format';
            } else if (isset($startDate) && $endDate <= $startDate) {
                $errors[] = 'End date must be after start date';
            }
        }
        
        // Validate images are provided for auctions
        $hasImages = false;
        
        // Check for auction_images array
        if (isset($_FILES['auction_images']) && !empty($_FILES['auction_images']['name'][0])) {
            $hasImages = true;
        }
        // Also check for artwork_images as fallback
        elseif (isset($_FILES['artwork_images']) && !empty($_FILES['artwork_images']['name'][0])) {
            $hasImages = true;
        }
        // Check for single image upload
        elseif (isset($_FILES['artwork_image']) && $_FILES['artwork_image']['error'] === UPLOAD_ERR_OK) {
            $hasImages = true;
        }
        
        if (!$hasImages) {
            $errors[] = 'At least one image is required for auction';
        }
    } else {
        // For regular artwork, check for artwork_image
        if (!isset($_FILES['artwork_image']) || $_FILES['artwork_image']['error'] !== UPLOAD_ERR_OK) {
            // Image is optional for regular artwork
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
    
    // Get artist ID from authentication (session or cookie)
    $artist_id = null;
    
    // First check for session-based authentication
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'artist') {
        $artist_id = (int)$_SESSION['user_id'];
    }
    // Fallback to cookie authentication
    elseif (isset($_COOKIE['user_login'])) {
        $cookieValue = $_COOKIE['user_login'];
        $parts = explode('_', $cookieValue, 2);
        
        if (count($parts) === 2) {
            $userId = (int)$parts[0];
            
            // Validate user exists and is an artist
            $stmt = $db->prepare("SELECT user_id FROM users WHERE user_id = ? AND is_active = 1 AND user_type = 'artist'");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $artist_id = $userId;
                }
                $stmt->close();
            }
        }
    }
    
    if (!$artist_id) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required. Please log in to add artwork.'
        ]);
        exit;
    }
    
    // Extract validated data
    $artwork_title = $postData['artwork_title'];
    $starting_bid = floatval($postData['starting_bid']);
    
    // Handle required fields
    $art_style = $postData['art_style']; // Now required, no default value
    $dimensions = !empty($postData['dimensions']) ? trim($postData['dimensions']) : null;
    $year = !empty($postData['year']) ? intval($postData['year']) : date('Y');
    $description = $postData['description'];
    $category = $postData['category'] ?? 'painting';
    
    // Use dimensions directly (no need to construct from width/height/depth)
    
    // Only process auction-specific fields if this is an auction
    if ($isAuction) {
        $start_date = $postData['start_date'];
        $end_date = $postData['end_date'];
        
        // Format datetime strings for database
        $start_datetime = date('Y-m-d H:i:s', strtotime($start_date));
        $end_datetime = date('Y-m-d H:i:s', strtotime($end_date));
    }
    
    // Handle image uploads first to get the primary image path
    $primaryImagePath = null;
    $uploaded_images = [];
    
    // Check if primary image is uploaded
    if (isset($_FILES['primary_image']) && $_FILES['primary_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadAuctionImage($_FILES['primary_image'], $artist_id, 0, 0);
        if ($upload_result['success']) {
            $primaryImagePath = $upload_result['filename'];
        } else {
            throw new Exception('Primary image upload failed: ' . $upload_result['message']);
        }
    }
    
    // Handle multiple images (auction_images[] for auctions, artwork_images[] for artworks)
    $imageField = $isAuction ? 'auction_images' : 'artwork_images';
    if (isset($_FILES[$imageField]) && is_array($_FILES[$imageField]['name'])) {
        for ($i = 0; $i < count($_FILES[$imageField]['name']); $i++) {
            if ($_FILES[$imageField]['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES[$imageField]['name'][$i],
                    'type' => $_FILES[$imageField]['type'][$i],
                    'tmp_name' => $_FILES[$imageField]['tmp_name'][$i],
                    'size' => $_FILES[$imageField]['size'][$i],
                    'error' => $_FILES[$imageField]['error'][$i]
                ];
                
                $upload_result = uploadAuctionImage($file, $artist_id, 0, $i + 1);
                if ($upload_result['success']) {
                    $uploaded_images[] = [
                        'path' => $upload_result['filename'],
                        'is_primary' => 0 // Additional images are not primary
                    ];
                }
            }
        }
    }
    
    // If no primary image was specifically uploaded, use the first additional image
    if ($primaryImagePath === null && !empty($uploaded_images)) {
        $primaryImagePath = $uploaded_images[0]['path'];
        $uploaded_images[0]['is_primary'] = 1;
    }
    
    // Use user-provided artwork type (free text input)
    $artworkType = !empty($postData['artwork_type']) ? trim($postData['artwork_type']) : 'Painting';
    
    // Insert artwork into artworks table
    if ($isAuction) {
        // For auctions, set on_auction = 1
        $artworkSql = "INSERT INTO artworks (
            artist_id, title, description, price, dimensions, year, 
            artwork_image, type, category, style, is_available, on_auction
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1)";
    } else {
        // For regular artwork, set on_auction = 0
        $artworkSql = "INSERT INTO artworks (
            artist_id, title, description, price, dimensions, year, 
            artwork_image, type, category, style, is_available, on_auction
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0)";
    }
    
    $artworkStmt = $db->prepare($artworkSql);
    if (!$artworkStmt) {
        throw new Exception("Failed to prepare artwork statement: " . $db->error);
    }
    
    // Handle dimensions - use null if empty
    $dimensionsForDb = !empty($dimensions) ? $dimensions : null;
    
    $artworkStmt->bind_param(
        "issdsissss",
        $artist_id,
        $artwork_title,
        $description,
        $starting_bid, // Use starting bid as price
        $dimensionsForDb,
        $year,
        $primaryImagePath, // Add primary image path
        $artworkType,
        $category,
        $art_style
    );
    
    if (!$artworkStmt->execute()) {
        throw new Exception("Failed to insert artwork: " . $artworkStmt->error);
    }
    
    $artwork_id = $db->insert_id;
    $artworkStmt->close();
    
    // Only create auction if this is an auction
    if ($isAuction) {
        // Insert auction into auctions table
        $auctionSql = "INSERT INTO auctions (
            product_id, artist_id, starting_bid, current_bid, 
            start_time, end_time, status
        ) VALUES (?, ?, ?, ?, ?, ?, 'active')";
        
        $auctionStmt = $db->prepare($auctionSql);
        if (!$auctionStmt) {
            throw new Exception("Failed to prepare auction statement: " . $db->error);
        }
        
        $auctionStmt->bind_param(
            "iiddss",
            $artwork_id,    // This references artwork_id from artworks table
            $artist_id,     // Include artist_id as required by the table
            $starting_bid,
            $starting_bid,  // current_bid starts as starting_bid
            $start_datetime,
            $end_datetime
        );
        
        if (!$auctionStmt->execute()) {
            throw new Exception("Failed to insert auction: " . $auctionStmt->error);
        }
        
        $auction_id = $db->insert_id;
        $auctionStmt->close();
    } else {
        $auction_id = null; // No auction created for regular artwork
    }
    
    // Handle image uploads and store in artwork_photos table
    $uploadErrors = [];
    $totalUploadedImages = [];
    
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
    
    // For auctions, at least one image is required
    if ($isAuction && empty($totalUploadedImages)) {
        throw new Exception("No images were successfully uploaded for auction.");
    }
    
    // Commit transaction
    $db->commit();
    
    // Success response
    http_response_code(200);
    if ($isAuction) {
        echo json_encode([
            'success' => true,
            'message' => 'Auction created successfully!',
            'data' => [
                'artwork_id' => $artwork_id,
                'auction_id' => $auction_id,
                'artwork_title' => $artwork_title,
                'starting_bid' => $starting_bid,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'images_uploaded' => count($totalUploadedImages),
                'image_files' => $totalUploadedImages
            ],
            'warnings' => !empty($uploadErrors) ? $uploadErrors : null
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Artwork added successfully!',
            'data' => [
                'artwork_id' => $artwork_id,
                'artwork_title' => $artwork_title,
                'price' => $starting_bid,
                'category' => $category,
                'images_uploaded' => count($totalUploadedImages),
                'image_files' => $totalUploadedImages
            ],
            'warnings' => !empty($uploadErrors) ? $uploadErrors : null
        ]);
    }
    
} catch (Exception $e) {
    // Rollback transaction
    $db->rollback();
    
    // Clean up any uploaded files
    if (!empty($totalUploadedImages)) {
        cleanupUploadedFiles($totalUploadedImages);
    }
    
    error_log("Auction creation error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create auction: ' . $e->getMessage(),
        'error_code' => 'AUCTION_CREATION_FAILED'
    ]);
} finally {
    // Reset autocommit
    $db->autocommit(true);
}
?>
