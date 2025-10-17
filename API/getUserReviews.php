<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "db.php";
require_once "checkCredentials.php";

// Use correct database connection variable
$conn = $db;

// Check authentication - either through standard method or POST parameter
$user_id = null;

if ($isAuthenticated) {
    // Use authenticated user ID
    $user_id = $currentUserId;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    // For testing purposes, allow direct user ID
    $user_id = (int)$_POST['user_id'];
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

try {
    // Start with a simple query to get reviews
    $sql = "SELECT 
                review_id,
                user_name,
                rating,
                comment,
                review_type,
                created_at,
                artwork_id
            FROM reviews 
            WHERE user_id = ?
            ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $review = [
            'review_id' => $row['review_id'],
            'user_name' => $row['user_name'],
            'rating' => (int)$row['rating'],
            'comment' => $row['comment'],
            'review_type' => $row['review_type'],
            'created_at' => $row['created_at'],
            'title' => $row['review_type'] === 'artwork' ? 'Artwork Review' : 'Gallery Review',
            'artist_name' => 'Unknown Artist',
            'type' => ucfirst($row['review_type'])
        ];
        
        $reviews[] = $review;
    }
    
    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'total_count' => count($reviews)
    ]);
    
} catch (Exception $e) {
    error_log("getUserReviews Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'debug_info' => [
            'user_id' => $user_id,
            'query_attempted' => true
        ]
    ]);
}

// Close the database connection
if (isset($conn)) {
    $conn->close();
}
?>
