<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "db.php";

// Set proper headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Allow both DELETE and POST methods
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only DELETE and POST requests are accepted.',
        'error_code' => 'METHOD_NOT_ALLOWED'
    ]);
    exit;
}

/**
 * Delete gallery image files from the filesystem
 */
function deleteGalleryImages($galleryId, $uploadDir = null) {
    try {
        // Set default upload directory
        if ($uploadDir === null) {
            $uploadDir = dirname(__DIR__) . '/uploads/galleries/';
        }
        
        $deletedFiles = [];
        $errors = [];
        
        // Look for files with the gallery ID pattern
        $pattern = $uploadDir . 'gallery_' . $galleryId . '_*';
        $files = glob($pattern);
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if (unlink($file)) {
                    $deletedFiles[] = basename($file);
                } else {
                    $errors[] = "Failed to delete file: " . basename($file);
                }
            }
        }
        
        return [
            'success' => true,
            'deleted_files' => $deletedFiles,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error deleting gallery images: ' . $e->getMessage()
        ];
    }
}

// Function to validate user cookie and get user ID
function validateUserCookie($conn) {
    if (!isset($_COOKIE['user_login'])) {
        return null;
    }
    
    $cookieValue = $_COOKIE['user_login'];
    
    // Extract user ID from cookie (format: user_id_hash)
    $parts = explode('_', $cookieValue, 2);
    if (count($parts) !== 2) {
        return null;
    }
    
    $user_id = (int)$parts[0];
    $provided_hash = $parts[1];
    
    if ($user_id <= 0) {
        return null;
    }
    
    // Get user data
    $stmt = $conn->prepare("SELECT email, is_active FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    $user = $result->fetch_assoc();
    
    // Check if user is active
    if (!$user['is_active']) {
        return null;
    }
    
    // Get all active login sessions for this user
    // Get all active login sessions for this user with session_id
    $session_stmt = $conn->prepare("
        SELECT session_id, login_time 
        FROM user_login_sessions 
        WHERE user_id = ? AND is_active = 1 
        ORDER BY login_time DESC
    ");
    $session_stmt->bind_param("i", $user_id);
    $session_stmt->execute();
    $session_result = $session_stmt->get_result();
    
    // Try to validate the hash against any active session using the same method as login
    while ($session = $session_result->fetch_assoc()) {
        $cookie_data = $session['session_id'] . '|' . $user['email'] . '|' . $user_id . '|' . $session['login_time'];
        $expected_hash = hash_hmac('sha256', $cookie_data, $session['session_id'] . '_' . $user['email']);
        if ($provided_hash === $expected_hash) {
            return $user_id; // Hash matches one of the active sessions
        }
    }
    
    // No matching hash found
    return null;
}

/**
}

/**
 * Validate that the gallery exists and belongs to the artist
 */
function validateGalleryOwnership($galleryId, $artistId, $db) {
    try {
        $stmt = $db->prepare("SELECT gallery_id, title, artist_id FROM galleries WHERE gallery_id = ?");
        $stmt->bind_param("i", $galleryId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'valid' => false,
                'message' => 'Gallery not found'
            ];
        }
        
        $gallery = $result->fetch_assoc();
        
        if ($gallery['artist_id'] != $artistId) {
            return [
                'valid' => false,
                'message' => 'You can only delete your own galleries'
            ];
        }
        
        return [
            'valid' => true,
            'gallery' => $gallery
        ];
        
    } catch (Exception $e) {
        return [
            'valid' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ];
    }
}

try {
    // Use cookie-based authentication instead of sessions
    $artistId = validateUserCookie($db);
    
    // Check if user is logged in - require proper authentication
    if (!$artistId) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'User not authenticated. Please log in.',
            'error_code' => 'AUTHENTICATION_REQUIRED'
        ]);
        exit;
    }
    
    // Get gallery ID from request
    $input = json_decode(file_get_contents('php://input'), true);
    $galleryId = null;
    
    // Handle different request methods
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // For DELETE requests, get ID from JSON body or URL parameter
        if ($input && isset($input['gallery_id'])) {
            $galleryId = $input['gallery_id'];
        } elseif (isset($_GET['id'])) {
            $galleryId = $_GET['id'];
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // For POST requests, get ID from JSON body or POST data
        if ($input && isset($input['gallery_id'])) {
            $galleryId = $input['gallery_id'];
        } elseif (isset($_POST['gallery_id'])) {
            $galleryId = $_POST['gallery_id'];
        }
    }
    
    // Validate gallery ID
    if (!$galleryId || !is_numeric($galleryId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid gallery ID provided',
            'error_code' => 'INVALID_GALLERY_ID'
        ]);
        exit;
    }
    
    // Validate ownership and get gallery details
    $validation = validateGalleryOwnership($galleryId, $artistId, $db);
    if (!$validation['valid']) {
        $statusCode = ($validation['message'] === 'Gallery not found') ? 404 : 403;
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'message' => $validation['message'],
            'error_code' => ($validation['message'] === 'Gallery not found') ? 'GALLERY_NOT_FOUND' : 'GALLERY_NOT_OWNED'
        ]);
        exit;
    }
    
    $galleryDetails = $validation['gallery'];
    
    // Start database transaction
    $db->autocommit(false);
    
    try {
        // Delete the gallery
        $stmt = $db->prepare("DELETE FROM galleries WHERE gallery_id = ?");
        $stmt->bind_param("i", $galleryId);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to delete gallery from database");
        }
        
        // Commit transaction
        $db->commit();
        
        // Delete gallery image files
        $imageCleanup = deleteGalleryImages($galleryId);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Gallery deleted successfully',
            'data' => [
                'gallery_id' => $galleryId,
                'title' => $galleryDetails['title'],
                'image_cleanup' => $imageCleanup
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Delete gallery error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while deleting the gallery: ' . $e->getMessage(),
        'error_code' => 'SERVER_ERROR'
    ]);
} finally {
    // Restore autocommit
    if (isset($db)) {
        $db->autocommit(true);
    }
}
?>
