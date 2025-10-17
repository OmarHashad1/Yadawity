<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db.php';

function getAchievements($artist_id) {
    global $db;
    
    try {
        // Validate input
        if (!$artist_id || !is_numeric($artist_id)) {
            throw new InvalidArgumentException('Valid artist ID is required');
        }
        
        // Check if artist_achievements table exists
        $table_check = $db->query("SHOW TABLES LIKE 'artist_achievements'");
        if (!$table_check || $table_check->num_rows === 0) {
            // Return empty array if table doesn't exist
            return [
                'success' => true,
                'message' => 'No achievements found',
                'data' => []
            ];
        }
        
        // Get achievements for the user
        $query = "SELECT achievement_id, achievement_name 
                  FROM artist_achievements 
                  WHERE user_id = ? 
                  ORDER BY achievement_id DESC";
        
        $stmt = $db->prepare($query);
        
        if (!$stmt) {
            throw new Exception('Failed to prepare query: ' . $db->error);
        }
        
        $stmt->bind_param("i", $artist_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to execute query: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $achievements = [];
        
        while ($row = $result->fetch_assoc()) {
            $achievements[] = [
                'achievement_id' => (int)$row['achievement_id'],
                'achievement_name' => $row['achievement_name']
            ];
        }
        
        $stmt->close();
        
        return [
            'success' => true,
            'message' => count($achievements) > 0 ? 'Achievements retrieved successfully' : 'No achievements found',
            'data' => $achievements,
            'count' => count($achievements)
        ];
        
    } catch (InvalidArgumentException $e) {
        http_response_code(400);
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    } catch (Exception $e) {
        error_log("Get achievements error: " . $e->getMessage());
        http_response_code(500);
        return [
            'success' => false,
            'message' => 'Server error occurred while retrieving achievements'
        ];
    }
}

try {
    // Only allow GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Only GET requests are allowed', 405);
    }
    
    // Get artist ID from URL parameter first (for viewing other users' achievements)
    $artist_id = null;
    
    // First try to get from URL parameter
    if (isset($_GET['artist_id'])) {
        $artist_id = (int)$_GET['artist_id'];
    }
    
    // Also check for user_id parameter
    if (!$artist_id && isset($_GET['user_id'])) {
        $artist_id = (int)$_GET['user_id'];
    }
    
    // Fallback to cookie (for logged-in user viewing their own profile)
    if (!$artist_id && isset($_COOKIE['user_login'])) {
        $cookie_value = $_COOKIE['user_login'];
        $parts = explode('_', $cookie_value);
        if (count($parts) > 0 && is_numeric($parts[0])) {
            $artist_id = (int)$parts[0];
        }
    }
    
    if (!$artist_id) {
        throw new Exception('Artist ID is required', 400);
    }
    
    // Get achievements
    $response = getAchievements($artist_id);
    
    // Output JSON response
    echo json_encode($response);
    
} catch (Exception $e) {
    $code = $e->getCode() === 405 ? 405 : ($e->getCode() === 400 ? 400 : 500);
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
