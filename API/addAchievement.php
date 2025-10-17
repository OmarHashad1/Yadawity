<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'db.php';

function addAchievement($artist_id, $achievement_text) {
    global $db;
    
    try {
        // Validate input
        if (!$artist_id || !is_numeric($artist_id)) {
            throw new InvalidArgumentException('Valid artist ID is required');
        }
        
        if (!$achievement_text || trim($achievement_text) === '') {
            throw new InvalidArgumentException('Achievement text is required');
        }
        
        $achievement_text = trim($achievement_text);
        
        // Check if achievement text is too long
        if (strlen($achievement_text) > 255) {
            throw new InvalidArgumentException('Achievement text must be 255 characters or less');
        }
        
        // Check if artist_achievements table exists, create if it doesn't
        $table_check = $db->query("SHOW TABLES LIKE 'artist_achievements'");
        if (!$table_check || $table_check->num_rows === 0) {
            $create_table = "CREATE TABLE artist_achievements (
                achievement_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                achievement_name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if (!$db->query($create_table)) {
                throw new Exception('Failed to create achievements table: ' . $db->error);
            }
        }
        
        // Check if the achievement already exists for this user
        $check_query = "SELECT achievement_id FROM artist_achievements WHERE user_id = ? AND achievement_name = ?";
        $check_stmt = $db->prepare($check_query);
        
        if (!$check_stmt) {
            throw new Exception('Failed to prepare check query: ' . $db->error);
        }
        
        $check_stmt->bind_param("is", $artist_id, $achievement_text);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $check_stmt->close();
            throw new InvalidArgumentException('This achievement already exists');
        }
        
        $check_stmt->close();
        
        // Insert new achievement
        $insert_query = "INSERT INTO artist_achievements (user_id, achievement_name) VALUES (?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        
        if (!$insert_stmt) {
            throw new Exception('Failed to prepare insert query: ' . $db->error);
        }
        
        $insert_stmt->bind_param("is", $artist_id, $achievement_text);
        
        if (!$insert_stmt->execute()) {
            throw new Exception('Failed to add achievement: ' . $insert_stmt->error);
        }
        
        $achievement_id = $db->insert_id;
        $insert_stmt->close();
        
        return [
            'success' => true,
            'message' => 'Achievement added successfully',
            'data' => [
                'achievement_id' => $achievement_id,
                'achievement_name' => $achievement_text,
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
        error_log("Add achievement error: " . $e->getMessage());
        http_response_code(500);
        return [
            'success' => false,
            'message' => 'Server error occurred while adding achievement'
        ];
    }
}

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed', 405);
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
    
    // Get achievement text from POST data
    $achievement_text = null;
    
    // Check if it's JSON data
    $input = file_get_contents('php://input');
    if ($input) {
        $json_data = json_decode($input, true);
        if ($json_data && isset($json_data['achievement'])) {
            $achievement_text = $json_data['achievement'];
        }
    }
    
    // Fallback to form data
    if (!$achievement_text && isset($_POST['achievement'])) {
        $achievement_text = $_POST['achievement'];
    }
    
    if (!$achievement_text) {
        throw new Exception('Achievement text is required', 400);
    }
    
    // Add achievement
    $response = addAchievement($artist_id, $achievement_text);
    
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
