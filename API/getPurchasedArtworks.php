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
    // For now, we'll return empty data since session management is not fully implemented
    // This prevents the 500 error and allows the page to load
    $artist_id = isset($_GET['artist_id']) ? (int)$_GET['artist_id'] : 0;
    
    if (!$artist_id) {
        // Clear any buffered output
        ob_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Artist ID is required'
        ]);
        exit;
    }

    // For now, return empty data to prevent errors
    // This can be enhanced when user session management is properly implemented
    
    // Clear any buffered output
    ob_clean();
    echo json_encode([
        'success' => true,
        'data' => []  // Changed from 'artworks' to 'data' to match JavaScript expectations
    ]);

} catch (Exception $e) {
    // Clear any buffered output
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>
