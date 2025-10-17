<?php
/**
 * Add to Cart API
 * 
 * Adds artwork to user's cart with quantity validation
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
error_log("AddToCart API - All cookies: " . json_encode($_COOKIE));
error_log("AddToCart API - All headers: " . json_encode(getallheaders()));
error_log("AddToCart API - Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("AddToCart API - Request URI: " . $_SERVER['REQUEST_URI']);
error_log("AddToCart API - HTTP Host: " . $_SERVER['HTTP_HOST']);
error_log("AddToCart API - Is authenticated: " . ($isAuthenticated ? 'true' : 'false'));
error_log("AddToCart API - Current user ID: " . ($currentUserId ?? 'null'));
error_log("AddToCart API - Auth message: " . ($authMessage ?? 'null'));

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Check if user is authenticated
if (!$isAuthenticated) {
    $response['message'] = 'Please log in to add items to cart';
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
    error_log("AddToCart API - Input data: " . json_encode($input));
    error_log("AddToCart API - User ID: " . ($currentUserId ?? 'null'));
    
    // Validate required fields - support both artwork_id and product_id
    $productId = null;
    $type = 'artwork'; // default type
    
    if (isset($input['product_id'])) {
        $productId = (int)$input['product_id'];
        $type = $input['type'] ?? 'artwork';
    } elseif (isset($input['artwork_id'])) {
        $productId = (int)$input['artwork_id'];
        $type = 'artwork';
    }
    
    if (!$productId || !isset($input['quantity'])) {
        $response['message'] = 'Product ID and quantity are required';
        echo json_encode($response);
        exit;
    }
    
    $quantity = (int)$input['quantity'];
    $userId = $currentUserId;
    
    // Debug: Log the parsed values
    error_log("AddToCart API - Parsed product_id: " . $productId);
    error_log("AddToCart API - Parsed quantity: " . $quantity);
    error_log("AddToCart API - Parsed type: " . $type);
    
    // Validate inputs
    if ($productId <= 0) {
        $response['message'] = 'Invalid product ID';
        echo json_encode($response);
        exit;
    }
    
    if ($quantity <= 0) {
        $response['message'] = 'Quantity must be greater than 0';
        echo json_encode($response);
        exit;
    }
    
    // Validate type
    $allowedTypes = ['artwork', 'session', 'workshop', 'gallery'];
    if (!in_array($type, $allowedTypes)) {
        $response['message'] = 'Invalid product type';
        echo json_encode($response);
        exit;
    }
    
    // Handle different product types
    $product = null;
    
    if ($type === 'artwork') {
        // Check if artwork exists and get stock information
        $artworkStmt = $db->prepare("
            SELECT a.artwork_id, a.title, a.price, a.stock_quantity, a.is_available, a.on_auction, a.artist_id
            FROM artworks a
            LEFT JOIN users u ON a.artist_id = u.user_id
            WHERE a.artwork_id = ? AND u.is_active = 1 AND a.on_auction = 0
        ");
        
        if (!$artworkStmt) {
            throw new Exception('Database error: ' . $db->error);
        }
        
        $artworkStmt->bind_param("i", $productId);
        $artworkStmt->execute();
        $artworkResult = $artworkStmt->get_result();
        
        if ($artworkResult->num_rows === 0) {
            $response['message'] = 'Artwork not found or not available';
            echo json_encode($response);
            exit;
        }
        
        $product = $artworkResult->fetch_assoc();
        
        // Check if artwork is on auction
        if ($product['on_auction'] == 1) {
            $response['message'] = 'This artwork is currently on auction and cannot be added to cart';
            echo json_encode($response);
            exit;
        }
        
        // Check if user is trying to add their own artwork
        if ($product['artist_id'] == $userId) {
            $response['message'] = 'You cannot add your own artwork to cart';
            echo json_encode($response);
            exit;
        }
        
        // Check available stock
        if ($product['stock_quantity'] < $quantity) {
            $response['message'] = "Only {$product['stock_quantity']} item(s) available in stock";
            $response['available_quantity'] = $product['stock_quantity'];
            echo json_encode($response);
            exit;
        }
        
    } elseif ($type === 'session') {
        // Check if session exists
        $sessionStmt = $db->prepare("
            SELECT session_id, title, price, capacity, is_active
            FROM sessions 
            WHERE session_id = ? AND is_active = 1
        ");
        
        if (!$sessionStmt) {
            throw new Exception('Database error: ' . $db->error);
        }
        
        $sessionStmt->bind_param("i", $productId);
        $sessionStmt->execute();
        $sessionResult = $sessionStmt->get_result();
        
        if ($sessionResult->num_rows === 0) {
            $response['message'] = 'Session not found or not available';
            echo json_encode($response);
            exit;
        }
        
        $product = $sessionResult->fetch_assoc();
        $product['stock_quantity'] = $product['capacity']; // Use capacity as stock for sessions
        
    } elseif ($type === 'workshop') {
        // Check if workshop exists
        $workshopStmt = $db->prepare("
            SELECT workshop_id, title, price, capacity, is_active
            FROM workshops 
            WHERE workshop_id = ? AND is_active = 1
        ");
        
        if (!$workshopStmt) {
            throw new Exception('Database error: ' . $db->error);
        }
        
        $workshopStmt->bind_param("i", $productId);
        $workshopStmt->execute();
        $workshopResult = $workshopStmt->get_result();
        
        if ($workshopResult->num_rows === 0) {
            $response['message'] = 'Workshop not found or not available';
            echo json_encode($response);
            exit;
        }
        
        $product = $workshopResult->fetch_assoc();
        $product['stock_quantity'] = $product['capacity']; // Use capacity as stock for workshops
    }
    
    // Check available stock/capacity
    if ($product['stock_quantity'] < $quantity) {
        $response['message'] = "Only {$product['stock_quantity']} item(s) available";
        $response['available_quantity'] = $product['stock_quantity'];
        echo json_encode($response);
        exit;
    }
    
    // Check if item already exists in cart
    $cartCheckQuery = "
        SELECT id, quantity 
        FROM cart 
        WHERE user_id = ? AND product_id = ? AND type = ? AND is_active = 1
    ";
    
    error_log("AddToCart API - Cart check query: " . $cartCheckQuery);
    error_log("AddToCart API - Parameters: user_id={$userId}, product_id={$productId}, type={$type}");
    
    $cartCheckStmt = $db->prepare($cartCheckQuery);
    
    if (!$cartCheckStmt) {
        error_log("AddToCart API - Prepare failed: " . $db->error);
        throw new Exception('Database error preparing cart check: ' . $db->error);
    }
    
    $cartCheckStmt->bind_param("iis", $userId, $productId, $type);
    
    if (!$cartCheckStmt->execute()) {
        error_log("AddToCart API - Execute failed: " . $cartCheckStmt->error);
        throw new Exception('Database error executing cart check: ' . $cartCheckStmt->error);
    }
    
    $cartResult = $cartCheckStmt->get_result();
    
    if ($cartResult->num_rows > 0) {
        // Item exists in cart
        $cartItem = $cartResult->fetch_assoc();
        
        // For sessions and workshops, don't allow duplicates or quantity increases
        if ($type === 'session' || $type === 'workshop') {
            $response['message'] = "This {$type} is already in your cart. You can only book one session/workshop of the same type.";
            echo json_encode($response);
            exit;
        }
        
        // For other items (artworks, galleries), allow quantity updates
        $newQuantity = $cartItem['quantity'] + $quantity;
        
        // Check if new quantity exceeds stock
        if ($newQuantity > $product['stock_quantity']) {
            $availableToAdd = $product['stock_quantity'] - $cartItem['quantity'];
            if ($availableToAdd <= 0) {
                $response['message'] = 'This item is already at maximum quantity in your cart';
            } else {
                $response['message'] = "You can only add {$availableToAdd} more of this item to your cart";
                $response['available_to_add'] = $availableToAdd;
            }
            echo json_encode($response);
            exit;
        }
        
        // Update existing cart item
        $updateStmt = $db->prepare("
            UPDATE cart 
            SET quantity = ?, created_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        
        if (!$updateStmt) {
            throw new Exception('Database error: ' . $db->error);
        }
        
        $updateStmt->bind_param("ii", $newQuantity, $cartItem['id']);
        
        if ($updateStmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Cart updated successfully';
            $response['data'] = [
                'product_id' => $productId,
                'title' => $product['title'],
                'quantity' => $newQuantity,
                'price' => $product['price'],
                'total_price' => $product['price'] * $newQuantity,
                'type' => $type,
                'action' => 'updated'
            ];
        } else {
            throw new Exception('Failed to update cart');
        }
        
    } else {
        // Add new item to cart
        $insertQuery = "
            INSERT INTO cart (user_id, product_id, quantity, type, is_active, created_at) 
            VALUES (?, ?, ?, ?, 1, CURRENT_TIMESTAMP)
        ";
        
        error_log("AddToCart API - Insert query: " . $insertQuery);
        error_log("AddToCart API - Insert parameters: user_id={$userId}, product_id={$productId}, quantity={$quantity}, type={$type}");
        
        $insertStmt = $db->prepare($insertQuery);
        
        if (!$insertStmt) {
            error_log("AddToCart API - Insert prepare failed: " . $db->error);
            throw new Exception('Database error preparing insert: ' . $db->error);
        }
        
        $insertStmt->bind_param("iiis", $userId, $productId, $quantity, $type);
        
        if (!$insertStmt->execute()) {
            error_log("AddToCart API - Insert execute failed: " . $insertStmt->error);
            throw new Exception('Database error executing insert: ' . $insertStmt->error);
        }
        
        $response['success'] = true;
        $response['message'] = ucfirst($type) . ' added to cart successfully';
        $response['data'] = [
            'product_id' => $productId,
            'title' => $product['title'],
            'quantity' => $quantity,
            'price' => $product['price'],
            'total_price' => $product['price'] * $quantity,
            'type' => $type,
            'action' => 'added'
        ];
    }
    
} catch (Exception $e) {
    error_log('AddToCart Error: ' . $e->getMessage());
    error_log('AddToCart Stack Trace: ' . $e->getTraceAsString());
    $response['message'] = 'An error occurred while adding to cart';
    $response['debug_error'] = $e->getMessage(); // Add debug info
    
    // Close any open statements (only if they exist and haven't been closed)
    if (isset($artworkStmt) && $artworkStmt !== false) {
        try { $artworkStmt->close(); } catch (Exception $closeEx) {}
    }
    if (isset($cartCheckStmt) && $cartCheckStmt !== false) {
        try { $cartCheckStmt->close(); } catch (Exception $closeEx) {}
    }
    if (isset($updateStmt) && $updateStmt !== false) {
        try { $updateStmt->close(); } catch (Exception $closeEx) {}
    }
    if (isset($insertStmt) && $insertStmt !== false) {
        try { $insertStmt->close(); } catch (Exception $closeEx) {}
    }
}

echo json_encode($response);
?>
