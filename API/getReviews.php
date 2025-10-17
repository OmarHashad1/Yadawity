<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db.php';

// Use correct database connection variable
$conn = $db;

try {
    $artist_id = isset($_GET['artist_id']) ? (int)$_GET['artist_id'] : 0;
    
    if (!$artist_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Artist ID is required'
        ]);
        exit;
    }

    // Check if reviews table exists first
    $table_check = $conn->query("SHOW TABLES LIKE 'reviews'");
    if ($table_check->num_rows === 0) {
        // Reviews table doesn't exist, return empty reviews
        echo json_encode([
            'success' => true,
            'reviews' => []
        ]);
        exit;
    }

    // Get reviews for the artist
    $query = "
        SELECT 
            r.review_id,
            r.user_name,
            r.rating,
            r.comment,
            r.created_at,
            r.artwork_id,
            a.title as artwork_title
        FROM reviews r
        LEFT JOIN artworks a ON r.artwork_id = a.artwork_id
        WHERE r.artist_id = ? OR r.artwork_id IN (
            SELECT artwork_id FROM artworks WHERE artist_id = ?
        )
        ORDER BY r.created_at DESC
        LIMIT 50
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        // If query fails, return empty reviews
        echo json_encode([
            'success' => true,
            'reviews' => []
        ]);
        exit;
    }
    
    $stmt->bind_param("ii", $artist_id, $artist_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = [
            'review_id' => (int)$row['review_id'],
            'user_name' => $row['user_name'],
            'rating' => (int)$row['rating'],
            'comment' => $row['comment'],
            'created_at' => $row['created_at'],
            'artwork_id' => $row['artwork_id'] ? (int)$row['artwork_id'] : null,
            'artwork_title' => $row['artwork_title']
        ];
    }

    echo json_encode([
        'success' => true,
        'reviews' => $reviews
    ]);

} catch (Exception $e) {
    // Return empty reviews on error to prevent frontend crashes
    echo json_encode([
        'success' => true,
        'reviews' => []
    ]);
}

$conn->close();
?>
