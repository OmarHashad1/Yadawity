<?php
session_start();

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once 'db.php';

// Log the request for debugging
error_log("UpdateCartQuantity API called - User: " . ($_SESSION['user_id'] ?? 'not logged in'));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to update your cart'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get input data
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

// Log the input data
error_log("UpdateCartQuantity raw input: " . $rawInput);
error_log("UpdateCartQuantity parsed input: " . json_encode($input));

// Check if input is valid JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong. Please try again.'
    ]);
    exit;
}

// Check required fields
if (!isset($input['product_id']) || empty($input['product_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please select a valid product to update'
    ]);
    exit;
}

if (!isset($input['quantity']) || !is_numeric($input['quantity'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid quantity'
    ]);
    exit;
}

$product_id = intval($input['product_id']);
$new_quantity = intval($input['quantity']);
$product_type = $input['type'] ?? 'artwork'; // Default to 'artwork' if not provided

// Validate quantity
if ($new_quantity < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Please select at least 1 item'
    ]);
    exit;
}

if ($new_quantity > 10) {
    echo json_encode([
        'success' => false,
        'message' => 'Maximum 10 items allowed per product'
    ]);
    exit;
}

try {
    // Validate database connection
    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Check if item exists in cart
    $checkStmt = $db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND type = ?");
    if (!$checkStmt) {
        throw new Exception("Failed to prepare check statement: " . $db->error);
    }
    
    $checkStmt->bind_param("iis", $user_id, $product_id, $product_type);
    
    if (!$checkStmt->execute()) {
        throw new Exception("Failed to execute check query: " . $checkStmt->error);
    }
    
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("Item not found in cart - User: $user_id, Product: $product_id, Type: $product_type");
        $checkStmt->close();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'This item is no longer in your cart'
        ]);
        exit;
    }
    
    $currentItem = $result->fetch_assoc();
    $cart_row_id = $currentItem['id'];
    $old_quantity = $currentItem['quantity'];
    $checkStmt->close();

    // Check if product exists and get its current stock
    $productStmt = $db->prepare("SELECT artwork_id, title, stock_quantity FROM artworks WHERE artwork_id = ?");
    if (!$productStmt) {
        throw new Exception("Failed to prepare product statement: " . $db->error);
    }
    
    $productStmt->bind_param("i", $product_id);
    
    if (!$productStmt->execute()) {
        throw new Exception("Failed to execute product query: " . $productStmt->error);
    }
    
    $productResult = $productStmt->get_result();
    
    if ($productResult->num_rows === 0) {
        error_log("Product not found - Product: $product_id");
        $productStmt->close();
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'This product is no longer available'
        ]);
        exit;
    }
    
    $product = $productResult->fetch_assoc();
    $productStmt->close();

    // Get available stock - handle case where stock_quantity column might not exist
    $available_stock = 999; // Default fallback
    if (isset($product['stock_quantity'])) {
        $available_stock = (int)$product['stock_quantity'];
        
        // Validate stock quantity - ensure requested quantity doesn't exceed available stock
        if ($available_stock > 0 && $new_quantity > $available_stock) {
            error_log("Insufficient stock - Product: $product_id, Requested: $new_quantity, Available: $available_stock");
            echo json_encode([
                'success' => false,
                'message' => $available_stock == 1 ? "Only 1 item left in stock" : "Only $available_stock items left in stock",
                'available_stock' => $available_stock,
                'requested_quantity' => $new_quantity
            ]);
            exit;
        }
        
        // Additional check: if stock is 0, don't allow any quantity
        if ($available_stock === 0) {
            error_log("Out of stock - Product: $product_id");
            echo json_encode([
                'success' => false,
                'message' => 'This item is currently sold out',
                'available_stock' => 0
            ]);
            exit;
        }
    }
    
    // Update cart quantity
    $updateStmt = $db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    if (!$updateStmt) {
        throw new Exception("Failed to prepare update statement: " . $db->error);
    }
    
    $updateStmt->bind_param("ii", $new_quantity, $cart_row_id);
    
    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update cart quantity: " . $updateStmt->error);
    }
    
    $affected_rows = $updateStmt->affected_rows;
    $updateStmt->close();
    
    if ($affected_rows > 0) {
        error_log("Successfully updated cart quantity - User: $user_id, Product: $product_id, Old: $old_quantity, New: $new_quantity");
        
        // Get updated cart totals
        $totalStmt = $db->prepare("
            SELECT 
                COUNT(*) as total_items,
                SUM(c.quantity) as total_quantity,
                SUM(c.quantity * a.price) as total_amount
            FROM cart c 
            JOIN artworks a ON c.product_id = a.artwork_id 
            WHERE c.user_id = ? AND c.is_active = 1
        ");
        
        if (!$totalStmt) {
            throw new Exception("Failed to prepare total statement: " . $db->error);
        }
        
        $totalStmt->bind_param("i", $user_id);
        
        if (!$totalStmt->execute()) {
            throw new Exception("Failed to execute total query: " . $totalStmt->error);
        }
        
        $totalResult = $totalStmt->get_result();
        $totals = $totalResult->fetch_assoc();
        $totalStmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Cart updated successfully',
            'old_quantity' => $old_quantity,
            'new_quantity' => $new_quantity,
            'product_id' => $product_id,
            'cart_totals' => [
                'total_items' => (int)$totals['total_items'],
                'total_quantity' => (int)$totals['total_quantity'],
                'total_amount' => (float)$totals['total_amount']
            ]
        ]);
    } else {
        error_log("No rows affected during update - User: $user_id, Product: $product_id");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to update cart. Please try again.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("UpdateCartQuantity error: " . $e->getMessage());
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
