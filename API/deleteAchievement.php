<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db.php';

function deleteAchievement($artist_id, $achievement_id = null, $achievement_name = null) {
    global $db;
    
    try {
        // Validate input
        if (!$artist_id || !is_numeric($artist_id)) {
            throw new InvalidArgumentException('Valid artist ID is required');
        }
        
        if (!$achievement_id && !$achievement_name) {
            throw new InvalidArgumentException('Either achievement ID or achievement name is required');
        }
        
        // Check if artist_achievements table exists
        $table_check = $db->query("SHOW TABLES LIKE 'artist_achievements'");
        if (!$table_check || $table_check->num_rows === 0) {
            throw new Exception('Achievements table does not exist');
        }
        
        // Prepare the delete query based on what we have
        if ($achievement_id && is_numeric($achievement_id)) {
            // Delete by achievement ID
            $delete_query = "DELETE FROM artist_achievements WHERE user_id = ? AND achievement_id = ?";
            $delete_stmt = $db->prepare($delete_query);
            
            if (!$delete_stmt) {
                throw new Exception('Failed to prepare delete query: ' . $db->error);
            }
            
            $delete_stmt->bind_param("ii", $artist_id, $achievement_id);
            
        } else if ($achievement_name) {
            // Delete by achievement name
            $delete_query = "DELETE FROM artist_achievements WHERE user_id = ? AND achievement_name = ?";
            $delete_stmt = $db->prepare($delete_query);
            
            if (!$delete_stmt) {
                throw new Exception('Failed to prepare delete query: ' . $db->error);
            }
            
            $delete_stmt->bind_param("is", $artist_id, $achievement_name);
        }
        
        // Execute the delete
        if (!$delete_stmt->execute()) {
            throw new Exception('Failed to delete achievement: ' . $delete_stmt->error);
        }
        
        $affected_rows = $delete_stmt->affected_rows;
        $delete_stmt->close();
        
        if ($affected_rows === 0) {
            throw new InvalidArgumentException('Achievement not found or you do not have permission to delete it');
        }
        
        return [
            'success' => true,
            'message' => 'Achievement deleted successfully',
            'data' => [
                'deleted_count' => $affected_rows,
                'user_id' => $artist_id
            ]
        ];
        
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    } catch (Exception $e) {
        error_log("Delete achievement error: " . $e->getMessage());
        http_response_code(500);
        return [
            'success' => false,
            'message' => 'Server error occurred while deleting achievement'
        ];
    }
}

try {
    // Allow POST and DELETE requests
    if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
        throw new Exception('Only POST and DELETE requests are allowed', 405);
    }
    
    // Get artist ID from cookie
    $artist_id = null;
    
    if (isset($_COOKIE['user_login'])) {
        $cookie_value = $_COOKIE['user_login'];
        $parts = explode('_', $cookie_value);
        if (count($parts) > 0 && is_numeric($parts[0])) {
            $artist_id = (int)$parts[0];
        }
    }
    
    if (!$artist_id) {
        throw new Exception('User authentication required', 401);
    }
    
    // Get achievement data
    $achievement_id = null;
    $achievement_name = null;
    
    // Check if it's JSON data
    $input = file_get_contents('php://input');
    if ($input) {
        $json_data = json_decode($input, true);
        if ($json_data) {
            $achievement_id = isset($json_data['achievement_id']) ? $json_data['achievement_id'] : null;
            $achievement_name = isset($json_data['achievement_name']) ? $json_data['achievement_name'] : null;
            
            // Also check for 'achievement' field for backward compatibility
            if (!$achievement_name && isset($json_data['achievement'])) {
                $achievement_name = $json_data['achievement'];
            }
        }
    }
    
    // Fallback to form data
    if (!$achievement_id && !$achievement_name) {
        $achievement_id = isset($_POST['achievement_id']) ? $_POST['achievement_id'] : null;
        $achievement_name = isset($_POST['achievement_name']) ? $_POST['achievement_name'] : null;
        
        // Also check for 'achievement' field for backward compatibility
        if (!$achievement_name && isset($_POST['achievement'])) {
            $achievement_name = $_POST['achievement'];
        }
    }
    
    // Also check URL parameters for GET-style parameters in POST
    if (!$achievement_id && !$achievement_name) {
        $achievement_id = isset($_GET['achievement_id']) ? $_GET['achievement_id'] : null;
        $achievement_name = isset($_GET['achievement_name']) ? $_GET['achievement_name'] : null;
        
        if (!$achievement_name && isset($_GET['achievement'])) {
            $achievement_name = $_GET['achievement'];
        }
    }
    
    if (!$achievement_id && !$achievement_name) {
        throw new Exception('Either achievement_id or achievement_name is required', 400);
    }
    
    // Delete achievement
    $response = deleteAchievement($artist_id, $achievement_id, $achievement_name);
    
    // Output JSON response
    echo json_encode($response);
    
} catch (Exception $e) {
    $code = $e->getCode() === 405 ? 405 : ($e->getCode() === 401 ? 401 : ($e->getCode() === 400 ? 400 : 500));
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($db) && $db) {
        $db->close();
    }
}
?>
