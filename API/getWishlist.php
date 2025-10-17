<?php

require_once "db.php";
require_once "checkCredentials.php";

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Response function
function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
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

// Function to get wishlist items
function getWishlistItems($db, $user_id) {
    // Updated SQL query to match actual table structure
    $sql = "SELECT 
                w.id as wishlist_id,
                w.created_at as added_to_wishlist,
                a.artwork_id,
                a.title,
                a.description,
                a.price,
                a.dimensions,
                a.year,
                a.category,
                a.style,
                a.artwork_image,
                ap.image_path as artwork_photo_filename,
                a.type,
                a.is_available,
                a.on_auction,
                a.stock_quantity,
                a.on_sale,
                a.sale_price,
                a.status,
                u.user_id as artist_id,
                u.first_name as artist_first_name,
                u.last_name as artist_last_name,
                u.profile_picture as artist_profile_picture,
                u.art_specialty as artist_specialty
            FROM wishlists w
            INNER JOIN artworks a ON w.product_id = a.artwork_id
            INNER JOIN users u ON a.artist_id = u.user_id
            LEFT JOIN artwork_photos ap ON a.artwork_id = ap.artwork_id AND (ap.is_primary = 1 OR ap.is_primary IS NULL)
            WHERE w.user_id = ? AND w.is_active = 1
            ORDER BY w.created_at DESC";

    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $db->error);
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $wishlist_items = [];
    
    while ($row = $result->fetch_assoc()) {
        // compute a usable artwork image URL; prefer artwork_photos filename, fallback to artworks.artwork_image
        $image_url = null;
        if (!empty($row['artwork_photo_filename'])) {
            $image_url = './uploads/artworks/' . $row['artwork_photo_filename'];
        } else if (!empty($row['artwork_image'])) {
            $image_url = './uploads/artworks/' . $row['artwork_image'];
        } else {
            $image_url = null;
        }

        $wishlist_items[] = [
            'wishlist_id' => (int)$row['wishlist_id'],
            'added_to_wishlist' => $row['added_to_wishlist'],
            'artwork' => [
                'artwork_id' => (int)$row['artwork_id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'price' => (float)$row['price'],
                'sale_price' => $row['sale_price'] ? (float)$row['sale_price'] : null,
                'on_sale' => (bool)$row['on_sale'],
                'dimensions' => $row['dimensions'],
                'year' => $row['year'],
                'category' => $row['category'],
                'style' => $row['style'],
                'artwork_image' => $row['artwork_image'],
                'artwork_image_url' => $image_url,
                'artwork_photo_filename' => $row['artwork_photo_filename'],
                'type' => $row['type'],
                'is_available' => (bool)$row['is_available'],
                'on_auction' => (bool)$row['on_auction'],
                'stock_quantity' => (int)$row['stock_quantity'],
                'status' => $row['status']
            ],
            'artist' => [
                'artist_id' => (int)$row['artist_id'],
                'name' => $row['artist_first_name'] . ' ' . $row['artist_last_name'],
                'first_name' => $row['artist_first_name'],
                'last_name' => $row['artist_last_name'],
                'profile_picture' => $row['artist_profile_picture'],
                'art_specialty' => $row['artist_specialty']
            ]
        ];
    }

    $stmt->close();
    return $wishlist_items;
}

// Main execution
try {
    // Get user ID from credentials
    $user_id = getUserIdFromCredentials();
    
    // Get wishlist items
    $wishlist_items = getWishlistItems($db, $user_id);
    
    // Return successful response
    sendResponse(true, 'Wishlist retrieved successfully', [
        'user_id' => $user_id,
        'total_items' => count($wishlist_items),
        'wishlist_items' => $wishlist_items
    ]);

} catch (Exception $e) {
    sendResponse(false, 'Error retrieving wishlist: ' . $e->getMessage());
} finally {
    if (isset($db)) {
        $db->close();
    }
}

?>