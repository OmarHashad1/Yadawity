<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db.php';

/**
 * Get user ID from cookie
 * @return int|false - Returns user ID if cookie is valid, false otherwise
 */
function getUserIdFromCookie() {
    global $db;
    
    // Check if cookie exists
    if (!isset($_COOKIE['user_login'])) {
        error_log("getArtistOrders: No user_login cookie found");
        return false;
    }
    
    $cookieValue = $_COOKIE['user_login'];
    error_log("getArtistOrders: Cookie value: " . $cookieValue);
    $parts = explode('_', $cookieValue, 2);
    
    if (count($parts) !== 2) {
        error_log("getArtistOrders: Invalid cookie format");
        return false;
    }
    
    $user_id = (int)$parts[0];
    $provided_hash = $parts[1];
    
    if ($user_id <= 0) {
        error_log("getArtistOrders: Invalid user_id: " . $user_id);
        return false;
    }
    
    // Get user data
    $stmt = $db->prepare("SELECT email, is_active FROM users WHERE user_id = ?");
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return false;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Check if user is active
    if (!$user['is_active']) {
        return false;
    }
    
    // Get all active login sessions for this user with session_id
    $session_stmt = $db->prepare("
        SELECT session_id, login_time 
        FROM user_login_sessions 
        WHERE user_id = ? AND is_active = 1 
        ORDER BY login_time DESC
    ");
    
    if (!$session_stmt) {
        return false;
    }
    
    $session_stmt->bind_param("i", $user_id);
    $session_stmt->execute();
    $session_result = $session_stmt->get_result();
    
    // Try to validate the hash against any active session using the same method as login
    $valid_session = false;
    $session_count = 0;
    while ($session = $session_result->fetch_assoc()) {
        $session_count++;
        $cookie_data = $session['session_id'] . '|' . $user['email'] . '|' . $user_id . '|' . $session['login_time'];
        $expected_hash = hash_hmac('sha256', $cookie_data, $session['session_id'] . '_' . $user['email']);
        error_log("getArtistOrders: Session " . $session_count . " - Expected hash: " . $expected_hash . ", Provided: " . $provided_hash);
        if ($provided_hash === $expected_hash) {
            $valid_session = true;
            error_log("getArtistOrders: Hash matched for session " . $session_count);
            break;
        }
    }
    error_log("getArtistOrders: Checked " . $session_count . " sessions, valid: " . ($valid_session ? 'true' : 'false'));
    $session_stmt->close();
    
    if (!$valid_session) {
        error_log("getArtistOrders: No valid session found for user " . $user_id);
        return false;
    }
    
    return $user_id;
}

/**
 * Send JSON response
 */
function sendResponse($success, $message, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

try {
    // Check if database connection exists
    if (!isset($db) || $db->connect_error) {
        sendResponse(false, 'Database connection failed', [], 500);
    }
    
    // Get user ID from cookie
    $artistId = getUserIdFromCookie();
    
    if (!$artistId) {
        sendResponse(false, 'Authentication required. Please login first.', [], 401);
    }
    
    // Get optional filters from query parameters
    $status = $_GET['status'] ?? '';
    $limit = (int)($_GET['limit'] ?? 50);
    $offset = (int)($_GET['offset'] ?? 0);
    $orderBy = $_GET['order_by'] ?? 'order_date';
    $orderDirection = strtoupper($_GET['order_direction'] ?? 'DESC');
    
    // Validate parameters
    $limit = max(1, min($limit, 100)); // Between 1 and 100
    $offset = max(0, $offset);
    
    $validOrderBy = ['id', 'order_number', 'total_amount', 'status', 'order_date', 'created_at'];
    if (!in_array($orderBy, $validOrderBy)) {
        $orderBy = 'order_date';
    }
    
    $validOrderDirection = ['ASC', 'DESC'];
    if (!in_array($orderDirection, $validOrderDirection)) {
        $orderDirection = 'DESC';
    }
    
    // Build the query to get artist's orders
    // We join orders with order_items to get only orders that contain this artist's items
    $query = "
        SELECT DISTINCT
            o.id as order_id,
            o.order_number,
            o.buyer_id,
            o.buyer_name,
            o.total_amount,
            o.status,
            o.shipping_address,
            o.order_date,
            o.created_at,
            o.updated_at
        FROM orders o
        INNER JOIN order_items oi ON o.id = oi.order_id
        WHERE oi.artist_id = ?
    ";
    
    $params = [$artistId];
    $types = "i";
    
    // Add status filter if provided
    if (!empty($status)) {
        $validStatuses = ['pending', 'confirmed', 'paid', 'shipped', 'delivered', 'cancelled'];
        if (in_array($status, $validStatuses)) {
            $query .= " AND o.status = ?";
            $params[] = $status;
            $types .= "s";
        }
    }
    
    $query .= " ORDER BY o.$orderBy $orderDirection";
    $query .= " LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $db->prepare($query);
    if (!$stmt) {
        sendResponse(false, 'Failed to prepare orders query: ' . $db->error, [], 500);
    }
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        // Get all artist's items for this order
        $itemsQuery = "
            SELECT 
                oi.id as item_id,
                oi.artwork_id,
                oi.artwork_title,
                oi.price,
                oi.quantity,
                oi.subtotal,
                oi.created_at as item_created_at
            FROM order_items oi
            WHERE oi.order_id = ? AND oi.artist_id = ?
        ";
        
        $itemsStmt = $db->prepare($itemsQuery);
        if ($itemsStmt) {
            $itemsStmt->bind_param("ii", $row['order_id'], $artistId);
            $itemsStmt->execute();
            $itemsResult = $itemsStmt->get_result();
            
            $artistItems = [];
            while ($itemRow = $itemsResult->fetch_assoc()) {
                $artistItems[] = [
                    'item_id' => (int)$itemRow['item_id'],
                    'artwork_id' => (int)$itemRow['artwork_id'],
                    'artwork_title' => $itemRow['artwork_title'],
                    'price' => (float)$itemRow['price'],
                    'quantity' => (int)$itemRow['quantity'],
                    'subtotal' => (float)$itemRow['subtotal'],
                    'item_created_at' => $itemRow['item_created_at']
                ];
            }
            $itemsStmt->close();
        } else {
            $artistItems = [];
        }
        
        // Calculate artist's revenue from this order
        $artistRevenue = array_sum(array_column($artistItems, 'subtotal'));
        
        $orders[] = [
            'order_id' => (int)$row['order_id'],
            'order_number' => $row['order_number'],
            'buyer' => [
                'buyer_id' => (int)$row['buyer_id'],
                'buyer_name' => $row['buyer_name'],
                'full_name' => $row['buyer_name'] // For compatibility
            ],
            'order_details' => [
                'total_amount' => (float)$row['total_amount'],
                'status' => $row['status'],
                'shipping_address' => $row['shipping_address'],
                'order_date' => $row['order_date'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ],
            'artist_items' => $artistItems,
            'artist_revenue' => $artistRevenue,
            'items_count' => count($artistItems)
        ];
    }
    
    $stmt->close();
    
    // Get total count for pagination
    $countQuery = "
        SELECT COUNT(DISTINCT o.id) as total_orders
        FROM orders o
        INNER JOIN order_items oi ON o.id = oi.order_id
        WHERE oi.artist_id = ?
    ";
    
    $countParams = [$artistId];
    $countTypes = "i";
    
    if (!empty($status) && in_array($status, ['pending', 'confirmed', 'paid', 'shipped', 'delivered', 'cancelled'])) {
        $countQuery .= " AND o.status = ?";
        $countParams[] = $status;
        $countTypes .= "s";
    }
    
    $countStmt = $db->prepare($countQuery);
    if ($countStmt) {
        $countStmt->bind_param($countTypes, ...$countParams);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalOrders = $countResult->fetch_assoc()['total_orders'];
        $countStmt->close();
    } else {
        $totalOrders = 0;
    }
    
    // Get order statistics for this artist
    $statsQuery = "
        SELECT 
            COUNT(DISTINCT o.id) as total_orders,
            COALESCE(SUM(oi.subtotal), 0) as total_revenue,
            AVG(oi.subtotal) as average_order_value,
            COUNT(CASE WHEN o.status = 'pending' THEN 1 END) as pending_orders,
            COUNT(CASE WHEN o.status = 'confirmed' THEN 1 END) as confirmed_orders,
            COUNT(CASE WHEN o.status = 'paid' THEN 1 END) as paid_orders,
            COUNT(CASE WHEN o.status = 'shipped' THEN 1 END) as shipped_orders,
            COUNT(CASE WHEN o.status = 'delivered' THEN 1 END) as delivered_orders,
            COUNT(CASE WHEN o.status = 'cancelled' THEN 1 END) as cancelled_orders,
            SUM(oi.quantity) as total_items_sold
        FROM orders o
        INNER JOIN order_items oi ON o.id = oi.order_id
        WHERE oi.artist_id = ?
    ";
    
    $statsStmt = $db->prepare($statsQuery);
    if ($statsStmt) {
        $statsStmt->bind_param("i", $artistId);
        $statsStmt->execute();
        $stats = $statsStmt->get_result()->fetch_assoc();
        $statsStmt->close();
    } else {
        $stats = [
            'total_orders' => 0,
            'total_revenue' => 0,
            'average_order_value' => 0,
            'pending_orders' => 0,
            'confirmed_orders' => 0,
            'paid_orders' => 0,
            'shipped_orders' => 0,
            'delivered_orders' => 0,
            'cancelled_orders' => 0,
            'total_items_sold' => 0
        ];
    }
    
    // Build response data
    $responseData = [
        'artist_id' => $artistId,
        'orders' => $orders,
        'pagination' => [
            'total_orders' => (int)$totalOrders,
            'returned_orders' => count($orders),
            'limit' => $limit,
            'offset' => $offset,
            'page' => floor($offset / $limit) + 1,
            'total_pages' => ceil($totalOrders / $limit),
            'has_next' => ($offset + $limit) < $totalOrders,
            'has_previous' => $offset > 0
        ],
        'statistics' => [
            'total_orders' => (int)$stats['total_orders'],
            'total_revenue' => (float)$stats['total_revenue'],
            'average_order_value' => round((float)$stats['average_order_value'], 2),
            'total_items_sold' => (int)$stats['total_items_sold'],
            'pending_orders' => (int)$stats['pending_orders'],
            'confirmed_orders' => (int)$stats['confirmed_orders'],
            'paid_orders' => (int)$stats['paid_orders'],
            'shipped_orders' => (int)$stats['shipped_orders'],
            'delivered_orders' => (int)$stats['delivered_orders'],
            'cancelled_orders' => (int)$stats['cancelled_orders']
        ],
        'filters' => [
            'status' => $status,
            'order_by' => $orderBy,
            'order_direction' => $orderDirection
        ]
    ];
    
    sendResponse(true, 'Artist orders retrieved successfully', $responseData);
    
} catch (Exception $e) {
    error_log("Get Artist Orders Error: " . $e->getMessage());
    sendResponse(false, 'An error occurred while retrieving orders', [], 500);
} finally {
    if (isset($db) && $db instanceof mysqli) {
        $db->close();
    }
}
?>
