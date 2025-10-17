<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once "db.php";
require_once "checkCredentials.php";

try {
    // Check if user is authenticated
    if (!$isAuthenticated) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    $user_id = $currentUserId;

    // Check if required parameters are provided
    if (!isset($_POST['review_id']) || !isset($_POST['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }

    $review_id = (int)$_POST['review_id'];
    $posted_user_id = (int)$_POST['user_id'];

    // Verify that the posted user_id matches the authenticated user
    if ($user_id !== $posted_user_id) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    // Check if the review exists and belongs to this user
    $check_query = "SELECT review_id FROM reviews WHERE review_id = ? AND user_id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bind_param("ii", $review_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Review not found or you do not have permission to delete it']);
        exit;
    }
    $check_stmt->close();

    // Delete the review
    $delete_query = "DELETE FROM reviews WHERE review_id = ? AND user_id = ?";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->bind_param("ii", $review_id, $user_id);
    
    if ($delete_stmt->execute()) {
        if ($delete_stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Review deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No review was deleted']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete review']);
    }
    
    $delete_stmt->close();

} catch (Exception $e) {
    error_log("deleteReview Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

// Close the database connection
if (isset($db)) {
    $db->close();
}
?>
