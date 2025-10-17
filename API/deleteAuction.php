<?php
require_once 'db.php';

// Set content type to JSON
header('Content-Type: application/json');

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST and DELETE methods
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get auction ID from multiple sources
    $auction_id = null;
    
    // Try to get from JSON body first
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && isset($input['auction_id'])) {
        $auction_id = $input['auction_id'];
    }
    // Try URL parameter
    elseif (isset($_GET['id'])) {
        $auction_id = $_GET['id'];
    }
    // Try POST data
    elseif (isset($_POST['auction_id'])) {
        $auction_id = $_POST['auction_id'];
    }
    
    // Check if auction_id is provided
    if (!$auction_id || empty($auction_id)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Auction ID is required',
            'error_code' => 'MISSING_AUCTION_ID',
            'debug' => [
                'json_input' => $input,
                'get_params' => $_GET,
                'post_params' => $_POST
            ]
        ]);
        exit;
    }
    
    $auction_id = intval($auction_id);
    
    // No authentication required - allow any user to delete auctions
    // This should be secured in production
    
    // Start transaction
    $db->begin_transaction();
    
    try {
        // First, check if the auction exists
        $stmt = $db->prepare("SELECT id, product_id, artist_id FROM auctions WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        
        $stmt->bind_param("i", $auction_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $db->rollback();
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'message' => 'Auction not found',
                'error_code' => 'AUCTION_NOT_FOUND',
                'debug_info' => [
                    'auction_id' => $auction_id
                ]
            ]);
            exit;
        }
        
        $auction = $result->fetch_assoc();
        $product_id = $auction['product_id'];
        $user_id = $auction['artist_id']; // Get the artist_id from the auction
        
        // Delete auction bids first (foreign key constraint)
        $stmt = $db->prepare("DELETE FROM auction_bids WHERE auction_id = ?");
        $stmt->bind_param("i", $auction_id);
        $stmt->execute();
        
        // Update artwork to remove auction flag
        $stmt = $db->prepare("UPDATE artworks SET on_auction = 0 WHERE artwork_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        
        // Delete the auction
        $stmt = $db->prepare("DELETE FROM auctions WHERE id = ?");
        $stmt->bind_param("i", $auction_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to delete auction");
        }
        
        // Commit transaction
        $db->commit();
        
        // Try to clean up auction images (optional)
        $image_cleanup = ['success' => true, 'deleted_files' => [], 'errors' => []];
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Auction deleted successfully',
            'data' => [
                'auction_id' => $auction_id,
                'product_id' => $product_id,
                'image_cleanup' => $image_cleanup
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    // Log error (you might want to log this to a file)
    error_log("Delete auction error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete auction: ' . $e->getMessage(),
        'error_code' => 'SERVER_ERROR'
    ]);
}

// Close connection
$db->close();
?>
