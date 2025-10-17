<?php
require_once 'db.php';

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Check if photo_id is provided
    if (!isset($input['photo_id']) || empty($input['photo_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Photo ID is required',
            'error_code' => 'MISSING_PHOTO_ID'
        ]);
        exit;
    }
    
    $photo_id = intval($input['photo_id']);
    
    // Start transaction
    $db->begin_transaction();
    
    try {
        // First, check if the photo exists and get artwork_id
        $stmt = $db->prepare("SELECT artwork_id, photo_path FROM artwork_photos WHERE photo_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        
        $stmt->bind_param("i", $photo_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $db->rollback();
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'message' => 'Photo not found',
                'error_code' => 'PHOTO_NOT_FOUND'
            ]);
            exit;
        }
        
        $photo = $result->fetch_assoc();
        $artwork_id = $photo['artwork_id'];
        $photo_path = $photo['photo_path'];
        
        // Remove primary flag from all photos of this artwork
        $stmt = $db->prepare("UPDATE artwork_photos SET is_primary = 0 WHERE artwork_id = ?");
        $stmt->bind_param("i", $artwork_id);
        $stmt->execute();
        
        // Set the selected photo as primary
        $stmt = $db->prepare("UPDATE artwork_photos SET is_primary = 1 WHERE photo_id = ?");
        $stmt->bind_param("i", $photo_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to set primary photo");
        }
        
        // Also update the main artwork table to use this photo as the primary image
        $stmt = $db->prepare("UPDATE artworks SET artwork_image = ? WHERE artwork_id = ?");
        $stmt->bind_param("si", $photo_path, $artwork_id);
        $stmt->execute();
        
        // Commit transaction
        $db->commit();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Primary photo updated successfully',
            'data' => [
                'photo_id' => $photo_id,
                'artwork_id' => $artwork_id,
                'photo_path' => $photo_path
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Set primary artwork photo error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to set primary photo: ' . $e->getMessage(),
        'error_code' => 'SERVER_ERROR'
    ]);
}

// Close connection
$db->close();
?>
