<?php
/**
 * Add to Wishlist API
 * 
 * Adds artwork to user's wishlist
 * Requires user authentication
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'db.php';
require_once 'checkCredentials.php';

// Debug: Log all received cookies and headers
error_log("AddToWishlist API - All cookies: " . json_encode($_COOKIE));
error_log("AddToWishlist API - All headers: " . json_encode(getallheaders()));
error_log("AddToWishlist API - Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("AddToWishlist API - Request URI: " . $_SERVER['REQUEST_URI']);
error_log("AddToWishlist API - HTTP Host: " . $_SERVER['HTTP_HOST']);
error_log("AddToWishlist API - Is authenticated: " . ($isAuthenticated ? 'true' : 'false'));
error_log("AddToWishlist API - Current user ID: " . ($currentUserId ?? 'null'));
error_log("AddToWishlist API - Auth message: " . ($authMessage ?? 'null'));

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Check if user is authenticated
if (!$isAuthenticated) {
    $response['message'] = 'Please log in to add items to wishlist';
    $response['redirect'] = 'login.php';
    echo json_encode($response);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Debug: Log the input data
    error_log("AddToWishlist API - Input data: " . json_encode($input));
    error_log("AddToWishlist API - User ID: " . ($currentUserId ?? 'null'));
    
    // Validate required fields
    if (!isset($input['artwork_id'])) {
        $response['message'] = 'Artwork ID is required';
        echo json_encode($response);
        exit;
    }
    
    $artworkId = (int)$input['artwork_id'];
    $userId = $currentUserId;
    
    // Debug: Log the parsed values
    error_log("AddToWishlist API - Parsed artwork_id: " . $artworkId);
    
    // Validate inputs
    if ($artworkId <= 0) {
        $response['message'] = 'Invalid artwork ID';
        echo json_encode($response);
        exit;
    }
    
    // Check if artwork exists and get artwork information
    $artworkStmt = $db->prepare("
        SELECT a.artwork_id, a.title, a.price, a.is_available, a.on_auction, a.artist_id
        FROM artworks a
        LEFT JOIN users u ON a.artist_id = u.user_id
        WHERE a.artwork_id = ? AND u.is_active = 1
    ");
    
    if (!$artworkStmt) {
        throw new Exception('Database error: ' . $db->error);
    }
    
    $artworkStmt->bind_param("i", $artworkId);
    $artworkStmt->execute();
    $artworkResult = $artworkStmt->get_result();
    
    if ($artworkResult->num_rows === 0) {
        $response['message'] = 'Artwork not found or not available';
        $response['debug'] = [
            'searched_artwork_id' => $artworkId,
            'artwork_id_type' => gettype($artworkId),
            'original_input' => $input['artwork_id'] ?? 'missing'
        ];
        echo json_encode($response);
        exit;
    }
    
    $artwork = $artworkResult->fetch_assoc();
    
    // Check if user is trying to add their own artwork
    if ($artwork['artist_id'] == $userId) {
        $response['message'] = 'You cannot add your own artwork to wishlist';
        echo json_encode($response);
        exit;
    }
    
    // Check if item already exists in wishlist
    $wishlistCheckQuery = "
        SELECT id 
        FROM wishlists 
        WHERE user_id = ? AND product_id = ? AND is_active = 1
    ";
    
    error_log("AddToWishlist API - Wishlist check query: " . $wishlistCheckQuery);
    error_log("AddToWishlist API - Parameters: user_id={$userId}, artwork_id={$artworkId}");
    
    $wishlistCheckStmt = $db->prepare($wishlistCheckQuery);
    
    if (!$wishlistCheckStmt) {
        error_log("AddToWishlist API - Prepare failed: " . $db->error);
        throw new Exception('Database error preparing wishlist check: ' . $db->error);
    }
    
    $wishlistCheckStmt->bind_param("ii", $userId, $artworkId);
    
    if (!$wishlistCheckStmt->execute()) {
        error_log("AddToWishlist API - Execute failed: " . $wishlistCheckStmt->error);
        throw new Exception('Database error executing wishlist check: ' . $wishlistCheckStmt->error);
    }
    
    $wishlistResult = $wishlistCheckStmt->get_result();
    
    if ($wishlistResult->num_rows > 0) {
        // Item already exists in wishlist
        $response['success'] = true;
        $response['message'] = 'This item is already in your wishlist';
        $response['data'] = [
            'artwork_id' => $artworkId,
            'artwork_title' => $artwork['title'],
            'price' => $artwork['price'],
            'action' => 'already_exists'
        ];
    } else {
        // Add new item to wishlist
        $insertQuery = "
            INSERT INTO wishlists (user_id, product_id, is_active, created_at) 
            VALUES (?, ?, 1, CURRENT_TIMESTAMP)
        ";
        
        error_log("AddToWishlist API - Insert query: " . $insertQuery);
        error_log("AddToWishlist API - Insert parameters: user_id={$userId}, artwork_id={$artworkId}");
        
        $insertStmt = $db->prepare($insertQuery);
        
        if (!$insertStmt) {
            error_log("AddToWishlist API - Insert prepare failed: " . $db->error);
            throw new Exception('Database error preparing insert: ' . $db->error);
        }
        
        $insertStmt->bind_param("ii", $userId, $artworkId);
        
        if (!$insertStmt->execute()) {
            error_log("AddToWishlist API - Insert execute failed: " . $insertStmt->error);
            throw new Exception('Database error executing insert: ' . $insertStmt->error);
        }
        
        $response['success'] = true;
        $response['message'] = 'Item added to wishlist successfully';
        $response['data'] = [
            'artwork_id' => $artworkId,
            'artwork_title' => $artwork['title'],
            'price' => $artwork['price'],
            'action' => 'added'
        ];
    }
    
} catch (Exception $e) {
    error_log('AddToWishlist Error: ' . $e->getMessage());
    error_log('AddToWishlist Stack Trace: ' . $e->getTraceAsString());
    $response['message'] = 'An error occurred while adding to wishlist';
    $response['debug_error'] = $e->getMessage(); // Add debug info
    
    // Close any open statements (only if they exist and haven't been closed)
    if (isset($artworkStmt) && $artworkStmt !== false) {
        try { $artworkStmt->close(); } catch (Exception $closeEx) {}
    }
    if (isset($wishlistCheckStmt) && $wishlistCheckStmt !== false) {
        try { $wishlistCheckStmt->close(); } catch (Exception $closeEx) {}
    }
    if (isset($insertStmt) && $insertStmt !== false) {
        try { $insertStmt->close(); } catch (Exception $closeEx) {}
    }
}

echo json_encode($response);
?>
