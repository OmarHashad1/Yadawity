<?php
// removeFromWishlist.php - API to remove items from user's wishlist

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

require_once 'db.php';
require_once 'checkCredentials.php';

// Debug logging
error_log("RemoveFromWishlist API called");
error_log("User authenticated: " . ($isAuthenticated ? 'true' : 'false'));
error_log("Current user ID: " . ($currentUserId ?? 'null'));

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Check if user is authenticated
if (!$isAuthenticated) {
    $response['message'] = 'Please log in to manage your wishlist';
    $response['redirect'] = 'login.php';
    echo json_encode($response);
    exit;
}

try {
    // Get the raw POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }

    // Validate input
    if (empty($data['wishlist_id'])) {
        throw new Exception('Wishlist ID is required');
    }

    $wishlist_id = intval($data['wishlist_id']);

    // Verify the wishlist item belongs to the authenticated user
    $checkStmt = $db->prepare("
        SELECT id 
        FROM wishlists 
        WHERE id = ? AND user_id = ?
    ");
    
    if (!$checkStmt) {
        throw new Exception('Database prepare failed: ' . $db->error);
    }
    
    $checkStmt->bind_param("ii", $wishlist_id, $currentUserId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        $checkStmt->close();
        throw new Exception('Wishlist item not found or access denied');
    }
    $checkStmt->close();

    // Remove the item from wishlist
    $deleteStmt = $db->prepare("
        DELETE FROM wishlists 
        WHERE id = ? AND user_id = ?
    ");
    
    if (!$deleteStmt) {
        throw new Exception('Database prepare failed: ' . $db->error);
    }
    
    $deleteStmt->bind_param("ii", $wishlist_id, $currentUserId);
    $deleteResult = $deleteStmt->execute();

    if (!$deleteResult) {
        $deleteStmt->close();
        throw new Exception('Failed to remove item from wishlist: ' . $db->error);
    }

    // Check if any rows were affected
    if ($deleteStmt->affected_rows === 0) {
        $deleteStmt->close();
        throw new Exception('Item not found in wishlist');
    }
    
    $deleteStmt->close();

    $response['success'] = true;
    $response['message'] = 'Item removed from wishlist successfully';
    echo json_encode($response);

} catch (Exception $e) {
    error_log("RemoveFromWishlist Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(400);
    $response['message'] = $e->getMessage();
    echo json_encode($response);
} catch (Error $e) {
    error_log("RemoveFromWishlist Fatal Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
?>
