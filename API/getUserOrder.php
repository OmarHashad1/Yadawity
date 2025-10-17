<?php

require_once "db.php";
require_once "checkCredentials.php";
header('Content-Type: application/json');

// Check if user is authenticated using our standard method
if (!$isAuthenticated) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $currentUserId;

// Get user type to determine which orders to show
$user_query = "SELECT user_type FROM users WHERE user_id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_type = strtolower($user_data['user_type'] ?? '');
$user_stmt->close();

$orders = [];

// Treat all users the same way - show their own orders as buyers
$sql = "SELECT o.id AS order_id, o.total_amount, o.status, o.type, 
               o.gallery_id, o.artwork_id, o.created_at,
               oi.id AS order_item_id, oi.artwork_id AS item_artwork_id, oi.price, oi.quantity,
               a.title AS artwork_title, a.artwork_image,
               g.title AS gallery_title, g.gallery_type,
               main_artwork.title AS main_artwork_title, main_artwork.artwork_image AS main_artwork_image
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN artworks a ON oi.artwork_id = a.artwork_id
        LEFT JOIN galleries g ON o.gallery_id = g.gallery_id
        LEFT JOIN artworks main_artwork ON o.artwork_id = main_artwork.artwork_id
        WHERE o.buyer_id = ?
        ORDER BY o.created_at DESC, o.id DESC";

$stmt = $db->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $oid = $row['order_id'];
    if (!isset($orders[$oid])) {
        $order_data = [
            'order_id' => $oid,
            'total_amount' => $row['total_amount'],
            'status' => $row['status'],
            'type' => $row['type'],
            'gallery_id' => $row['gallery_id'],
            'artwork_id' => $row['artwork_id'],
            'gallery_title' => $row['gallery_title'],
            'gallery_type' => $row['gallery_type'],
            'main_artwork_title' => $row['main_artwork_title'],
            'main_artwork_image' => $row['main_artwork_image'],
            'created_at' => $row['created_at'],
            'items' => []
        ];
        
        // For artists, add buyer information
        if ($user_type === 'artist' && isset($row['first_name'])) {
            $order_data['buyer_name'] = trim($row['first_name'] . ' ' . $row['last_name']);
        }
        
        $orders[$oid] = $order_data;
    }
    
    // Only add item if it exists (not null from LEFT JOIN)
    if ($row['order_item_id'] !== null) {
        $orders[$oid]['items'][] = [
            'order_item_id' => $row['order_item_id'],
            'artwork_id' => $row['item_artwork_id'],
            'artwork_title' => $row['artwork_title'],
            'artwork_image' => $row['artwork_image'],
            'price' => $row['price'],
            'quantity' => $row['quantity']
        ];
    }
}
$stmt->close();

echo json_encode(array_values($orders));
