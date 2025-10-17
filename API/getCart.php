<?php
require_once 'db.php';
require_once 'checkCredentials.php';

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}


function getUserIdFromCredentials() {
    // Use the checkCredentials.php function
    $credentials = checkUserCredentials();
    
    if (!$credentials['authenticated'] || !$credentials['user_id']) {
        throw new Exception('User not authenticated. Please log in.');
    }
    
    return $credentials['user_id'];
}

function getCartItems($userId) {
    global $db;
    
    $cartItems = [];
    
    // Get all cart items for user
    $cartQuery = "SELECT id as cart_id, product_id, quantity, type, created_at as added_date 
                  FROM cart 
                  WHERE user_id = ? AND is_active = 1 
                  ORDER BY created_at DESC";
    
    $stmt = $db->prepare($cartQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $cartResult = $stmt->get_result();
    
    while ($cartRow = $cartResult->fetch_assoc()) {
        $productData = null;
        
        // Get product data based on type
        switch ($cartRow['type']) {
            case 'artwork':
                $productQuery = "SELECT 
                                    a.artwork_id as product_id,
                                    a.title,
                                    a.description,
                                    a.price,
                                    a.artwork_image,
                                    ap.image_path as photo_filename,
                                    a.dimensions,
                                    a.year,
                                    a.type as artwork_type,
                                    a.is_available,
                                    a.on_auction,
                                    u.user_id as artist_id,
                                    u.first_name as artist_first_name,
                                    u.last_name as artist_last_name,
                                    u.profile_picture as artist_profile_picture
                                FROM artworks a
                                LEFT JOIN artwork_photos ap ON a.artwork_id = ap.artwork_id AND (ap.is_primary = 1 OR ap.is_primary IS NULL)
                                INNER JOIN users u ON a.artist_id = u.user_id
                                WHERE a.artwork_id = ?";
                break;
                
            case 'session':
                $productQuery = "SELECT 
                                    s.session_id as product_id,
                                    s.title,
                                    s.session_description as description,
                                    s.price,
                                    null as image,
                                    null as photo_filename,
                                    s.category,
                                    s.date,
                                    s.open_time,
                                    s.city,
                                    s.street,
                                    s.capacity,
                                    s.doctor_name,
                                    s.doctor_description,
                                    s.doctor_photo,
                                    s.is_active
                                FROM sessions s
                                WHERE s.session_id = ?";
                break;
                
            case 'workshop':
                $productQuery = "SELECT 
                                    w.workshop_id as product_id,
                                    w.title,
                                    w.workshop_description as description,
                                    w.price,
                                    null as image,
                                    null as photo_filename,
                                    w.category,
                                    w.date,
                                    w.open_time,
                                    w.city,
                                    w.street,
                                    w.capacity,
                                    w.doctor_name,
                                    w.doctor_description,
                                    w.doctor_photo,
                                    w.is_active
                                FROM workshops w
                                WHERE w.workshop_id = ?";
                break;
                
            default:
                continue 2; // Skip unknown types
        }
        
        if ($productQuery) {
            $productStmt = $db->prepare($productQuery);
            $productStmt->bind_param("i", $cartRow['product_id']);
            $productStmt->execute();
            $productResult = $productStmt->get_result();
            
            if ($productResult->num_rows > 0) {
                $productData = $productResult->fetch_assoc();
                $productData['cart_id'] = $cartRow['cart_id'];
                $productData['quantity'] = $cartRow['quantity'];
                $productData['added_date'] = $cartRow['added_date'];
                $productData['type'] = $cartRow['type'];
                $productData['item_total'] = $productData['price'] * $cartRow['quantity'];
                
                $cartItems[] = $productData;
            }
        }
    }
    
    return $cartItems;
}

function buildCartSummary($cartItems, $userId) {
    $total_amount = 0;
    $total_items = 0;
    
    foreach ($cartItems as $item) {
        $total_amount += $item['item_total'];
        $total_items += $item['quantity'];
    }
    
    return [
        'user_id' => $userId,
        'total_items' => $total_items,
        'total_amount' => round($total_amount, 2),
        'currency' => 'USD',
        'items' => $cartItems,
        'cart_summary' => [
            'items_count' => count($cartItems),
            'total_quantity' => $total_items,
            'subtotal' => round($total_amount, 2),
            'estimated_tax' => round($total_amount * 0.08, 2), // 8% estimated tax
            'estimated_total' => round($total_amount * 1.08, 2)
        ]
    ];
}

function formatCartItem($row) {
    return [
        'cart_id' => (int)$row['cart_id'],
        'quantity' => (int)$row['quantity'],
        'added_date' => $row['added_date'],
        'type' => $row['type'] ?? 'artwork',
        'artwork' => [
            'artwork_id' => (int)$row['artwork_id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'price' => (float)$row['price'],
            'artwork_image' => $row['artwork_image'],
            'artwork_photo_filename' => isset($row['artwork_photo_filename']) ? $row['artwork_photo_filename'] : null,
            // Prefer the artwork_photos filename when available, otherwise fallback to artworks.artwork_image
            'artwork_image_url' => (!empty($row['artwork_photo_filename']) ? './uploads/artworks/' . $row['artwork_photo_filename'] : (!empty($row['artwork_image']) ? './uploads/artworks/' . $row['artwork_image'] : null)),
            'dimensions' => $row['dimensions'],
            'year' => $row['year'] ? (int)$row['year'] : null,
            'type' => $row['artwork_type'],
            'is_available' => (bool)$row['is_available'],
            'on_auction' => (bool)$row['on_auction']
        ],
        'artist' => [
            'artist_id' => (int)$row['artist_id'],
            'first_name' => $row['artist_first_name'],
            'last_name' => $row['artist_last_name'],
            'full_name' => $row['artist_first_name'] . ' ' . $row['artist_last_name'],
            'profile_picture' => $row['artist_profile_picture']
        ],
        'item_total' => (float)$row['item_total'],
        'formatted_item_total' => '$' . number_format((float)$row['item_total'], 2)
    ];
}


function handleGetCart() {
    global $db;
    
    try {
        // Validate database connection
        if (!isset($db) || $db->connect_error) {
            throw new Exception("Database connection failed: " . ($db->connect_error ?? "Connection not established"));
        }
        
        // Get user ID from credentials
        $user_id = getUserIdFromCredentials();
        
        // Get cart items
        $cartItems = getCartItems($user_id);
        
        // Build cart summary
        $cart_data = [
            'user_id' => $user_id,
            'cart_items' => $cartItems,
            'total_items' => count($cartItems),
            'total_amount' => array_sum(array_column($cartItems, 'item_total')),
            'currency' => 'EGP'
        ];
        
        // Send success response
        sendResponse(true, 'Cart retrieved successfully', $cart_data);
        
    } catch (Exception $e) {
        // Send error response
        sendResponse(false, 'An error occurred while retrieving cart: ' . $e->getMessage());
    } finally {
        // Close database connection if it exists
        if (isset($db) && !$db->connect_error) {
            $db->close();
        }
    }
}

handleGetCart();
?>