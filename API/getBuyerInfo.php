<?php
session_start();
header('Content-Type: application/json');

// Include database connection
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get user information and determine user type
    $query = "SELECT 
                *
              FROM users
              WHERE user_id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $user_type = strtolower($row['user_type']);
        
        // Initialize counts
        $orders_count = 0;
        $wishlist_count = 0;
        $reviews_count = 0;
        
        // Get counts - treat all users (buyers and artists) the same way as buyers
        
        // Get orders as buyer (purchases)
        $orders_query = "SELECT COUNT(*) as count FROM orders WHERE buyer_id = ?";
        $orders_stmt = $db->prepare($orders_query);
        $orders_stmt->bind_param("i", $user_id);
        $orders_stmt->execute();
        $orders_result = $orders_stmt->get_result();
        if ($orders_row = $orders_result->fetch_assoc()) {
            $orders_count = $orders_row['count'];
        }
        $orders_stmt->close();
        
        // Get wishlist count
        $wishlist_query = "SELECT COUNT(*) as count FROM wishlists WHERE user_id = ?";
        $wishlist_stmt = $db->prepare($wishlist_query);
        $wishlist_stmt->bind_param("i", $user_id);
        $wishlist_stmt->execute();
        $wishlist_result = $wishlist_stmt->get_result();
        if ($wishlist_row = $wishlist_result->fetch_assoc()) {
            $wishlist_count = $wishlist_row['count'];
        }
        $wishlist_stmt->close();
        
        // Get reviews given by user
        $reviews_query = "SELECT COUNT(*) as count FROM reviews WHERE user_id = ?";
        $reviews_stmt = $db->prepare($reviews_query);
        $reviews_stmt->bind_param("i", $user_id);
        $reviews_stmt->execute();
        $reviews_result = $reviews_stmt->get_result();
        if ($reviews_row = $reviews_result->fetch_assoc()) {
            $reviews_count = $reviews_row['count'];
        }
        $reviews_stmt->close();
        
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $row['user_id'],
                'first_name' => $row['first_name'] ?? '',
                'last_name' => $row['last_name'] ?? '',
                'email' => $row['email'] ?? '',
                'phone' => $row['phone'] ?? '',
                'location' => $row['location'] ?? '',
                'address' => $row['location'] ?? '', // Map location to address for compatibility
                'profile_picture' => $row['profile_picture'] ?? '',
                'user_type' => $row['user_type'] ?? '',
                'member_since' => isset($row['created_at']) ? date('Y', strtotime($row['created_at'])) : date('Y'),
                'orders_count' => $orders_count,
                'wishlist_count' => $wishlist_count,
                'reviews_count' => $reviews_count
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$db->close();
?>
