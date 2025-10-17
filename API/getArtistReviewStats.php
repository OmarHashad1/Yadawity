<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Prevent any output before JSON
ob_start();

require_once 'db.php';

// Use correct database connection variable
$conn = $db;

try {
    $artist_id = isset($_GET['artist_id']) ? (int)$_GET['artist_id'] : 0;
    
    if (!$artist_id) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Artist ID is required'
        ]);
        exit;
    }

    // Check if reviews table exists first
    $table_check = $conn->query("SHOW TABLES LIKE 'reviews'");
    if ($table_check->num_rows === 0) {
        // Reviews table doesn't exist, return default values
        ob_clean();
        echo json_encode([
            'success' => true,
            'data' => [
                'average_rating' => 0.0,
                'total_reviews' => 0
            ]
        ]);
        exit;
    }

    // Get review statistics for the artist
    $query = "
        SELECT 
            COALESCE(AVG(CAST(rating AS DECIMAL(3,2))), 0.0) as average_rating,
            COUNT(*) as total_reviews
        FROM reviews 
        WHERE artist_id = ? OR artwork_id IN (
            SELECT artwork_id FROM artworks WHERE artist_id = ?
        )
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        // If query fails, return default values
        ob_clean();
        echo json_encode([
            'success' => true,
            'data' => [
                'average_rating' => 0.0,
                'total_reviews' => 0
            ]
        ]);
        exit;
    }
    
    $stmt->bind_param("ii", $artist_id, $artist_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'data' => [
                'average_rating' => (float)$row['average_rating'],
                'total_reviews' => (int)$row['total_reviews']
            ]
        ]);
    } else {
        // No reviews found
        ob_clean();
        echo json_encode([
            'success' => true,
            'data' => [
                'average_rating' => 0.0,
                'total_reviews' => 0
            ]
        ]);
    }

} catch (Exception $e) {
    // Return default values on error to prevent frontend crashes
    ob_clean();
    echo json_encode([
        'success' => true,
        'data' => [
            'average_rating' => 0.0,
            'total_reviews' => 0
        ]
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>
