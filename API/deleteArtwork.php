<?php
// Disable error display to prevent HTML output before JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffering to catch any unwanted output
ob_start();

require_once "db.php";

// Clean any output that might have been generated
ob_clean();

// Set proper headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    exit;
}

// Allow both DELETE and POST methods
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only DELETE and POST requests are accepted.',
        'error_code' => 'METHOD_NOT_ALLOWED'
    ]);
    exit;
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
 * Delete artwork image files from the filesystem
 */
function deleteArtworkImages($artworkId, $uploadDir = null) {
    try {
        // Set default upload directory
        if ($uploadDir === null) {
            $uploadDir = dirname(__DIR__) . '/uploads/artworks/';
        }
        
        $deletedFiles = [];
        $errors = [];
        
        // Look for files with the artwork ID pattern
        $pattern = $uploadDir . 'artwork_' . $artworkId . '_*';
        $files = glob($pattern);
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if (unlink($file)) {
                    $deletedFiles[] = basename($file);
                } else {
                    $errors[] = 'Failed to delete: ' . basename($file);
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
            'message' => 'Error deleting artwork images: ' . $e->getMessage()
        ];
    }
}

/**
 * Validate artwork ownership
 */
function validateArtworkOwnership($db, $artworkId, $artistId) {
    try {
        $stmt = $db->prepare("SELECT artist_id FROM artworks WHERE artwork_id = ?");
        $stmt->bind_param("i", $artworkId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return [
                'valid' => false,
                'message' => 'Artwork not found'
            ];
        }
        
        $artwork = $result->fetch_assoc();
        
        if ($artwork['artist_id'] != $artistId) {
            return [
                'valid' => false,
                'message' => 'You are not authorized to delete this artwork'
            ];
        }
        
        return ['valid' => true];
        
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
    
    // Check if user is logged in - no fallback, require proper authentication
    if (!$artistId) {
        ob_clean();
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'User not authenticated. Please log in.',
            'error_code' => 'AUTHENTICATION_REQUIRED'
        ]);
        exit;
    }
    
    // Get artwork ID from request
    $input = json_decode(file_get_contents('php://input'), true);
    $artworkId = null;
    
    // Handle different request methods
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // For DELETE requests, get ID from JSON body or URL parameter
        if ($input && isset($input['artwork_id'])) {
            $artworkId = $input['artwork_id'];
        } elseif (isset($_GET['id'])) {
            $artworkId = $_GET['id'];
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // For POST requests, get ID from JSON body or POST data
        if ($input && isset($input['artwork_id'])) {
            $artworkId = $input['artwork_id'];
        } elseif (isset($_POST['artwork_id'])) {
            $artworkId = $_POST['artwork_id'];
        }
    }
    
    // Validate artwork ID
    if (!$artworkId || !is_numeric($artworkId)) {
        ob_clean();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid or missing artwork ID',
            'error_code' => 'INVALID_ARTWORK_ID'
        ]);
        exit;
    }
    
    $artworkId = (int)$artworkId;
    
    // Validate artwork ownership
    $ownershipCheck = validateArtworkOwnership($db, $artworkId, $artistId);
    if (!$ownershipCheck['valid']) {
        ob_clean();
        http_response_code($ownershipCheck['message'] === 'Artwork not found' ? 404 : 403);
        echo json_encode([
            'success' => false,
            'message' => $ownershipCheck['message'],
            'error_code' => $ownershipCheck['message'] === 'Artwork not found' ? 'ARTWORK_NOT_FOUND' : 'UNAUTHORIZED'
        ]);
        exit;
    }
    
    // Begin transaction
    $db->autocommit(false);
    
    try {
        // Get artwork details before deletion (for logging and file cleanup)
        $stmt = $db->prepare("SELECT title, artwork_image FROM artworks WHERE artwork_id = ?");
        $stmt->bind_param("i", $artworkId);
        $stmt->execute();
        $artworkDetails = $stmt->get_result()->fetch_assoc();
        
        // Delete related records first (to maintain referential integrity)
        
        // Delete from cart items (where product_id matches artwork_id and type is 'artwork')
        $stmt = $db->prepare("DELETE FROM cart WHERE product_id = ? AND type = 'artwork'");
        $stmt->bind_param("i", $artworkId);
        $stmt->execute();
        
        // Delete from wishlists (where product_id matches artwork_id and type is 'artwork')
        $stmt = $db->prepare("DELETE FROM wishlists WHERE product_id = ? AND type = 'artwork'");
        $stmt->bind_param("i", $artworkId);
        $stmt->execute();
        
        // Delete auction records if it's an auction item
        $stmt = $db->prepare("DELETE FROM auctions WHERE product_id = ?");
        $stmt->bind_param("i", $artworkId);
        $stmt->execute();
        
        // Finally, delete the artwork itself
        $stmt = $db->prepare("DELETE FROM artworks WHERE artwork_id = ?");
        $stmt->bind_param("i", $artworkId);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to delete artwork from database");
        }
        
        // Commit transaction
        $db->commit();
        
        // Delete artwork image files
        $imageCleanup = deleteArtworkImages($artworkId);
        
        // Clean output buffer and send JSON response
        ob_clean();
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Artwork deleted successfully',
            'data' => [
                'artwork_id' => $artworkId,
                'title' => $artworkDetails['title'],
                'image_cleanup' => $imageCleanup
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Delete artwork error: " . $e->getMessage());
    
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while deleting the artwork: ' . $e->getMessage(),
        'error_code' => 'SERVER_ERROR'
    ]);
} finally {
    // Restore autocommit
    if (isset($db)) {
        $db->autocommit(true);
    }
}
?>
