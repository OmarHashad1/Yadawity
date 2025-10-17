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
    
    // Check if required parameters are provided
    if (!isset($input['photo_id']) || empty($input['photo_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Photo ID is required',
            'error_code' => 'MISSING_PHOTO_ID'
        ]);
        exit;
    }
    
    if (!isset($input['artwork_id']) || empty($input['artwork_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Artwork ID is required',
            'error_code' => 'MISSING_ARTWORK_ID'
        ]);
        exit;
    }
    
    $photo_id = intval($input['photo_id']);
    $artwork_id = intval($input['artwork_id']);
    
    // Start transaction
    $db->begin_transaction();
    
    try {
        // First, verify that the photo exists and belongs to the artwork
        $stmt = $db->prepare("SELECT photo_id, photo_path FROM artwork_photos WHERE photo_id = ? AND artwork_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        
        $stmt->bind_param("ii", $photo_id, $artwork_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $db->rollback();
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'message' => 'Photo not found or does not belong to this artwork',
                'error_code' => 'PHOTO_NOT_FOUND'
            ]);
            exit;
        }
        
        $photo_data = $result->fetch_assoc();
        $photo_path = $photo_data['photo_path'];
        
        // Reset all photos for this artwork to not be primary
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
        
        // Update the main artwork table with the new primary image
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
    error_log("Set primary photo error: " . $e->getMessage());
    
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
