<?php
session_start();

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once 'db.php';

// Log the request for debugging
error_log("RemoveFromCart API called - User: " . ($_SESSION['user_id'] ?? 'not logged in'));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get input data
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

// Log the input data
error_log("RemoveFromCart raw input: " . $rawInput);
error_log("RemoveFromCart parsed input: " . json_encode($input));

// Check if input is valid JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON input'
    ]);
    exit;
}

// Check if product_id is provided
if (!isset($input['product_id']) || empty($input['product_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Product ID is required'
    ]);
    exit;
}

$artwork_id = intval($input['product_id']);
$product_type = $input['type'] ?? 'artwork'; // Default to 'artwork' if not provided

try {
    // Validate database connection
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Check if item exists in cart (considering both product_id and type)
    $checkStmt = $db->prepare("SELECT quantity, type FROM cart WHERE user_id = ? AND product_id = ? AND type = ?");
    if (!$checkStmt) {
        throw new Exception("Failed to prepare check statement: " . $db->error);
    }
    
    $checkStmt->bind_param("iis", $user_id, $artwork_id, $product_type);
    
    if (!$checkStmt->execute()) {
        throw new Exception("Failed to execute check query: " . $checkStmt->error);
    }
    
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("Item not found in cart - User: $user_id, Product: $artwork_id, Type: $product_type");
        $checkStmt->close();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Item not found in cart'
        ]);
        exit;
    }
    
    $currentItem = $result->fetch_assoc();
    $checkStmt->close();
    
    // Remove item from cart (considering both product_id and type)
    $deleteStmt = $db->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ? AND type = ?");
    if (!$deleteStmt) {
        throw new Exception("Failed to prepare delete statement: " . $db->error);
    }
    
    $deleteStmt->bind_param("iis", $user_id, $artwork_id, $product_type);
    
    if ($deleteStmt->execute()) {
        if ($deleteStmt->affected_rows > 0) {
            error_log("Successfully removed item - User: $user_id, Product: $artwork_id, Type: $product_type");
            
            // Get updated cart count
            $countStmt = $db->prepare("SELECT COUNT(*) as cart_count FROM cart WHERE user_id = ?");
            if (!$countStmt) {
                throw new Exception("Failed to prepare count statement: " . $db->error);
            }
            
            $countStmt->bind_param("i", $user_id);
            
            if (!$countStmt->execute()) {
                throw new Exception("Failed to execute count query: " . $countStmt->error);
            }
            
            $countResult = $countStmt->get_result();
            $cartCount = $countResult->fetch_assoc()['cart_count'];
            $countStmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Item removed from cart successfully',
                'cart_count' => (int)$cartCount,
                'removed_quantity' => (int)$currentItem['quantity']
            ]);
        } else {
            error_log("No rows affected during delete - User: $user_id, Product: $artwork_id, Type: $product_type");
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'No item was removed'
            ]);
        }
    } else {
        throw new Exception("Failed to execute delete query: " . $deleteStmt->error);
    }
    
    $deleteStmt->close();
    
} catch (Exception $e) {
    error_log("RemoveFromCart error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} finally {
    // Close database connection
    if (isset($db) && $db) {
        $db->close();
    }
}
?>
